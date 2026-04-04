package migrate

import (
	"context"
	"database/sql"
	"fmt"
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

func RunKeitaro(mysqlDSN, pgDSN string, dryRun bool) error {
	if dryRun {
		log.Printf("[DRY RUN] Starting Keitaro to SkyPlix Migration (no changes will be written)...")
	} else {
		log.Printf("Starting Keitaro to SkyPlix Migration...")
	}

	// Connect to MySQL (Keitaro)
	mysqlDB, err := sql.Open("mysql", mysqlDSN)
	if err != nil {
		return fmt.Errorf("mysql connect error: %w", err)
	}
	defer mysqlDB.Close()

	if err := mysqlDB.Ping(); err != nil {
		return fmt.Errorf("mysql ping error: %w", err)
	}
	log.Println("Connected to Keitaro MySQL.")

	// Connect to PostgreSQL (SkyPlix)
	ctx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancel()

	pgPool, err := pgxpool.New(ctx, pgDSN)
	if err != nil {
		return fmt.Errorf("postgres connect error: %w", err)
	}
	defer pgPool.Close()

	if err := pgPool.Ping(ctx); err != nil {
		return fmt.Errorf("postgres ping error: %w", err)
	}
	log.Println("Connected to SkyPlix PostgreSQL.")

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

			newID := uuid.New()
			status := "active"
			if c.State == "disabled" {
				status = "inactive"
			}

			if !dryRun {
				_, err = pgPool.Exec(context.Background(), `
				INSERT INTO campaigns (id, name, alias, state, created_at, updated_at)
				VALUES ($1, $2, $3, $4, NOW(), NOW())
				ON CONFLICT (alias) DO NOTHING
				`, newID, c.Name, c.Alias, status)

				if err != nil {
					log.Printf("Failed to insert campaign %s: %v", c.Name, err)
					continue
				}
			} else {
				log.Printf("[DRY RUN] Would migrate campaign: %s (%s)", c.Name, c.Alias)
			}
			migrated++
		}
		log.Printf("Successfully migrated %d campaigns.", migrated)
	}

	log.Println("Note: Currently only basic campaign metadata is migrated.")
	log.Println("Migration Complete.")
	return nil
}
