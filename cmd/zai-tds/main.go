package main

import (
	"context"
	"fmt"
	"log"
	"os"
	"os/signal"
	"syscall"

	"github.com/spf13/cobra"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/config"
	"github.com/skyplix/zai-tds/internal/server"
)

var (
	version    = "1.0.0"
	configPath string
)

func main() {
	rootCmd := &cobra.Command{
		Use:   "zai-tds",
		Short: "SkyPlix TDS — High-performance Traffic Distribution System",
	}

	rootCmd.PersistentFlags().StringVarP(&configPath, "config", "c", "config.yaml", "Path to configuration file")

	serveCmd := &cobra.Command{
		Use:   "serve",
		Short: "Start the TDS server",
		Run:   runServe,
	}

	migrateCmd := &cobra.Command{
		Use:   "migrate",
		Short: "Database migration tools",
	}

	migrateKeitaroCmd := &cobra.Command{
		Use:   "keitaro",
		Short: "Migrate metadata from Keitaro (MySQL)",
		Run:   runMigrateKeitaro,
	}

	versionCmd := &cobra.Command{
		Use:   "version",
		Short: "Print the version number of SkyPlix TDS",
		Run: func(cmd *cobra.Command, args []string) {
			fmt.Printf("SkyPlix TDS v%s\n", version)
		},
	}

	migrateCmd.AddCommand(migrateKeitaroCmd)
	rootCmd.AddCommand(serveCmd, migrateCmd, versionCmd)

	if err := rootCmd.Execute(); err != nil {
		fmt.Fprintf(os.Stderr, "Error: %v\n", err)
		os.Exit(1)
	}
}

func runMigrateKeitaro(cmd *cobra.Command, args []string) {
	fmt.Println("Migrate Keitaro command not fully implemented in CLI wrapper yet.")
	fmt.Println("Use 'go run scripts/migrate_keitaro.go' for now.")
}

func runServe(cmd *cobra.Command, args []string) {
	// Load configuration
	if v := os.Getenv("CONFIG_PATH"); v != "" {
		configPath = v
	}

	cfg, err := config.Load(configPath)
	if err != nil {
		log.Fatalf("FATAL: config error: %v", err)
	}

	// Initialize logger
	logger, err := newLogger(cfg.System.Debug)
	if err != nil {
		log.Fatalf("FATAL: logger init: %v", err)
	}
	defer logger.Sync()

	logger.Info("SkyPlix TDS starting",
		zap.String("version", version),
		zap.String("addr", cfg.Addr()),
		zap.Bool("debug", cfg.System.Debug),
	)

	// Log configuration warnings
	for _, warning := range cfg.Warnings() {
		logger.Warn("config warning", zap.String("issue", warning))
	}

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

	logger.Info("SkyPlix TDS shutdown complete")
}

func newLogger(debug bool) (*zap.Logger, error) {
	if debug {
		return zap.NewDevelopment()
	}
	return zap.NewProduction()
}
