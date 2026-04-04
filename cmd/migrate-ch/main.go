package main

import (
	"context"
	"fmt"
	"log"
	"os"
	"path/filepath"
	"sort"
	"strings"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2"
	"github.com/skyplix/zai-tds/internal/config"
)

func main() {
	cfgPath := "config.yaml"
	if len(os.Args) > 1 {
		cfgPath = os.Args[1]
	}

	cfg, err := config.Load(cfgPath)
	if err != nil {
		log.Fatalf("failed to load config: %v", err)
	}

	if cfg.ClickHouse.Addr == "" {
		log.Println("ClickHouse address not configured, skipping migrations")
		return
	}

	conn, err := clickhouse.Open(&clickhouse.Options{
		Addr: []string{cfg.ClickHouse.Addr},
		Auth: clickhouse.Auth{
			Database: cfg.ClickHouse.Database,
			Username: cfg.ClickHouse.Username,
			Password: cfg.ClickHouse.Password,
		},
		DialTimeout: 10 * time.Second,
	})
	if err != nil {
		log.Fatalf("failed to connect to ClickHouse: %v", err)
	}
	defer conn.Close()

	ctx := context.Background()
	if err := conn.Ping(ctx); err != nil {
		log.Fatalf("failed to ping ClickHouse: %v", err)
	}

	// Create migrations table if not exists
	err = conn.Exec(ctx, `
		CREATE TABLE IF NOT EXISTS migrations (
			version UInt32,
			name String,
			applied_at DateTime DEFAULT now()
		) ENGINE = MergeTree() ORDER BY version
	`)
	if err != nil {
		log.Fatalf("failed to create migrations table: %v", err)
	}

	// Get applied migrations
	rows, err := conn.Query(ctx, "SELECT version FROM migrations")
	if err != nil {
		log.Fatalf("failed to query applied migrations: %v", err)
	}
	defer rows.Close()

	applied := make(map[uint32]bool)
	for rows.Next() {
		var v uint32
		if err := rows.Scan(&v); err != nil {
			log.Fatalf("failed to scan migration version: %v", err)
		}
		applied[v] = true
	}

	// Read migration files
	migrationDir := "db/clickhouse/migrations"
	files, err := os.ReadDir(migrationDir)
	if err != nil {
		log.Fatalf("failed to read migrations directory: %v", err)
	}

	var toApply []string
	for _, f := range files {
		if f.IsDir() || !strings.HasSuffix(f.Name(), ".sql") {
			continue
		}

		var version uint32
		_, err := fmt.Sscanf(f.Name(), "%d", &version)
		if err != nil {
			continue // Skip files that don't start with a number
		}

		if !applied[version] {
			toApply = append(toApply, f.Name())
		}
	}

	sort.Strings(toApply)

	for _, name := range toApply {
		log.Printf("Applying migration: %s", name)

		content, err := os.ReadFile(filepath.Join(migrationDir, name))
		if err != nil {
			log.Fatalf("failed to read migration file %s: %v", name, err)
		}

		// Split by semicolon for execution, or execute as a whole if ClickHouse supports it
		// Most ClickHouse drivers prefer executing single statements.
		// However, many migrations contain multiple CREATE TABLE/VIEW statements.
		// clickhouse-go's Exec can handle multiple statements in some configurations but it's safer to split.

		statements := strings.Split(string(content), ";")
		for _, stmt := range statements {
			stmt = strings.TrimSpace(stmt)
			if stmt == "" {
				continue
			}
			if err := conn.Exec(ctx, stmt); err != nil {
				log.Fatalf("failed to execute statement in %s: %v\nStatement: %s", name, err, stmt)
			}
		}

		var version uint32
		fmt.Sscanf(name, "%d", &version)
		err = conn.Exec(ctx, "INSERT INTO migrations (version, name) VALUES (?, ?)", version, name)
		if err != nil {
			log.Fatalf("failed to record migration %s: %v", name, err)
		}
		log.Printf("Successfully applied migration: %s", name)
	}

	log.Println("All migrations applied successfully")
}
