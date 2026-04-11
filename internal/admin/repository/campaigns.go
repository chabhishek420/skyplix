package repository

import (
	"context"
	"fmt"
	"strings"
	"time"

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
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	rows, err := r.db.Query(ctx, `
		SELECT id, alias, name, type, bind_visitors,
		       is_optimization_enabled, optimization_metric, optimization_period_hours,
		       state, traffic_source_id, default_stream_id
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
			&c.BindVisitors, &c.IsOptimizationEnabled, &c.OptimizationMetric, &c.OptimizationPeriodHours,
			&c.State, &c.TrafficSourceID, &c.DefaultStreamID,
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
	ctx, cancel := context.WithTimeout(ctx, 3*time.Second)
	defer cancel()

	var c model.Campaign
	err := r.db.QueryRow(ctx, `
		SELECT id, alias, name, type, bind_visitors,
		       is_optimization_enabled, optimization_metric, optimization_period_hours,
		       state, traffic_source_id, default_stream_id
		FROM campaigns
		WHERE id = $1
	`, id).Scan(
		&c.ID, &c.Alias, &c.Name, &c.Type,
		&c.BindVisitors, &c.IsOptimizationEnabled, &c.OptimizationMetric, &c.OptimizationPeriodHours,
		&c.State, &c.TrafficSourceID, &c.DefaultStreamID,
	)
	if err != nil {
		return nil, fmt.Errorf("get campaign: %w", err)
	}
	return &c, nil
}

// Create inserts a new campaign.
func (r *CampaignRepository) Create(ctx context.Context, c *model.Campaign) error {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	if c.ID == uuid.Nil {
		c.ID = uuid.New()
	}
	applyOptimizationDefaults(c)

	return r.db.QueryRow(ctx, `
		INSERT INTO campaigns (
			id, alias, name, type, bind_visitors,
			is_optimization_enabled, optimization_metric, optimization_period_hours,
			state, traffic_source_id, default_stream_id
		)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)
		RETURNING id
	`,
		c.ID, c.Alias, c.Name, c.Type, c.BindVisitors,
		c.IsOptimizationEnabled, c.OptimizationMetric, c.OptimizationPeriodHours,
		c.State, c.TrafficSourceID, c.DefaultStreamID,
	).Scan(&c.ID)
}

// Update modifies an existing campaign.
func (r *CampaignRepository) Update(ctx context.Context, c *model.Campaign) error {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	applyOptimizationDefaults(c)

	_, err := r.db.Exec(ctx, `
		UPDATE campaigns
		SET alias = $2, name = $3, type = $4, bind_visitors = $5,
		    is_optimization_enabled = $6, optimization_metric = $7, optimization_period_hours = $8,
		    state = $9, traffic_source_id = $10, default_stream_id = $11, updated_at = NOW()
		WHERE id = $1
	`,
		c.ID, c.Alias, c.Name, c.Type, c.BindVisitors,
		c.IsOptimizationEnabled, c.OptimizationMetric, c.OptimizationPeriodHours,
		c.State, c.TrafficSourceID, c.DefaultStreamID,
	)
	return err
}

// Delete archives or deletes a campaign.
func (r *CampaignRepository) Delete(ctx context.Context, id uuid.UUID) error {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	_, err := r.db.Exec(ctx, "DELETE FROM campaigns WHERE id = $1", id)
	return err
}

// Clone duplicates a campaign with a new name and alias.
func (r *CampaignRepository) Clone(ctx context.Context, id uuid.UUID, newID uuid.UUID, newName, newAlias string) (*model.Campaign, error) {
	var c model.Campaign
	err := r.db.QueryRow(ctx, `
		INSERT INTO campaigns (
			id, alias, name, type, bind_visitors,
			is_optimization_enabled, optimization_metric, optimization_period_hours,
			state, traffic_source_id, default_stream_id
		)
		SELECT
			$2, $3, $4, type, bind_visitors,
			is_optimization_enabled, optimization_metric, optimization_period_hours,
			state, traffic_source_id, default_stream_id
		FROM campaigns
		WHERE id = $1
		RETURNING id, alias, name, type, bind_visitors,
		          is_optimization_enabled, optimization_metric, optimization_period_hours,
		          state, traffic_source_id, default_stream_id
	`, id, newID, newAlias, newName).Scan(
		&c.ID, &c.Alias, &c.Name, &c.Type,
		&c.BindVisitors, &c.IsOptimizationEnabled, &c.OptimizationMetric, &c.OptimizationPeriodHours,
		&c.State, &c.TrafficSourceID, &c.DefaultStreamID,
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

func applyOptimizationDefaults(c *model.Campaign) {
	if c == nil {
		return
	}
	if strings.TrimSpace(c.OptimizationMetric) == "" {
		c.OptimizationMetric = "CR"
	}
	if c.OptimizationPeriodHours == 0 {
		c.OptimizationPeriodHours = 24
	}
}
