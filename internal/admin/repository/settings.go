package repository

import (
	"context"
	"fmt"

	"github.com/jackc/pgx/v5"
)

// SettingsRepository handles SQL operations for the settings table.
type SettingsRepository struct {
	db DB
}

// NewSettingsRepository creates a new repository.
func NewSettingsRepository(db DB) *SettingsRepository {
	return &SettingsRepository{db: db}
}

// GetAll returns all settings as a key-value map.
func (r *SettingsRepository) GetAll(ctx context.Context) (map[string]string, error) {
	rows, err := r.db.Query(ctx, "SELECT key, value FROM settings")
	if err != nil {
		return nil, fmt.Errorf("query settings: %w", err)
	}
	defer rows.Close()

	settings := make(map[string]string)
	for rows.Next() {
		var k, v string
		if err := rows.Scan(&k, &v); err != nil {
			return nil, fmt.Errorf("scan setting: %w", err)
		}
		settings[k] = v
	}

	return settings, nil
}

// Get returns a single setting value by key.
func (r *SettingsRepository) Get(ctx context.Context, key string) (string, error) {
	var value string
	err := r.db.QueryRow(ctx, "SELECT value FROM settings WHERE key = $1", key).Scan(&value)
	if err != nil {
		if err == pgx.ErrNoRows {
			return "", nil
		}
		return "", fmt.Errorf("get setting %q: %w", key, err)
	}
	return value, nil
}

// BulkUpsert updates or inserts multiple settings.
func (r *SettingsRepository) BulkUpsert(ctx context.Context, settings map[string]string) error {
	batch := &pgx.Batch{}

	for k, v := range settings {
		batch.Queue(`
			INSERT INTO settings (key, value, updated_at) 
			VALUES ($1, $2, NOW())
			ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = NOW()
		`, k, v)
	}

	br := r.db.SendBatch(ctx, batch)
	defer br.Close()

	for i := 0; i < len(settings); i++ {
		_, err := br.Exec()
		if err != nil {
			return fmt.Errorf("batch upsert settings: %w", err)
		}
	}

	return nil
}
