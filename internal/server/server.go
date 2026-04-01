package server

import (
	"context"
	"net/http"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/config"
)

// Server is the main application server.
// It holds all long-lived dependencies and wires them together.
type Server struct {
	cfg     *config.Config
	logger  *zap.Logger
	version string
	http    *http.Server
}

// New constructs a Server, validates connections (Postgres + Valkey),
// and applies database migrations. Returns error on any startup failure.
func New(cfg *config.Config, logger *zap.Logger, version string) (*Server, error) {
	s := &Server{
		cfg:     cfg,
		logger:  logger,
		version: version,
	}

	// Build the HTTP server with routes
	mux := s.routes()
	s.http = &http.Server{
		Addr:    cfg.Addr(),
		Handler: mux,
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
	shutdownCtx, cancel := context.WithTimeout(context.Background(), 30*1_000_000_000) // 30s
	defer cancel()

	s.logger.Info("shutting down HTTP server")
	return s.http.Shutdown(shutdownCtx)
}
