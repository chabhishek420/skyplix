package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"

	"github.com/skyplix/zai-tds/internal/model"
)

// SourceRepository handles SQL operations for the traffic_sources table.
type SourceRepository struct {
	db *pgxpool.Pool
}

// NewSourceRepository creates a new repository.
func NewSourceRepository(db *pgxpool.Pool) *SourceRepository {
	return &SourceRepository{db: db}
}

// List returns a paginated list of traffic sources.
func (r *SourceRepository) List(ctx context.Context, limit, offset int) ([]model.TrafficSource, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, name, postback_url, params, state
		FROM traffic_sources
		ORDER BY created_at DESC
		LIMIT $1 OFFSET $2
	`, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query traffic sources: %w", err)
	}
	defer rows.Close()

	var sources []model.TrafficSource
	for rows.Next() {
		var s model.TrafficSource
		err := rows.Scan(&s.ID, &s.Name, &s.PostbackURL, &s.Params, &s.State)
		if err != nil {
			return nil, fmt.Errorf("scan traffic source: %w", err)
		}
		sources = append(sources, s)
	}

	return sources, nil
}

// GetByID retrieves a single traffic source by uuid.
func (r *SourceRepository) GetByID(ctx context.Context, id uuid.UUID) (*model.TrafficSource, error) {
	var s model.TrafficSource
	err := r.db.QueryRow(ctx, `
		SELECT id, name, postback_url, params, state
		FROM traffic_sources
		WHERE id = $1
	`, id).Scan(&s.ID, &s.Name, &s.PostbackURL, &s.Params, &s.State)
	if err != nil {
		return nil, fmt.Errorf("get traffic source: %w", err)
	}
	return &s, nil
}

// Create inserts a new traffic source.
func (r *SourceRepository) Create(ctx context.Context, s *model.TrafficSource) error {
	if s.ID == uuid.Nil {
		s.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO traffic_sources (id, name, postback_url, params, state)
		VALUES ($1, $2, $3, $4, $5)
		RETURNING id
	`, s.ID, s.Name, s.PostbackURL, s.Params, s.State).Scan(&s.ID)
}

// Update modifies an existing traffic source.
func (r *SourceRepository) Update(ctx context.Context, s *model.TrafficSource) error {
	_, err := r.db.Exec(ctx, `
		UPDATE traffic_sources
		SET name = $2, postback_url = $3, params = $4, state = $5, updated_at = NOW()
		WHERE id = $1
	`, s.ID, s.Name, s.PostbackURL, s.Params, s.State)
	return err
}

// Delete archives or deletes a traffic source.
func (r *SourceRepository) Delete(ctx context.Context, id uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM traffic_sources WHERE id = $1", id)
	return err
}
