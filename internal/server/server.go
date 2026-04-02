package server

import (
	"context"
	"fmt"
	"net/http"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/config"
	"github.com/skyplix/zai-tds/internal/device"
	"github.com/skyplix/zai-tds/internal/geo"
	"github.com/skyplix/zai-tds/internal/queue"
	"github.com/skyplix/zai-tds/internal/worker"
)

// Server is the main application server.
// It holds all long-lived dependencies and wires them together.
type Server struct {
	cfg       *config.Config
	logger    *zap.Logger
	version   string
	http      *http.Server
	db        *pgxpool.Pool
	valkey    *redis.Client
	geo       *geo.Resolver
	device    *device.Detector
	chWriter  *queue.Writer
	workers   *worker.Manager
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
	if err := db.Ping(ctx); err != nil {
		return nil, fmt.Errorf("postgres ping: %w", err)
	}
	s.db = db
	logger.Info("PostgreSQL connected")

	// Valkey
	vk := redis.NewClient(&redis.Options{
		Addr:     cfg.Valkey.Addr,
		Password: cfg.Valkey.Password,
		DB:       cfg.Valkey.DB,
	})
	if err := vk.Ping(ctx).Err(); err != nil {
		return nil, fmt.Errorf("valkey ping: %w", err)
	}
	s.valkey = vk
	logger.Info("Valkey connected")

	// GeoIP (graceful — paths may be empty in dev)
	geoResolver, err := geo.New(cfg.GeoIP.CountryDB, cfg.GeoIP.CityDB, logger)
	if err != nil {
		return nil, fmt.Errorf("geoip init: %w", err)
	}
	s.geo = geoResolver

	// ClickHouse async writer
	chWriter, err := queue.NewWriter(cfg.ClickHouse.Addr, cfg.ClickHouse.Database, logger)
	if err != nil {
		// Non-fatal in dev — log warning and continue without ClickHouse
		logger.Warn("clickhouse unavailable — click storage disabled", zap.Error(err))
		s.chWriter = nil
	} else {
		s.chWriter = chWriter
		logger.Info("ClickHouse connected")
	}

	// Build HTTP server with full routes
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

// Run starts workers, then the HTTP server.
// Blocks until ctx is cancelled, then performs graceful shutdown.
func (s *Server) Run(ctx context.Context) error {
	// Create a separate context for workers that we can cancel independently
	// after the HTTP server has finished draining existing requests.
	workerCtx, stopWorkers := context.WithCancel(context.Background())
	defer stopWorkers()

	// Start background workers
	workers := []worker.Worker{
		worker.NewHitLimitResetWorker(s.valkey, s.logger),
		worker.NewCacheWarmupWorker(s.valkey, s.logger),
		worker.NewSessionJanitorWorker(s.logger),
	}
	if s.chWriter != nil {
		workers = append(workers, workerFunc("click-writer", s.chWriter.Run))
	}

	mgr := worker.NewManager(s.logger, workers...)
	mgr.StartAll(workerCtx)

	// Start HTTP server
	errCh := make(chan error, 1)
	go func() {
		s.logger.Info("ZAI TDS listening", zap.String("addr", s.cfg.Addr()))
		if err := s.http.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			errCh <- err
		}
		close(errCh)
	}()

	select {
	case <-ctx.Done():
		s.logger.Info("shutdown signal received")
	case err := <-errCh:
		return err
	}

	// Step 1: Graceful HTTP shutdown (stop accepting new requests, wait for active ones to finish)
	shutdownCtx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	s.logger.Info("shutting down HTTP server")
	if err := s.http.Shutdown(shutdownCtx); err != nil {
		s.logger.Error("server shutdown error", zap.Error(err))
	}

	// Step 2: Signal workers to stop (now that no new clicks are coming from HTTP handlers)
	s.logger.Info("stopping background workers")
	stopWorkers()

	// Step 3: Wait for all workers (including click-writer drain/flush) to finish
	mgr.Wait()
	s.logger.Info("all workers stopped")

	// Close dependencies
	if s.db != nil {
		s.db.Close()
		s.logger.Info("PostgreSQL pool closed")
	}
	if s.valkey != nil {
		s.valkey.Close()
		s.logger.Info("Valkey client closed")
	}
	if s.geo != nil {
		s.geo.Close()
	}

	return nil
}

// workerFunc adapts a func to the Worker interface for anonymous workers.
type workerFuncAdapter struct {
	name string
	fn   func(context.Context) error
}

func workerFunc(name string, fn func(context.Context) error) worker.Worker {
	return &workerFuncAdapter{name: name, fn: fn}
}

func (w *workerFuncAdapter) Name() string              { return w.name }
func (w *workerFuncAdapter) Run(ctx context.Context) error { return w.fn(ctx) }
