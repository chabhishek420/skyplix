package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"

	"github.com/skyplix/zai-tds/internal/model"
)

// LandingRepository handles SQL operations for the landings table.
type LandingRepository struct {
	db *pgxpool.Pool
}

// NewLandingRepository creates a new repository.
func NewLandingRepository(db *pgxpool.Pool) *LandingRepository {
	return &LandingRepository{db: db}
}

// List returns a paginated list of landings.
func (r *LandingRepository) List(ctx context.Context, limit, offset int) ([]model.Landing, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, name, url, state
		FROM landings
		ORDER BY created_at DESC
		LIMIT $1 OFFSET $2
	`, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query landings: %w", err)
	}
	defer rows.Close()

	var landings []model.Landing
	for rows.Next() {
		var l model.Landing
		err := rows.Scan(&l.ID, &l.Name, &l.URL, &l.State)
		if err != nil {
			return nil, fmt.Errorf("scan landing: %w", err)
		}
		landings = append(landings, l)
	}

	return landings, nil
}

// GetByID retrieves a single landing by uuid.
func (r *LandingRepository) GetByID(ctx context.Context, id uuid.UUID) (*model.Landing, error) {
	var l model.Landing
	err := r.db.QueryRow(ctx, `
		SELECT id, name, url, state
		FROM landings
		WHERE id = $1
	`, id).Scan(&l.ID, &l.Name, &l.URL, &l.State)
	if err != nil {
		return nil, fmt.Errorf("get landing: %w", err)
	}
	return &l, nil
}

// Create inserts a new landing.
func (r *LandingRepository) Create(ctx context.Context, l *model.Landing) error {
	if l.ID == uuid.Nil {
		l.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO landings (id, name, url, state)
		VALUES ($1, $2, $3, $4)
		RETURNING id
	`, l.ID, l.Name, l.URL, l.State).Scan(&l.ID)
}

// Update modifies an existing landing.
func (r *LandingRepository) Update(ctx context.Context, l *model.Landing) error {
	_, err := r.db.Exec(ctx, `
		UPDATE landings
		SET name = $2, url = $3, state = $4, updated_at = NOW()
		WHERE id = $1
	`, l.ID, l.Name, l.URL, l.State)
	return err
}

// Delete archives or deletes a landing.
func (r *LandingRepository) Delete(ctx context.Context, id uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM landings WHERE id = $1", id)
	return err
}
