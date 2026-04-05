package main

import (
	"context"
	"flag"
	"fmt"
	"log"
	"os"
	"path/filepath"
	"sort"
	"strings"

	"github.com/ClickHouse/clickhouse-go/v2"
)

func main() {
	addr := flag.String("addr", "127.0.0.1:9000", "ClickHouse address")
	db := flag.String("db", "zai_analytics", "ClickHouse database")
	dir := flag.String("dir", "db/clickhouse/migrations", "Migrations directory")
	flag.Parse()

	conn, err := clickhouse.Open(&clickhouse.Options{
		Addr: []string{*addr},
		Auth: clickhouse.Auth{
			Database: "default",
			Username: "default",
			Password: "",
		},
	})
	if err != nil {
		log.Fatalf("ClickHouse connect error: %v", err)
	}

	ctx := context.Background()
	if err := conn.Exec(ctx, fmt.Sprintf("CREATE DATABASE IF NOT EXISTS %s", *db)); err != nil {
		log.Fatalf("Create database error: %v", err)
	}

	// Simple migrations tracking table
	if err := conn.Exec(ctx, fmt.Sprintf("CREATE TABLE IF NOT EXISTS %s.schema_migrations (version String, applied_at DateTime DEFAULT now()) ENGINE = MergeTree() ORDER BY version", *db)); err != nil {
		log.Fatalf("Create migrations table error: %v", err)
	}

	files, err := os.ReadDir(*dir)
	if err != nil {
		log.Fatalf("Read migrations dir error: %v", err)
	}

	var migrations []string
	for _, f := range files {
		if !f.IsDir() && strings.HasSuffix(f.Name(), ".sql") {
			migrations = append(migrations, f.Name())
		}
	}
	sort.Strings(migrations)

	for _, m := range migrations {
		var count uint64
		row := conn.QueryRow(ctx, fmt.Sprintf("SELECT count(*) FROM %s.schema_migrations WHERE version = ?", *db), m)
		if err := row.Scan(&count); err != nil {
			log.Fatalf("Check migration error: %v", err)
		}

		if count > 0 {
			fmt.Printf("Skipping applied migration: %s\n", m)
			continue
		}

		fmt.Printf("Applying migration: %s\n", m)
		content, err := os.ReadFile(filepath.Join(*dir, m))
		if err != nil {
			log.Fatalf("Read migration file error: %v", err)
		}

		// Split by semicolon for execution
		queries := strings.Split(string(content), ";")
		for _, q := range queries {
			q = strings.TrimSpace(q)
			if q == "" {
				continue
			}
			if err := conn.Exec(ctx, q); err != nil {
				log.Fatalf("Execute migration %s error: %v\nQuery: %s", m, err, q)
			}
		}

		if err := conn.Exec(ctx, fmt.Sprintf("INSERT INTO %s.schema_migrations (version) VALUES (?)", *db), m); err != nil {
			log.Fatalf("Log migration error: %v", err)
		}
		fmt.Printf("Successfully applied %s\n", m)
	}

	fmt.Println("All ClickHouse migrations up to date.")
}
