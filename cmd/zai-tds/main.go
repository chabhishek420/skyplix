package main

import (
	"context"
	"fmt"
	"log"
	"os"
	"os/signal"
	"path/filepath"
	"sort"
	"strings"
	"syscall"

	"github.com/ClickHouse/clickhouse-go/v2"
	"github.com/spf13/cobra"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/config"
	"github.com/skyplix/zai-tds/internal/server"
)

const version = "1.0.0"

var cfgPath string

func main() {
	rootCmd := &cobra.Command{
		Use:   "skyplix",
		Short: "SkyPlix TDS - High Performance Traffic Distribution System",
	}

	rootCmd.PersistentFlags().StringVarP(&cfgPath, "config", "c", "config.yaml", "path to config file")

	// Serve command
	serveCmd := &cobra.Command{
		Use:   "serve",
		Short: "Start the TDS server",
		Run:   runServe,
	}

	// Migrate command
	migrateCmd := &cobra.Command{
		Use:   "migrate",
		Short: "Run database migrations",
	}

	migrateCHCmd := &cobra.Command{
		Use:   "clickhouse",
		Short: "Run ClickHouse migrations",
		Run:   runMigrateCH,
	}
	migrateCmd.AddCommand(migrateCHCmd)

	rootCmd.AddCommand(serveCmd, migrateCmd)

	if err := rootCmd.Execute(); err != nil {
		fmt.Println(err)
		os.Exit(1)
	}
}

func runServe(cmd *cobra.Command, args []string) {
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

func runMigrateCH(cmd *cobra.Command, args []string) {
	cfg, err := config.Load(cfgPath)
	if err != nil {
		log.Fatalf("failed to load config: %v", err)
	}

	if cfg.ClickHouse.Addr == "" {
		log.Println("ClickHouse address not configured, skipping")
		return
	}

	conn, err := clickhouse.Open(&clickhouse.Options{
		Addr: []string{cfg.ClickHouse.Addr},
		Auth: clickhouse.Auth{
			Database: cfg.ClickHouse.Database,
			Username: cfg.ClickHouse.Username,
			Password: cfg.ClickHouse.Password,
		},
	})
	if err != nil {
		log.Fatalf("failed to connect to ClickHouse: %v", err)
	}
	defer conn.Close()

	ctx := context.Background()

	// Create migrations table
	err = conn.Exec(ctx, `CREATE TABLE IF NOT EXISTS migrations (version UInt32, name String, applied_at DateTime DEFAULT now()) ENGINE = MergeTree() ORDER BY version`)
	if err != nil {
		log.Fatalf("failed to create migrations table: %v", err)
	}

	rows, err := conn.Query(ctx, "SELECT version FROM migrations")
	if err != nil {
		log.Fatalf("failed to query applied migrations: %v", err)
	}
	defer rows.Close()

	applied := make(map[uint32]bool)
	for rows.Next() {
		var v uint32
		if err := rows.Scan(&v); err != nil {
			log.Fatalf("scan error: %v", err)
		}
		applied[v] = true
	}

	migrationDir := "db/clickhouse/migrations"
	files, _ := os.ReadDir(migrationDir)

	var toApply []string
	for _, f := range files {
		if !strings.HasSuffix(f.Name(), ".sql") {
			continue
		}
		var version uint32
		fmt.Sscanf(f.Name(), "%d", &version)
		if !applied[version] {
			toApply = append(toApply, f.Name())
		}
	}

	sort.Strings(toApply)

	for _, name := range toApply {
		log.Printf("Applying: %s", name)
		content, _ := os.ReadFile(filepath.Join(migrationDir, name))
		statements := strings.Split(string(content), ";")
		for _, stmt := range statements {
			stmt = strings.TrimSpace(stmt)
			if stmt == "" {
				continue
			}
			if err := conn.Exec(ctx, stmt); err != nil {
				log.Fatalf("failed in %s: %v\nStmt: %s", name, err, stmt)
			}
		}
		var version uint32
		fmt.Sscanf(name, "%d", &version)
		conn.Exec(ctx, "INSERT INTO migrations (version, name) VALUES (?, ?)", version, name)
	}
	log.Println("Migrations complete")
}

func newLogger(debug bool) (*zap.Logger, error) {
	if debug {
		return zap.NewDevelopment()
	}
	return zap.NewProduction()
}
