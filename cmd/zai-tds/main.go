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
	"github.com/skyplix/zai-tds/internal/migrate"
	"github.com/skyplix/zai-tds/internal/server"
)

var (
	version  = "1.0.0"
	cfgPath  string
	mysqlDSN string
	pgDSN    string
)

func main() {
	rootCmd := &cobra.Command{
		Use:   "skyplix",
		Short: "SkyPlix TDS - High Performance Traffic Delivery System",
		Run:   runServer,
	}

	rootCmd.PersistentFlags().StringVarP(&cfgPath, "config", "c", "config.yaml", "path to config file")

	serveCmd := &cobra.Command{
		Use:   "serve",
		Short: "Start the TDS server",
		Run:   runServer,
	}

	migrateCmd := &cobra.Command{
		Use:   "migrate",
		Short: "Migrate data from external sources",
	}

	keitaroCmd := &cobra.Command{
		Use:   "keitaro",
		Short: "Migrate from Keitaro MySQL database",
		Run:   runKeitaroMigration,
	}
	keitaroCmd.Flags().StringVar(&mysqlDSN, "mysql", "", "Keitaro MySQL DSN (user:pass@tcp(host:port)/db)")
	keitaroCmd.Flags().StringVar(&pgDSN, "postgres", "", "SkyPlix Postgres DSN")

	migrateCmd.AddCommand(keitaroCmd)
	rootCmd.AddCommand(serveCmd, migrateCmd, versionCmd)

	if err := rootCmd.Execute(); err != nil {
		fmt.Fprintln(os.Stderr, err)
		os.Exit(1)
	}
}

var versionCmd = &cobra.Command{
	Use:   "version",
	Short: "Print the version number of SkyPlix",
	Run: func(cmd *cobra.Command, args []string) {
		fmt.Printf("SkyPlix TDS v%s\n", version)
	},
}

func runServer(cmd *cobra.Command, args []string) {
	if v := os.Getenv("CONFIG_PATH"); v != "" {
		cfgPath = v
	}

	cfg, err := config.Load(cfgPath)
	if err != nil {
		fmt.Fprintf(os.Stderr, "FATAL: config error: %v\n", err)
		os.Exit(1)
	}

	logger, err := newLogger(cfg.System.Debug)
	if err != nil {
		fmt.Fprintf(os.Stderr, "FATAL: logger init: %v\n", err)
		os.Exit(1)
	}
	defer logger.Sync()

	logger.Info("SkyPlix TDS starting",
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
}

func runKeitaroMigration(cmd *cobra.Command, args []string) {
	if mysqlDSN == "" || pgDSN == "" {
		fmt.Println("Error: --mysql and --postgres flags are required")
		cmd.Usage()
		os.Exit(1)
	}

	if err := migrate.RunKeitaro(mysqlDSN, pgDSN); err != nil {
		fmt.Fprintf(os.Stderr, "Migration failed: %v\n", err)
		os.Exit(1)
	}
}

func newLogger(debug bool) (*zap.Logger, error) {
	if debug {
		return zap.NewDevelopment()
	}
	return zap.NewProduction()
}
