package main

import (
	"context"
	"fmt"
	"os"
	"os/signal"
	"syscall"

	"github.com/spf13/cobra"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/config"
	"github.com/skyplix/zai-tds/internal/server"
)

const version = "1.0.0"

var (
	cfgPath     string
	debug       bool
	dryRun      bool
	sourceDB    string
	workspaceID string
)

func main() {
	rootCmd := &cobra.Command{
		Use:   "zai-tds",
		Short: "SkyPlix TDS — High-performance Go Tracking System",
	}

	rootCmd.PersistentFlags().StringVarP(&cfgPath, "config", "c", "config.yaml", "path to config file")
	rootCmd.PersistentFlags().BoolVarP(&debug, "debug", "d", false, "enable debug logging")

	rootCmd.AddCommand(serveCmd())
	rootCmd.AddCommand(migrateCmd())

	if err := rootCmd.Execute(); err != nil {
		fmt.Fprintf(os.Stderr, "Error: %v\n", err)
		os.Exit(1)
	}
}

func serveCmd() *cobra.Command {
	return &cobra.Command{
		Use:   "serve",
		Short: "Run the TDS tracking and admin server",
		Run: func(cmd *cobra.Command, args []string) {
			cfg, err := config.Load(cfgPath)
			if err != nil {
				fmt.Fprintf(os.Stderr, "FATAL: config error: %v\n", err)
				os.Exit(1)
			}

			logger, _ := newLogger(cfg.System.Debug || debug)
			defer logger.Sync()

			logger.Info("ZAI TDS starting",
				zap.String("version", version),
				zap.String("addr", cfg.Addr()),
			)

			ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
			defer stop()

			srv, err := server.New(cfg, logger, version)
			if err != nil {
				logger.Fatal("server init failed", zap.Error(err))
			}

			if err := srv.Run(ctx); err != nil {
				logger.Error("server exited with error", zap.Error(err))
				os.Exit(1)
			}
		},
	}
}

func migrateCmd() *cobra.Command {
	cmd := &cobra.Command{
		Use:   "migrate",
		Short: "Manage database migrations",
	}

	keitaroCmd := &cobra.Command{
		Use:   "keitaro",
		Short: "Import data from a Keitaro MySQL database",
		Run: func(cmd *cobra.Command, args []string) {
			fmt.Printf("Starting migration from %s (dry-run: %v)\n", sourceDB, dryRun)
			// Migration logic will be invoked here.
		},
	}

	keitaroCmd.Flags().BoolVar(&dryRun, "dry-run", false, "perform a dry run without persisting changes")
	keitaroCmd.Flags().StringVar(&sourceDB, "source-db", "", "MySQL connection string for Keitaro database")
	keitaroCmd.Flags().StringVar(&workspaceID, "workspace-id", "", "SkyPlix workspace UUID for imported entities")

	cmd.AddCommand(keitaroCmd)
	return cmd
}

func newLogger(debug bool) (*zap.Logger, error) {
	if debug {
		return zap.NewDevelopment()
	}
	return zap.NewProduction()
}
