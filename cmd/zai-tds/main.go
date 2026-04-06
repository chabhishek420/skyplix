package main

import (
	"context"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"path/filepath"
	"sort"
	"strings"
	"syscall"

	"database/sql"

	"github.com/ClickHouse/clickhouse-go/v2"
	_ "github.com/go-sql-driver/mysql"
	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/spf13/cobra"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/config"
	"github.com/skyplix/zai-tds/internal/server"
)

const version = "1.0.0"

var cfgPath string

func main() {
	rootCmd := &cobra.Command{
		Use:   "zai-tds",
		Short: "SkyPlix (ZAI) TDS - High Performance Traffic Distribution System",
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

	migrateKeitaroCmd := &cobra.Command{
		Use:   "keitaro",
		Short: "Migrate data from Keitaro MySQL",
		Run:   runMigrateKeitaro,
	}
	migrateKeitaroCmd.Flags().String("mysql", "", "MySQL DSN for Keitaro (required)")

	migrateCmd.AddCommand(migrateCHCmd, migrateKeitaroCmd)

	// Healthcheck command
	healthCmd := &cobra.Command{
		Use:   "healthcheck",
		Short: "Run a container health check",
		Run:   runHealthcheck,
	}

	rootCmd.AddCommand(serveCmd, migrateCmd, healthCmd)

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

func runMigrateKeitaro(cmd *cobra.Command, args []string) {
	mysqlDSN, _ := cmd.Flags().GetString("mysql")
	if mysqlDSN == "" {
		log.Fatal("--mysql DSN is required")
	}

	cfg, err := config.Load(cfgPath)
	if err != nil {
		log.Fatalf("failed to load config: %v", err)
	}

	log.Println("Starting Keitaro Migration...")

	mysqlDB, err := sql.Open("mysql", mysqlDSN)
	if err != nil {
		log.Fatalf("mysql connect error: %v", err)
	}
	defer mysqlDB.Close()

	ctx := context.Background()
	pgPool, err := pgxpool.New(ctx, cfg.Postgres.DSN)
	if err != nil {
		log.Fatalf("postgres connect error: %v", err)
	}
	defer pgPool.Close()

	rows, err := mysqlDB.Query("SELECT id, name, alias, state FROM campaigns WHERE state != 'deleted'")
	if err != nil {
		log.Fatalf("failed to query Keitaro: %v", err)
	}
	defer rows.Close()

	migrated := 0
	for rows.Next() {
		var id int
		var name, alias, state string
		if err := rows.Scan(&id, &name, &alias, &state); err != nil {
			log.Printf("scan error: %v", err)
			continue
		}

		status := "active"
		if state == "disabled" {
			status = "inactive"
		}

		_, err = pgPool.Exec(ctx, `
			INSERT INTO campaigns (id, name, alias, status, default_action_type, default_action_payload, created_at, updated_at)
			VALUES ($1, $2, $3, $4, 'redirect', '{}', NOW(), NOW())
			ON CONFLICT (alias) DO NOTHING
		`, uuid.New(), name, alias, status)

		if err == nil {
			migrated++
		}
	}
	log.Printf("Successfully migrated %d campaigns", migrated)
}

func runHealthcheck(cmd *cobra.Command, args []string) {
	cfg, err := config.Load(cfgPath)
	if err != nil {
		os.Exit(1)
	}

	url := fmt.Sprintf("http://localhost:%d/api/v1/health", cfg.Server.Port)
	resp, err := http.Get(url)
	if err != nil || resp.StatusCode != http.StatusOK {
		os.Exit(1)
	}
	os.Exit(0)
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
		statements := splitSQLStatements(string(content))
		for _, stmt := range statements {
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

func splitSQLStatements(sql string) []string {
	var statements []string
	var current strings.Builder
	inString := false
	var quoteChar rune
	inLineComment := false
	inBlockComment := false

	chars := []rune(sql)
	for i := 0; i < len(chars); i++ {
		r := chars[i]

		// Handle comments
		if !inString {
			if !inBlockComment && !inLineComment && i < len(chars)-1 && r == '-' && chars[i+1] == '-' {
				inLineComment = true
				i++
				continue
			}
			if inLineComment && r == '\n' {
				inLineComment = false
				continue
			}
			if !inBlockComment && !inLineComment && i < len(chars)-1 && r == '/' && chars[i+1] == '*' {
				inBlockComment = true
				i++
				continue
			}
			if inBlockComment && i < len(chars)-1 && r == '*' && chars[i+1] == '/' {
				inBlockComment = false
				i++
				continue
			}
			if inLineComment || inBlockComment {
				continue
			}
		}

		switch r {
		case '\'', '"', '`':
			if !inString {
				inString = true
				quoteChar = r
			} else if quoteChar == r {
				// Check for escaped quote (very basic check)
				if i > 0 && chars[i-1] != '\\' {
					inString = false
				}
			}
			current.WriteRune(r)
		case ';':
			if !inString {
				stmt := strings.TrimSpace(current.String())
				if stmt != "" {
					statements = append(statements, stmt)
				}
				current.Reset()
			} else {
				current.WriteRune(r)
			}
		default:
			current.WriteRune(r)
		}
	}

	stmt := strings.TrimSpace(current.String())
	if stmt != "" {
		statements = append(statements, stmt)
	}

	return statements
}

func newLogger(debug bool) (*zap.Logger, error) {
	if debug {
		return zap.NewDevelopment()
	}
	return zap.NewProduction()
}
