package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// CampaignGroupRepository handles SQL operations for campaign groups.
type CampaignGroupRepository struct {
	db DB
}

// NewCampaignGroupRepository creates a new repository.
func NewCampaignGroupRepository(db DB) *CampaignGroupRepository {
	return &CampaignGroupRepository{db: db}
}

// List returns all groups in a workspace.
func (r *CampaignGroupRepository) List(ctx context.Context, workspaceID uuid.UUID) ([]model.CampaignGroup, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, workspace_id, name, notes, created_at, updated_at
		FROM campaign_groups
		WHERE workspace_id = $1
		ORDER BY name ASC
	`, workspaceID)
	if err != nil {
		return nil, fmt.Errorf("query campaign groups: %w", err)
	}
	defer rows.Close()

	var groups []model.CampaignGroup
	for rows.Next() {
		var g model.CampaignGroup
		err := rows.Scan(&g.ID, &g.WorkspaceID, &g.Name, &g.Notes, &g.CreatedAt, &g.UpdatedAt)
		if err != nil {
			return nil, fmt.Errorf("scan group: %w", err)
		}
		groups = append(groups, g)
	}

	return groups, nil
}

// GetByID retrieves a group by id and workspace.
func (r *CampaignGroupRepository) GetByID(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) (*model.CampaignGroup, error) {
	var g model.CampaignGroup
	err := r.db.QueryRow(ctx, `
		SELECT id, workspace_id, name, notes, created_at, updated_at
		FROM campaign_groups
		WHERE id = $1 AND workspace_id = $2
	`, id, workspaceID).Scan(&g.ID, &g.WorkspaceID, &g.Name, &g.Notes, &g.CreatedAt, &g.UpdatedAt)
	if err != nil {
		return nil, fmt.Errorf("get group: %w", err)
	}
	return &g, nil
}

// Create inserts a new group.
func (r *CampaignGroupRepository) Create(ctx context.Context, g *model.CampaignGroup) error {
	if g.ID == uuid.Nil {
		g.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO campaign_groups (id, workspace_id, name, notes)
		VALUES ($1, $2, $3, $4)
		RETURNING id
	`, g.ID, g.WorkspaceID, g.Name, g.Notes).Scan(&g.ID)
}

// Update modifies a group.
func (r *CampaignGroupRepository) Update(ctx context.Context, g *model.CampaignGroup) error {
	_, err := r.db.Exec(ctx, `
		UPDATE campaign_groups
		SET name = $3, notes = $4, updated_at = NOW()
		WHERE id = $1 AND workspace_id = $2
	`, g.ID, g.WorkspaceID, g.Name, g.Notes)
	return err
}

// Delete removes a group.
func (r *CampaignGroupRepository) Delete(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM campaign_groups WHERE id = $1 AND workspace_id = $2", id, workspaceID)
	return err
}
