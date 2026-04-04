package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// SourceRepository handles SQL operations for the traffic_sources table.
type SourceRepository struct {
	db DB
}

// NewSourceRepository creates a new repository.
func NewSourceRepository(db DB) *SourceRepository {
	return &SourceRepository{db: db}
}

// List returns a paginated list of traffic sources for a specific workspace.
func (r *SourceRepository) List(ctx context.Context, workspaceID uuid.UUID, limit, offset int) ([]model.TrafficSource, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, workspace_id, name, postback_url, params, state, created_at, updated_at
		FROM traffic_sources
		WHERE workspace_id = $1
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`, workspaceID, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query traffic sources: %w", err)
	}
	defer rows.Close()

	var sources []model.TrafficSource
	for rows.Next() {
		var s model.TrafficSource
		err := rows.Scan(&s.ID, &s.WorkspaceID, &s.Name, &s.PostbackURL, &s.Params, &s.State, &s.CreatedAt, &s.UpdatedAt)
		if err != nil {
			return nil, fmt.Errorf("scan traffic source: %w", err)
		}
		sources = append(sources, s)
	}

	return sources, nil
}

// GetByID retrieves a single traffic source by uuid and workspace.
func (r *SourceRepository) GetByID(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) (*model.TrafficSource, error) {
	var s model.TrafficSource
	err := r.db.QueryRow(ctx, `
		SELECT id, workspace_id, name, postback_url, params, state, created_at, updated_at
		FROM traffic_sources
		WHERE id = $1 AND workspace_id = $2
	`, id, workspaceID).Scan(&s.ID, &s.WorkspaceID, &s.Name, &s.PostbackURL, &s.Params, &s.State, &s.CreatedAt, &s.UpdatedAt)
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
		INSERT INTO traffic_sources (id, workspace_id, name, postback_url, params, state)
		VALUES ($1, $2, $3, $4, $5, $6)
		RETURNING id
	`, s.ID, s.WorkspaceID, s.Name, s.PostbackURL, s.Params, s.State).Scan(&s.ID)
}

// Update modifies an existing traffic source within a workspace.
func (r *SourceRepository) Update(ctx context.Context, s *model.TrafficSource) error {
	_, err := r.db.Exec(ctx, `
		UPDATE traffic_sources
		SET name = $3, postback_url = $4, params = $5, state = $6, updated_at = NOW()
		WHERE id = $1 AND workspace_id = $2
	`, s.ID, s.WorkspaceID, s.Name, s.PostbackURL, s.Params, s.State)
	return err
}

// Delete archives or deletes a traffic source within a workspace.
func (r *SourceRepository) Delete(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM traffic_sources WHERE id = $1 AND workspace_id = $2", id, workspaceID)
	return err
}
