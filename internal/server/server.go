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
)

// Server is the main application server.
// It holds all long-lived dependencies and wires them together.
type Server struct {
	cfg     *config.Config
	logger  *zap.Logger
	version string
	http    *http.Server
	db      *pgxpool.Pool
	valkey  *redis.Client
	geo     *geo.Resolver
	device  *device.Detector
}

// New constructs a Server, connects to Postgres + Valkey,
// and wires up the HTTP router with the full pipeline.
func New(cfg *config.Config, logger *zap.Logger, version string) (*Server, error) {
	s := &Server{
		cfg:     cfg,
		logger:  logger,
		version: version,
		device:  device.New(),
	}

	// Connect to PostgreSQL
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	db, err := pgxpool.New(ctx, cfg.Postgres.DSN)
	if err != nil {
		return nil, fmt.Errorf("postgres connect: %w", err)
	}
	if err := db.Ping(ctx); err != nil {
		return nil, fmt.Errorf("postgres ping: %w", err)
	}
	s.db = db
	logger.Info("PostgreSQL connected")

	// Connect to Valkey
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

	// Initialize GeoIP resolver (graceful — paths may be empty in dev)
	geoResolver, err := geo.New(cfg.GeoIP.CountryDB, cfg.GeoIP.CityDB, logger)
	if err != nil {
		return nil, fmt.Errorf("geoip init: %w", err)
	}
	s.geo = geoResolver

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

// Run starts the HTTP server and blocks until ctx is cancelled.
// Performs graceful shutdown with a 30-second timeout.
func (s *Server) Run(ctx context.Context) error {
	// Start HTTP server in background
	errCh := make(chan error, 1)
	go func() {
		s.logger.Info("ZAI TDS listening", zap.String("addr", s.cfg.Addr()))
		if err := s.http.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			errCh <- err
		}
		close(errCh)
	}()

	// Wait for context cancellation (OS signal) or server error
	select {
	case <-ctx.Done():
		s.logger.Info("shutdown signal received")
	case err := <-errCh:
		return err
	}

	// Graceful shutdown — 30s timeout
	shutdownCtx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	s.logger.Info("shutting down HTTP server")
	if err := s.http.Shutdown(shutdownCtx); err != nil {
		s.logger.Error("shutdown error", zap.Error(err))
	}

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
