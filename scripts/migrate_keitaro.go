package main

import (
	"context"
	"database/sql"
	"flag"
	"log"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"
)

type LegacyCampaign struct {
	ID    int
	Name  string
	Alias string
	State string
}

func main() {
	mysqlDSN := flag.String("mysql", "root:password@tcp(127.0.0.1:3306)/keitaro", "MySQL DSN for Keitaro database")
	pgDSN := flag.String("postgres", "postgres://zai:zai_dev_pass@127.0.0.1:5432/zai_tds?sslmode=disable", "Postgres DSN for ZAI TDS")
	flag.Parse()

	log.Println("Starting Keitaro to ZAI TDS Migration...")

	// Connect to MySQL (Keitaro)
	mysqlDB, err := sql.Open("mysql", *mysqlDSN)
	if err != nil {
		log.Fatalf("MySQL connect error: %v", err)
	}
	defer mysqlDB.Close()

	if err := mysqlDB.Ping(); err != nil {
		log.Fatalf("MySQL ping error (is DB accessible?): %v", err)
	}
	log.Println("Connected to Keitaro MySQL.")

	// Connect to PostgreSQL (ZAI TDS)
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	pgPool, err := pgxpool.New(ctx, *pgDSN)
	if err != nil {
		log.Fatalf("Postgres connect error: %v", err)
	}
	defer pgPool.Close()

	if err := pgPool.Ping(ctx); err != nil {
		log.Fatalf("Postgres ping error: %v", err)
	}
	log.Println("Connected to ZAI TDS PostgreSQL.")

	// 1. Migrate Campaigns
	log.Println("Migrating Keitaro campaigns...")
	rows, err := mysqlDB.Query("SELECT id, name, alias, state FROM campaigns WHERE state != 'deleted'")
	if err != nil {
		log.Printf("Warning: Failed to query Keitaro campaigns (table may not exist or differs): %v", err)
	} else {
		defer rows.Close()

		migrated := 0
		for rows.Next() {
			var c LegacyCampaign
			if err := rows.Scan(&c.ID, &c.Name, &c.Alias, &c.State); err != nil {
				log.Printf("Failed to scan row: %v", err)
				continue
			}

			// Generate ZAI standard
			newID := uuid.New()
			status := "active"
			if c.State == "disabled" {
				status = "inactive"
			}

			// Upsert to Postgres
			_, err = pgPool.Exec(context.Background(), `
			INSERT INTO campaigns (id, name, alias, status, default_action_type, default_action_payload, created_at, updated_at)
			VALUES ($1, $2, $3, $4, 'redirect', '{}', NOW(), NOW())
			ON CONFLICT (alias) DO NOTHING
			`, newID, c.Name, c.Alias, status)

			if err != nil {
				log.Printf("Failed to insert campaign %s: %v", c.Name, err)
			} else {
				migrated++
			}
		}
		log.Printf("Successfully migrated %d campaigns.", migrated)
	}

	log.Println("Note: Currently only basic campaign structures are automatically migrated.")
	log.Println("Streams, Landing Pages, and Offers require manual mapping due to diverging condition systems.")
	log.Println("Migration Complete.")
}
