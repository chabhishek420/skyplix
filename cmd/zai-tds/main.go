package main

import (
	"context"
	"fmt"
	"os"
	"os/signal"
	"syscall"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/config"
	"github.com/skyplix/zai-tds/internal/server"
)

const version = "0.1.0"

func main() {
	// Load configuration
	cfgPath := "config.yaml"
	if v := os.Getenv("CONFIG_PATH"); v != "" {
		cfgPath = v
	}

	cfg, err := config.Load(cfgPath)
	if err != nil {
		fmt.Fprintf(os.Stderr, "FATAL: config error: %v\n", err)
		os.Exit(1)
	}

	// Initialize logger
	logger, err := newLogger(cfg.System.Debug)
	if err != nil {
		fmt.Fprintf(os.Stderr, "FATAL: logger init: %v\n", err)
		os.Exit(1)
	}
	defer logger.Sync() //nolint:errcheck

	logger.Info("ZAI TDS starting",
		zap.String("version", version),
		zap.String("addr", cfg.Addr()),
		zap.Bool("debug", cfg.System.Debug),
	)

	// Context with OS signal cancellation for graceful shutdown
	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	// Build and run server
	srv, err := server.New(cfg, logger, version)
	if err != nil {
		logger.Fatal("server init failed", zap.Error(err))
	}

	if err := srv.Run(ctx); err != nil {
		logger.Error("server exited with error", zap.Error(err))
		os.Exit(1)
	}

	logger.Info("ZAI TDS shutdown complete")
}

func newLogger(debug bool) (*zap.Logger, error) {
	if debug {
		return zap.NewDevelopment()
	}
	return zap.NewProduction()
}
