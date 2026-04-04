package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// LandingRepository handles SQL operations for the landings table.
type LandingRepository struct {
	db DB
}

// NewLandingRepository creates a new repository.
func NewLandingRepository(db DB) *LandingRepository {
	return &LandingRepository{db: db}
}

// List returns a paginated list of landings for a specific workspace.
func (r *LandingRepository) List(ctx context.Context, workspaceID uuid.UUID, limit, offset int) ([]model.Landing, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, workspace_id, name, url, state, notes, created_at, updated_at
		FROM landings
		WHERE workspace_id = $1
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`, workspaceID, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query landings: %w", err)
	}
	defer rows.Close()

	var landings []model.Landing
	for rows.Next() {
		var l model.Landing
		err := rows.Scan(&l.ID, &l.WorkspaceID, &l.Name, &l.URL, &l.State, &l.Notes, &l.CreatedAt, &l.UpdatedAt)
		if err != nil {
			return nil, fmt.Errorf("scan landing: %w", err)
		}
		landings = append(landings, l)
	}

	return landings, nil
}

// GetByID retrieves a single landing by uuid and workspace.
func (r *LandingRepository) GetByID(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) (*model.Landing, error) {
	var l model.Landing
	err := r.db.QueryRow(ctx, `
		SELECT id, workspace_id, name, url, state, notes, created_at, updated_at
		FROM landings
		WHERE id = $1 AND workspace_id = $2
	`, id, workspaceID).Scan(&l.ID, &l.WorkspaceID, &l.Name, &l.URL, &l.State, &l.Notes, &l.CreatedAt, &l.UpdatedAt)
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
		INSERT INTO landings (id, workspace_id, name, url, state, notes)
		VALUES ($1, $2, $3, $4, $5, $6)
		RETURNING id
	`, l.ID, l.WorkspaceID, l.Name, l.URL, l.State, l.Notes).Scan(&l.ID)
}

// Update modifies an existing landing within a workspace.
func (r *LandingRepository) Update(ctx context.Context, l *model.Landing) error {
	_, err := r.db.Exec(ctx, `
		UPDATE landings
		SET name = $3, url = $4, state = $5, notes = $6, updated_at = NOW()
		WHERE id = $1 AND workspace_id = $2
	`, l.ID, l.WorkspaceID, l.Name, l.URL, l.State, l.Notes)
	return err
}

// Delete archives or deletes a landing within a workspace.
func (r *LandingRepository) Delete(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM landings WHERE id = $1 AND workspace_id = $2", id, workspaceID)
	return err
}
