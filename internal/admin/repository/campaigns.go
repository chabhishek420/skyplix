package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// CampaignRepository handles SQL operations for the campaigns table.
type CampaignRepository struct {
	db DB
}

// NewCampaignRepository creates a new repository.
func NewCampaignRepository(db DB) *CampaignRepository {
	return &CampaignRepository{db: db}
}

// List returns a paginated list of campaigns for a specific workspace.
func (r *CampaignRepository) List(ctx context.Context, workspaceID uuid.UUID, limit, offset int) ([]model.Campaign, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, workspace_id, group_id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id, cost_model, cost_value, notes, tags, created_at, updated_at
		FROM campaigns
		WHERE workspace_id = $1
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`, workspaceID, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query campaigns: %w", err)
	}
	defer rows.Close()

	var campaigns []model.Campaign
	for rows.Next() {
		var c model.Campaign
		err := rows.Scan(
			&c.ID, &c.WorkspaceID, &c.GroupID, &c.Alias, &c.Name, &c.Type,
			&c.BindVisitors, &c.State, &c.TrafficSourceID, &c.DefaultStreamID,
			&c.CostModel, &c.CostValue, &c.Notes, &c.Tags, &c.CreatedAt, &c.UpdatedAt,
		)
		if err != nil {
			return nil, fmt.Errorf("scan campaign: %w", err)
		}
		campaigns = append(campaigns, c)
	}

	return campaigns, nil
}

// GetByID retrieves a single campaign by uuid and workspace.
func (r *CampaignRepository) GetByID(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) (*model.Campaign, error) {
	var c model.Campaign
	err := r.db.QueryRow(ctx, `
		SELECT id, workspace_id, group_id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id, cost_model, cost_value, notes, tags, created_at, updated_at
		FROM campaigns
		WHERE id = $1 AND workspace_id = $2
	`, id, workspaceID).Scan(
		&c.ID, &c.WorkspaceID, &c.GroupID, &c.Alias, &c.Name, &c.Type,
		&c.BindVisitors, &c.State, &c.TrafficSourceID, &c.DefaultStreamID,
		&c.CostModel, &c.CostValue, &c.Notes, &c.Tags, &c.CreatedAt, &c.UpdatedAt,
	)
	if err != nil {
		return nil, fmt.Errorf("get campaign: %w", err)
	}
	return &c, nil
}

// Create inserts a new campaign.
func (r *CampaignRepository) Create(ctx context.Context, c *model.Campaign) error {
	if c.ID == uuid.Nil {
		c.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO campaigns (id, workspace_id, group_id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id, cost_model, cost_value, notes, tags)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
		RETURNING id
	`, c.ID, c.WorkspaceID, c.GroupID, c.Alias, c.Name, c.Type, c.BindVisitors, c.State, c.TrafficSourceID, c.DefaultStreamID, c.CostModel, c.CostValue, c.Notes, c.Tags).Scan(&c.ID)
}

// Update modifies an existing campaign within a workspace.
func (r *CampaignRepository) Update(ctx context.Context, c *model.Campaign) error {
	_, err := r.db.Exec(ctx, `
		UPDATE campaigns
		SET group_id = $3, alias = $4, name = $5, type = $6, bind_visitors = $7, state = $8,
		    traffic_source_id = $9, default_stream_id = $10, cost_model = $11, cost_value = $12,
		    notes = $13, tags = $14, updated_at = NOW()
		WHERE id = $1 AND workspace_id = $2
	`, c.ID, c.WorkspaceID, c.GroupID, c.Alias, c.Name, c.Type, c.BindVisitors, c.State, c.TrafficSourceID, c.DefaultStreamID, c.CostModel, c.CostValue, c.Notes, c.Tags)
	return err
}

// Delete archives or deletes a campaign within a workspace.
func (r *CampaignRepository) Delete(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM campaigns WHERE id = $1 AND workspace_id = $2", id, workspaceID)
	return err
}

// Clone duplicates a campaign with a new name and alias within a workspace.
func (r *CampaignRepository) Clone(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID, newID uuid.UUID, newName, newAlias string) (*model.Campaign, error) {
	var c model.Campaign
	err := r.db.QueryRow(ctx, `
		INSERT INTO campaigns (id, workspace_id, group_id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id, cost_model, cost_value, notes, tags)
		SELECT $3, workspace_id, group_id, $4, $5, type, bind_visitors, state, traffic_source_id, default_stream_id, cost_model, cost_value, notes, tags
		FROM campaigns
		WHERE id = $1 AND workspace_id = $2
		RETURNING id, workspace_id, group_id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id, cost_model, cost_value, notes, tags, created_at, updated_at
	`, id, workspaceID, newID, newAlias, newName).Scan(
		&c.ID, &c.WorkspaceID, &c.GroupID, &c.Alias, &c.Name, &c.Type,
		&c.BindVisitors, &c.State, &c.TrafficSourceID, &c.DefaultStreamID,
		&c.CostModel, &c.CostValue, &c.Notes, &c.Tags, &c.CreatedAt, &c.UpdatedAt,
	)
	if err != nil {
		return nil, fmt.Errorf("clone campaign: %w", err)
	}
	return &c, nil
}

// SetState updates the campaign state (active, disabled, archived) within a workspace.
func (r *CampaignRepository) SetState(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID, state string) error {
	_, err := r.db.Exec(ctx, "UPDATE campaigns SET state = $1, updated_at = NOW() WHERE id = $2 AND workspace_id = $3", state, id, workspaceID)
	return err
}
