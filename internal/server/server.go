package server

import (
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"sync"
	"time"
	"path/filepath"

	"github.com/ClickHouse/clickhouse-go/v2"
	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/action"
	"github.com/skyplix/zai-tds/internal/admin/handler"
	"github.com/skyplix/zai-tds/internal/admin/repository"
	"github.com/skyplix/zai-tds/internal/analytics"
	"github.com/skyplix/zai-tds/internal/attribution"
	"github.com/skyplix/zai-tds/internal/auth"

	"github.com/skyplix/zai-tds/internal/binding"
	"github.com/skyplix/zai-tds/internal/botdb"
	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/config"
	"github.com/skyplix/zai-tds/internal/device"
	"github.com/skyplix/zai-tds/internal/filter"
	"github.com/skyplix/zai-tds/internal/geo"
	"github.com/skyplix/zai-tds/internal/hitlimit"
	"github.com/skyplix/zai-tds/internal/ratelimit"

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
	cfg             *config.Config
	logger          *zap.Logger
	version         string
	startTime       time.Time
	http            *http.Server
	db              *pgxpool.Pool
	valkey          *redis.Client
	geo             *geo.Resolver
	device          *device.Detector
	chWriter        *queue.Writer
	chReader        driver.Conn
	workers         *worker.Manager
	adminHandler    *handler.Handler
	postbackHandler *handler.PostbackHandler
	reportsHandler  *handler.ReportsHandler
	authSvc         *auth.Service
	botDB           *botdb.ValkeyStore

	uaStore         *botdb.UAStore
	ratelimiter     *ratelimit.Service

	cache          *cache.Cache
	filterEngine   *filter.Engine
	cidrFilter     *filter.CIDRFilter
	sessionSvc     *session.Service
	rotator        *rotator.Rotator
	actionEngine   *action.Engine
	hitlimitSvc    *hitlimit.Service
	bindingSvc     *binding.Service
	lpTokenSvc     *lptoken.Service
	attributionSvc *attribution.Service

	pipelineL1 *pipeline.Pipeline
	pipelineL2 *pipeline.Pipeline
}

// New constructs a Server, connects to all databases, initializes workers.
func New(cfg *config.Config, logger *zap.Logger, version string) (*Server, error) {
	s := &Server{
		cfg:       cfg,
		logger:    logger,
		version:   version,
		startTime: time.Now(),
		device:    device.New(),
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
	geoResolver, _ := geo.New(cfg.GeoIP.CountryDB, cfg.GeoIP.CityDB, cfg.GeoIP.ASNDB, logger)
	s.geo = geoResolver

	// ClickHouse
	chWriter, _ := queue.NewWriter(cfg.ClickHouse.Addr, cfg.ClickHouse.Database, logger)
	s.chWriter = chWriter

	// ClickHouse read client (used for postback attribution fallback)
	if cfg.ClickHouse.Addr != "" {
		chConn, err := clickhouse.Open(&clickhouse.Options{
			Addr: []string{cfg.ClickHouse.Addr},
			Auth: clickhouse.Auth{
				Database: cfg.ClickHouse.Database,
				Username: cfg.ClickHouse.Username,
				Password: cfg.ClickHouse.Password,
			},
			DialTimeout:     5 * time.Second,
			MaxOpenConns:    3,
			MaxIdleConns:    1,
			ConnMaxLifetime: time.Hour,
		})
		if err != nil {
			logger.Warn("clickhouse reader init failed", zap.Error(err))
		} else if err := chConn.Ping(ctx); err != nil {
			logger.Warn("clickhouse reader ping failed", zap.Error(err))
		} else {
			s.chReader = chConn
		}
	}

	// Phase 2 services
	s.cache = cache.New(s.valkey, s.db, logger)
	s.filterEngine = filter.NewEngine()
	s.sessionSvc = session.New(s.valkey, logger)

	// CIDR Filter (Phase 19)
	botsFilePath := filepath.Join("reference", "YellowCloaker", "bases", "bots.txt")
	cidrF, err := filter.NewCIDRFilterFromFile(botsFilePath)
	if err != nil {
		logger.Warn("Failed to load CIDR bots file, using empty filter", zap.Error(err))
		// Optional: s.cidrFilter = &filter.CIDRFilter{} // will just return false for all Contains
	} else {
		s.cidrFilter = cidrF
	}
	s.rotator = rotator.New()
	s.actionEngine = action.NewEngine()
	s.hitlimitSvc = hitlimit.New(s.valkey, logger)
	s.bindingSvc = binding.New(s.valkey, logger)
	s.lpTokenSvc = lptoken.New(s.valkey, logger)
	s.attributionSvc = attribution.New(s.valkey, logger)

	// Bot IP Database (Plan 4.1/4.2)
	botDB, err := botdb.NewValkeyStore(s.valkey)
	if err != nil {
		return nil, fmt.Errorf("botdb init: %w", err)
	}
	s.botDB = botDB

	uaStore, err := botdb.NewUAStore(s.valkey)
	if err != nil {
		return nil, fmt.Errorf("uastore init: %w", err)
	}
	s.uaStore = uaStore

	s.ratelimiter = ratelimit.New(s.valkey, logger)

	// Auth Service (Phase 6)
	s.authSvc = auth.NewService(s.db, cfg.System.Salt)

	// Admin Handler
	s.adminHandler = handler.NewHandler(s.db, s.cache, s.botDB, s.uaStore, logger)

	var convChan chan<- queue.ConversionRecord
	if s.chWriter != nil {
		convChan = s.chWriter.ConvChan()
	}

	// Postback Handler (Phase 5.2)
	s.postbackHandler = handler.NewPostbackHandler(
		logger,
		repository.NewSettingsRepository(s.db),
		s.attributionSvc,
		s.chReader,
		convChan,
	)

	// Analytics & Reports (Phase 5.3)
	if s.chReader != nil {
		analyticsSvc := analytics.New(s.chReader, s.db, logger)
		s.reportsHandler = handler.NewReportsHandler(logger, analyticsSvc)
	}

	// Workers (AUDIT FIX #5)

	s.workers = worker.NewManager(logger,
		worker.NewCacheWarmupWorker(s.valkey, s.cache, logger), // AUDIT FIX #2: pass s.cache (upgraded in 3.5)
		worker.NewSessionJanitorWorker(logger),
		worker.NewHitLimitResetWorker(s.valkey, logger),
	)

	// Warmup cache
	if err := s.cache.Warmup(ctx); err != nil {
		logger.Error("cache warmup failed", zap.Error(err))
	}

	var clickChan chan<- queue.ClickRecord
	if s.chWriter != nil {
		clickChan = s.chWriter.ClickChan()
	}

	// Build singleton pipelines
	s.pipelineL1 = pipeline.New(
		&stage.DomainRedirectStage{},
		&stage.CheckPrefetchStage{},
		&stage.NormalizeIPStage{},
		&stage.BuildRawClickStage{
			BotDB:        s.botDB,
			CustomUA:     s.uaStore,
			Geo:          s.geo,
			CIDRFilter:   s.cidrFilter,
			RateLimiter:  s.ratelimiter,
			IPRateLimit:  cfg.System.RateLimitPerIP,
			IPRateWindow: cfg.System.RateLimitWindow,
		},
		&stage.FindCampaignStage{Cache: s.cache, Logger: logger},
		&stage.CheckDefaultCampaignStage{},
		&stage.UpdateRawClickStage{Geo: s.geo, Device: s.device, Logger: logger},
		&stage.UpdateParamsStage{},
		&stage.CheckParamAliasesStage{Cache: s.cache, Logger: logger},
		&stage.UpdateGlobalUniquenessStage{Session: s.sessionSvc, Logger: logger},
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
		&stage.PrepareRawClickToStoreStage{},
		&stage.CheckSendingToAnotherCampaignStage{},
		&stage.StoreRawClicksStage{ClickChan: clickChan, Attribution: s.attributionSvc},
	)

	s.pipelineL2 = pipeline.New(
		&stage.NormalizeIPStage{},
		&stage.BuildRawClickStage{
			BotDB:        s.botDB,
			CustomUA:     s.uaStore,
			Geo:          s.geo,
			CIDRFilter:   s.cidrFilter,
			RateLimiter:  s.ratelimiter,
			IPRateLimit:  cfg.System.RateLimitPerIP,
			IPRateWindow: cfg.System.RateLimitWindow,
		},
		&stage.L2FindCampaignStage{LPToken: s.lpTokenSvc, Cache: s.cache, Logger: logger},
		&stage.UpdateParamsStage{},
		&stage.ChooseOfferStage{Cache: s.cache, Rotator: s.rotator, Binding: s.bindingSvc, Logger: logger},
		&stage.FindAffiliateNetworkStage{Cache: s.cache},
		&stage.UpdateCostsStage{},
		&stage.UpdatePayoutStage{},
		&stage.SetCookieStage{},
		&stage.ExecuteActionStage{ActionEngine: s.actionEngine, Logger: logger},
		&stage.PrepareRawClickToStoreStage{},
		&stage.CheckSendingToAnotherCampaignStage{},
		&stage.StoreRawClicksStage{ClickChan: clickChan, Attribution: s.attributionSvc},
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

// Handler returns the HTTP handler (multiplexer) for this server.
func (s *Server) Handler() http.Handler {
	return s.http.Handler
}

func (s *Server) handleLogin(w http.ResponseWriter, r *http.Request) {
	var payload struct {
		ApiKey string `json:"api_key"`
	}

	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		http.Error(w, `{"error": "invalid request"}`, http.StatusBadRequest)
		return
	}

	token, err := s.authSvc.Login(r.Context(), payload.ApiKey)
	if err != nil {
		http.Error(w, `{"error": "unauthorized"}`, http.StatusUnauthorized)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	_ = json.NewEncoder(w).Encode(map[string]string{
		"token": token,
	})
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

	// Start background workers (AUDIT FIX #5)
	if s.workers != nil {
		s.workers.StartAll(ctx)
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
	if s.workers != nil {
		s.workers.Wait()
	}
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
