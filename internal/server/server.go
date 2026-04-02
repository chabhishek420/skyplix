package server

import (
	"context"
	"fmt"
	"net/http"
	"sync"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/action"
	"github.com/skyplix/zai-tds/internal/binding"
	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/config"
	"github.com/skyplix/zai-tds/internal/device"
	"github.com/skyplix/zai-tds/internal/filter"
	"github.com/skyplix/zai-tds/internal/geo"
	"github.com/skyplix/zai-tds/internal/hitlimit"

	"github.com/skyplix/zai-tds/internal/lptoken"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/pipeline/stage"
	"github.com/skyplix/zai-tds/internal/queue"
	"github.com/skyplix/zai-tds/internal/rotator"
	"github.com/skyplix/zai-tds/internal/session"
	"github.com/skyplix/zai-tds/internal/worker"
)

// Server is the main application server.
type Server struct {
	cfg      *config.Config
	logger   *zap.Logger
	version  string
	http     *http.Server
	db       *pgxpool.Pool
	valkey   *redis.Client
	geo      *geo.Resolver
	device   *device.Detector
	chWriter *queue.Writer
	workers  *worker.Manager

	cache        *cache.Cache
	filterEngine *filter.Engine
	sessionSvc   *session.Service
	rotator      *rotator.Rotator
	actionEngine *action.Engine
	hitlimitSvc  *hitlimit.Service
	bindingSvc   *binding.Service
	lpTokenSvc   *lptoken.Service

	pipelineL1 *pipeline.Pipeline
	pipelineL2 *pipeline.Pipeline
}

// New constructs a Server, connects to all databases, initializes workers.
func New(cfg *config.Config, logger *zap.Logger, version string) (*Server, error) {
	s := &Server{
		cfg:     cfg,
		logger:  logger,
		version: version,
		device:  device.New(),
	}

	ctx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancel()

	// PostgreSQL
	db, err := pgxpool.New(ctx, cfg.Postgres.DSN)
	if err != nil {
		return nil, fmt.Errorf("postgres connect: %w", err)
	}
	s.db = db

	// Valkey
	vk := redis.NewClient(&redis.Options{
		Addr:     cfg.Valkey.Addr,
		Password: cfg.Valkey.Password,
		DB:       cfg.Valkey.DB,
	})
	s.valkey = vk

	// GeoIP
	geoResolver, _ := geo.New(cfg.GeoIP.CountryDB, cfg.GeoIP.CityDB, logger)
	s.geo = geoResolver

	// ClickHouse
	chWriter, _ := queue.NewWriter(cfg.ClickHouse.Addr, cfg.ClickHouse.Database, logger)
	s.chWriter = chWriter

	// Phase 2 services
	s.cache = cache.New(s.valkey, s.db, logger)
	s.filterEngine = filter.NewEngine()
	s.sessionSvc = session.New(s.valkey, logger)
	s.rotator = rotator.New()
	s.actionEngine = action.NewEngine()
	s.hitlimitSvc = hitlimit.New(s.valkey, logger)
	s.bindingSvc = binding.New(s.valkey, logger)
	s.lpTokenSvc = lptoken.New(s.valkey, logger)

	// Warmup cache
	if err := s.cache.Warmup(ctx); err != nil {
		logger.Error("cache warmup failed", zap.Error(err))
	}

	var clickChan chan<- queue.ClickRecord
	if s.chWriter != nil {
		clickChan = s.chWriter.Chan()
	}

	// Build singleton pipelines
	s.pipelineL1 = pipeline.New(
		&stage.DomainRedirectStage{},
		&stage.CheckPrefetchStage{},
		&stage.BuildRawClickStage{},
		&stage.FindCampaignStage{Cache: s.cache, Logger: logger},
		&stage.CheckDefaultCampaignStage{},
		&stage.UpdateRawClickStage{Geo: s.geo, Device: s.device, Logger: logger},
		&stage.CheckParamAliasesStage{Cache: s.cache, Logger: logger},
		&stage.UpdateCampaignUniquenessStage{Session: s.sessionSvc, Logger: logger},
		&stage.ChooseStreamStage{Cache: s.cache, Filter: s.filterEngine, Rotator: s.rotator, Binding: s.bindingSvc, Logger: logger},
		&stage.UpdateStreamUniquenessStage{Session: s.sessionSvc, Logger: logger},
		&stage.ChooseLandingStage{Cache: s.cache, Rotator: s.rotator, Binding: s.bindingSvc, Logger: logger},
		&stage.ChooseOfferStage{Cache: s.cache, Rotator: s.rotator, Binding: s.bindingSvc, Logger: logger},
		&stage.GenerateTokenStage{},
		&stage.SaveLPTokenStage{LPToken: s.lpTokenSvc},
		&stage.FindAffiliateNetworkStage{Cache: s.cache},
		&stage.UpdateHitLimitStage{Service: s.hitlimitSvc},
		&stage.UpdateCostsStage{},
		&stage.UpdatePayoutStage{},
		&stage.SaveUniquenessSessionStage{Session: s.sessionSvc},
		&stage.SetCookieStage{},
		&stage.ExecuteActionStage{ActionEngine: s.actionEngine, Logger: logger},
		stage.NewNoOp(21, "PrepareRawClickToStore", logger),
		stage.NewNoOp(22, "CheckSendingToAnotherCampaign", logger),
		&stage.StoreRawClicksStage{ClickChan: clickChan},
	)

	s.pipelineL2 = pipeline.New(
		&stage.BuildRawClickStage{},
		&stage.L2FindCampaignStage{LPToken: s.lpTokenSvc, Cache: s.cache, Logger: logger},
		&stage.ChooseOfferStage{Cache: s.cache, Rotator: s.rotator, Binding: s.bindingSvc, Logger: logger},
		&stage.FindAffiliateNetworkStage{Cache: s.cache},
		&stage.UpdateCostsStage{},
		&stage.UpdatePayoutStage{},
		&stage.GenerateTokenStage{},
		&stage.SetCookieStage{},
		&stage.ExecuteActionStage{ActionEngine: s.actionEngine, Logger: logger},
		&stage.StoreRawClicksStage{ClickChan: clickChan},
	)

	// Build HTTP server
	mux := s.routes()
	s.http = &http.Server{
		Addr:         cfg.Addr(),
		Handler:      mux,
		ReadTimeout:  10 * time.Second,
		WriteTimeout: 15 * time.Second,
		IdleTimeout:  60 * time.Second,
	}

	return s, nil
}

// Run starts the HTTP server. Blocks until ctx is cancelled.
func (s *Server) Run(ctx context.Context) error {
	var wg sync.WaitGroup
	
	if s.chWriter != nil {
		wg.Add(1)
		go func() {
			defer wg.Done()
			s.chWriter.Run(ctx)
		}()
	}

	errChan := make(chan error, 1)

	go func() {
		s.logger.Info("HTTP server listening", zap.String("addr", s.http.Addr))
		if err := s.http.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			errChan <- fmt.Errorf("http server: %w", err)
		}
	}()

	// Wait for shutdown signal or error
	select {
	case <-ctx.Done():
		s.logger.Info("Shutdown signal received")
	case err := <-errChan:
		return err
	}

	// 1. Drain HTTP traffic (stop taking new clicks)
	shutdownCtx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()
	if err := s.http.Shutdown(shutdownCtx); err != nil {
		s.logger.Error("HTTP shutdown error", zap.Error(err))
	}

	// 2. Shut down workers (flushes ClickHouse batches)
	wg.Wait()

	// 3. Close connections
	if s.db != nil {
		s.db.Close()
	}
	if s.valkey != nil {
		s.valkey.Close()
	}

	return nil
}
