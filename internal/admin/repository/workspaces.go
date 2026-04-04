package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// WorkspaceRepository handles SQL operations for the workspaces table.
type WorkspaceRepository struct {
	db DB
}

// NewWorkspaceRepository creates a new repository.
func NewWorkspaceRepository(db DB) *WorkspaceRepository {
	return &WorkspaceRepository{db: db}
}

// List returns all workspaces a user has access to.
func (r *WorkspaceRepository) ListForUser(ctx context.Context, userID uuid.UUID) ([]model.Workspace, error) {
	rows, err := r.db.Query(ctx, `
		SELECT w.id, w.name, w.owner_id, w.state, w.created_at, w.updated_at
		FROM workspaces w
		JOIN user_workspaces uw ON w.id = uw.workspace_id
		WHERE uw.user_id = $1
		ORDER BY w.name ASC
	`, userID)
	if err != nil {
		return nil, fmt.Errorf("query user workspaces: %w", err)
	}
	defer rows.Close()

	var workspaces []model.Workspace
	for rows.Next() {
		var w model.Workspace
		err := rows.Scan(&w.ID, &w.Name, &w.OwnerID, &w.State, &w.CreatedAt, &w.UpdatedAt)
		if err != nil {
			return nil, fmt.Errorf("scan workspace: %w", err)
		}
		workspaces = append(workspaces, w)
	}

	return workspaces, nil
}

// GetByID retrieves a workspace by id.
func (r *WorkspaceRepository) GetByID(ctx context.Context, id uuid.UUID) (*model.Workspace, error) {
	var w model.Workspace
	err := r.db.QueryRow(ctx, `
		SELECT id, name, owner_id, state, created_at, updated_at
		FROM workspaces
		WHERE id = $1
	`, id).Scan(&w.ID, &w.Name, &w.OwnerID, &w.State, &w.CreatedAt, &w.UpdatedAt)
	if err != nil {
		return nil, fmt.Errorf("get workspace: %w", err)
	}
	return &w, nil
}

// Create inserts a new workspace.
func (r *WorkspaceRepository) Create(ctx context.Context, w *model.Workspace) error {
	if w.ID == uuid.Nil {
		w.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO workspaces (id, name, owner_id, state)
		VALUES ($1, $2, $3, $4)
		RETURNING id
	`, w.ID, w.Name, w.OwnerID, w.State).Scan(&w.ID)
}

// Update modifies a workspace.
func (r *WorkspaceRepository) Update(ctx context.Context, w *model.Workspace) error {
	_, err := r.db.Exec(ctx, `
		UPDATE workspaces
		SET name = $2, state = $3, updated_at = NOW()
		WHERE id = $1
	`, w.ID, w.Name, w.State)
	return err
}

// AddUser adds a user to a workspace.
func (r *WorkspaceRepository) AddUser(ctx context.Context, workspaceID, userID uuid.UUID, role string) error {
	_, err := r.db.Exec(ctx, `
		INSERT INTO user_workspaces (workspace_id, user_id, role)
		VALUES ($1, $2, $3)
		ON CONFLICT (workspace_id, user_id) DO UPDATE SET role = EXCLUDED.role
	`, workspaceID, userID, role)
	return err
}

// RemoveUser removes a user from a workspace.
func (r *WorkspaceRepository) RemoveUser(ctx context.Context, workspaceID, userID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM user_workspaces WHERE workspace_id = $1 AND user_id = $2", workspaceID, userID)
	return err
}
