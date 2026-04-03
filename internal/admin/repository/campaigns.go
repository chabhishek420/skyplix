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

// List returns a paginated list of campaigns.
func (r *CampaignRepository) List(ctx context.Context, limit, offset int) ([]model.Campaign, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id
		FROM campaigns
		ORDER BY created_at DESC
		LIMIT $1 OFFSET $2
	`, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query campaigns: %w", err)
	}
	defer rows.Close()

	var campaigns []model.Campaign
	for rows.Next() {
		var c model.Campaign
		err := rows.Scan(
			&c.ID, &c.Alias, &c.Name, &c.Type,
			&c.BindVisitors, &c.State, &c.TrafficSourceID, &c.DefaultStreamID,
		)
		if err != nil {
			return nil, fmt.Errorf("scan campaign: %w", err)
		}
		campaigns = append(campaigns, c)
	}

	return campaigns, nil
}

// GetByID retrieves a single campaign by uuid.
func (r *CampaignRepository) GetByID(ctx context.Context, id uuid.UUID) (*model.Campaign, error) {
	var c model.Campaign
	err := r.db.QueryRow(ctx, `
		SELECT id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id
		FROM campaigns
		WHERE id = $1
	`, id).Scan(
		&c.ID, &c.Alias, &c.Name, &c.Type,
		&c.BindVisitors, &c.State, &c.TrafficSourceID, &c.DefaultStreamID,
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
		INSERT INTO campaigns (id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
		RETURNING id
	`, c.ID, c.Alias, c.Name, c.Type, c.BindVisitors, c.State, c.TrafficSourceID, c.DefaultStreamID).Scan(&c.ID)
}

// Update modifies an existing campaign.
func (r *CampaignRepository) Update(ctx context.Context, c *model.Campaign) error {
	_, err := r.db.Exec(ctx, `
		UPDATE campaigns
		SET alias = $2, name = $3, type = $4, bind_visitors = $5, state = $6, 
		    traffic_source_id = $7, default_stream_id = $8, updated_at = NOW()
		WHERE id = $1
	`, c.ID, c.Alias, c.Name, c.Type, c.BindVisitors, c.State, c.TrafficSourceID, c.DefaultStreamID)
	return err
}

// Delete archives or deletes a campaign.
func (r *CampaignRepository) Delete(ctx context.Context, id uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM campaigns WHERE id = $1", id)
	return err
}

// Clone duplicates a campaign with a new name and alias.
func (r *CampaignRepository) Clone(ctx context.Context, id uuid.UUID, newID uuid.UUID, newName, newAlias string) (*model.Campaign, error) {
	var c model.Campaign
	err := r.db.QueryRow(ctx, `
		INSERT INTO campaigns (id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id)
		SELECT $2, $3, $4, type, bind_visitors, state, traffic_source_id, default_stream_id
		FROM campaigns
		WHERE id = $1
		RETURNING id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id
	`, id, newID, newAlias, newName).Scan(
		&c.ID, &c.Alias, &c.Name, &c.Type,
		&c.BindVisitors, &c.State, &c.TrafficSourceID, &c.DefaultStreamID,
	)
	if err != nil {
		return nil, fmt.Errorf("clone campaign: %w", err)
	}
	return &c, nil
}

// SetState updates the campaign state (active, disabled, archived).
func (r *CampaignRepository) SetState(ctx context.Context, id uuid.UUID, state string) error {
	_, err := r.db.Exec(ctx, "UPDATE campaigns SET state = $1, updated_at = NOW() WHERE id = $2", state, id)
	return err
}
