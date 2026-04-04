package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// StreamRepository handles SQL operations for the streams table.
type StreamRepository struct {
	db DB
}

// NewStreamRepository creates a new repository.
func NewStreamRepository(db DB) *StreamRepository {
	return &StreamRepository{db: db}
}

// ListByCampaign returns all streams for a specific campaign and workspace.
func (r *StreamRepository) ListByCampaign(ctx context.Context, campaignID uuid.UUID, workspaceID uuid.UUID) ([]model.Stream, error) {
	rows, err := r.db.Query(ctx, `
		SELECT s.id, s.workspace_id, s.campaign_id, s.name, s.type, s.position, s.weight, s.state, s.action_type, s.action_payload, s.filters, s.daily_limit, s.total_limit
		FROM streams s
		JOIN campaigns c ON s.campaign_id = c.id
		WHERE s.campaign_id = $1 AND c.workspace_id = $2
		ORDER BY s.position ASC
	`, campaignID, workspaceID)
	if err != nil {
		return nil, fmt.Errorf("query streams: %w", err)
	}
	defer rows.Close()

	var streams []model.Stream
	for rows.Next() {
		var s model.Stream
		err := rows.Scan(
			&s.ID, &s.WorkspaceID, &s.CampaignID, &s.Name, &s.Type, &s.Position, &s.Weight,
			&s.State, &s.ActionType, &s.ActionPayload, &s.Filters, &s.DailyLimit, &s.TotalLimit,
		)
		if err != nil {
			return nil, fmt.Errorf("scan stream: %w", err)
		}
		streams = append(streams, s)
	}

	return streams, nil
}

// GetByID retrieves a single stream by uuid and workspace.
func (r *StreamRepository) GetByID(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) (*model.Stream, error) {
	var s model.Stream
	err := r.db.QueryRow(ctx, `
		SELECT s.id, s.workspace_id, s.campaign_id, s.name, s.type, s.position, s.weight, s.state, s.action_type, s.action_payload, s.filters, s.daily_limit, s.total_limit
		FROM streams s
		JOIN campaigns c ON s.campaign_id = c.id
		WHERE s.id = $1 AND c.workspace_id = $2
	`, id, workspaceID).Scan(
		&s.ID, &s.WorkspaceID, &s.CampaignID, &s.Name, &s.Type, &s.Position, &s.Weight,
		&s.State, &s.ActionType, &s.ActionPayload, &s.Filters, &s.DailyLimit, &s.TotalLimit,
	)
	if err != nil {
		return nil, fmt.Errorf("get stream: %w", err)
	}
	return &s, nil
}

// Create inserts a new stream.
func (r *StreamRepository) Create(ctx context.Context, s *model.Stream) error {
	if s.ID == uuid.Nil {
		s.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO streams (id, workspace_id, campaign_id, name, type, position, weight, state, action_type, action_payload, filters, daily_limit, total_limit)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13)
		RETURNING id
	`, s.ID, s.WorkspaceID, s.CampaignID, s.Name, s.Type, s.Position, s.Weight, s.State, s.ActionType, s.ActionPayload, s.Filters, s.DailyLimit, s.TotalLimit).Scan(&s.ID)
}

// Update modifies an existing stream within a workspace.
func (r *StreamRepository) Update(ctx context.Context, s *model.Stream) error {
	_, err := r.db.Exec(ctx, `
		UPDATE streams
		SET name = $3, type = $4, position = $5, weight = $6, state = $7,
		    action_type = $8, action_payload = $9, filters = $10,
		    daily_limit = $11, total_limit = $12, updated_at = NOW()
		WHERE id = $1 AND workspace_id = $2
	`, s.ID, s.WorkspaceID, s.Name, s.Type, s.Position, s.Weight, s.State, s.ActionType, s.ActionPayload, s.Filters, s.DailyLimit, s.TotalLimit)
	return err
}

// Delete removes a stream within a workspace.
func (r *StreamRepository) Delete(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM streams WHERE id = $1 AND workspace_id = $2", id, workspaceID)
	return err
}

// Clone duplicates a stream to a new campaign or with a new position within a workspace.
func (r *StreamRepository) Clone(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID, newID uuid.UUID, targetCampaignID uuid.UUID, newName string, newPosition int) (*model.Stream, error) {
	var s model.Stream
	err := r.db.QueryRow(ctx, `
		INSERT INTO streams (id, workspace_id, campaign_id, name, type, position, weight, state, action_type, action_payload, filters, daily_limit, total_limit)
		SELECT $3, workspace_id, $4, $5, type, $6, weight, state, action_type, action_payload, filters, daily_limit, total_limit
		FROM streams
		WHERE id = $1 AND workspace_id = $2
		RETURNING id, workspace_id, campaign_id, name, type, position, weight, state, action_type, action_payload, filters, daily_limit, total_limit
	`, id, workspaceID, newID, targetCampaignID, newName, newPosition).Scan(
		&s.ID, &s.WorkspaceID, &s.CampaignID, &s.Name, &s.Type, &s.Position, &s.Weight,
		&s.State, &s.ActionType, &s.ActionPayload, &s.Filters, &s.DailyLimit, &s.TotalLimit,
	)
	if err != nil {
		return nil, fmt.Errorf("clone stream: %w", err)
	}

	// Clone offers
	_, err = r.db.Exec(ctx, `
		INSERT INTO stream_offers (stream_id, offer_id, weight)
		SELECT $2, offer_id, weight
		FROM stream_offers
		WHERE stream_id = $1
	`, id, newID)
	if err != nil {
		return nil, fmt.Errorf("clone stream offers: %w", err)
	}

	// Clone landings
	_, err = r.db.Exec(ctx, `
		INSERT INTO stream_landings (stream_id, landing_id, weight)
		SELECT $2, landing_id, weight
		FROM stream_landings
		WHERE stream_id = $1
	`, id, newID)
	if err != nil {
		return nil, fmt.Errorf("clone stream landings: %w", err)
	}

	return &s, nil
}

// GetOffers returns all offers associated with a stream and workspace.
func (r *StreamRepository) GetOffers(ctx context.Context, streamID uuid.UUID, workspaceID uuid.UUID) ([]model.WeightedOffer, error) {
	rows, err := r.db.Query(ctx, `
		SELECT o.id, o.workspace_id, o.name, o.url, o.affiliate_network_id, o.payout, o.daily_cap, o.state, o.notes, so.weight
		FROM offers o
		JOIN stream_offers so ON o.id = so.offer_id
		JOIN streams s ON so.stream_id = s.id
		JOIN campaigns c ON s.campaign_id = c.id
		WHERE so.stream_id = $1 AND c.workspace_id = $2
	`, streamID, workspaceID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var offers []model.WeightedOffer
	for rows.Next() {
		var wo model.WeightedOffer
		err := rows.Scan(
			&wo.Offer.ID, &wo.Offer.WorkspaceID, &wo.Offer.Name, &wo.Offer.URL,
			&wo.Offer.AffiliateNetworkID, &wo.Offer.Payout, &wo.Offer.DailyCap,
			&wo.Offer.State, &wo.Offer.Notes, &wo.Weight,
		)
		if err != nil {
			return nil, err
		}
		offers = append(offers, wo)
	}
	return offers, nil
}

// GetLandings returns all landings associated with a stream and workspace.
func (r *StreamRepository) GetLandings(ctx context.Context, streamID uuid.UUID, workspaceID uuid.UUID) ([]model.WeightedLanding, error) {
	rows, err := r.db.Query(ctx, `
		SELECT l.id, l.workspace_id, l.name, l.url, l.state, l.notes, sl.weight
		FROM landings l
		JOIN stream_landings sl ON l.id = sl.landing_id
		JOIN streams s ON sl.stream_id = s.id
		JOIN campaigns c ON s.campaign_id = c.id
		WHERE sl.stream_id = $1 AND c.workspace_id = $2
	`, streamID, workspaceID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var landings []model.WeightedLanding
	for rows.Next() {
		var wl model.WeightedLanding
		err := rows.Scan(
			&wl.Landing.ID, &wl.Landing.WorkspaceID, &wl.Landing.Name,
			&wl.Landing.URL, &wl.Landing.State, &wl.Landing.Notes, &wl.Weight,
		)
		if err != nil {
			return nil, err
		}
		landings = append(landings, wl)
	}
	return landings, nil
}

// SyncOffers replaces all offers for a stream within a workspace context.
func (r *StreamRepository) SyncOffers(ctx context.Context, streamID uuid.UUID, workspaceID uuid.UUID, offers []model.WeightedOffer) error {
	// Verify stream ownership via workspace
	_, err := r.db.Exec(ctx, `
		DELETE FROM stream_offers
		WHERE stream_id IN (
			SELECT s.id FROM streams s
			JOIN campaigns c ON s.campaign_id = c.id
			WHERE s.id = $1 AND c.workspace_id = $2
		)`, streamID, workspaceID)
	if err != nil {
		return err
	}

	for _, wo := range offers {
		_, err = r.db.Exec(ctx, "INSERT INTO stream_offers (stream_id, offer_id, weight) VALUES ($1, $2, $3)", streamID, wo.Offer.ID, wo.Weight)
		if err != nil {
			return err
		}
	}

	return nil
}

// SyncLandings replaces all landings for a stream within a workspace context.
func (r *StreamRepository) SyncLandings(ctx context.Context, streamID uuid.UUID, workspaceID uuid.UUID, landings []model.WeightedLanding) error {
	// Verify stream ownership via workspace
	_, err := r.db.Exec(ctx, `
		DELETE FROM stream_landings
		WHERE stream_id IN (
			SELECT s.id FROM streams s
			JOIN campaigns c ON s.campaign_id = c.id
			WHERE s.id = $1 AND c.workspace_id = $2
		)`, streamID, workspaceID)
	if err != nil {
		return err
	}

	for _, wl := range landings {
		_, err = r.db.Exec(ctx, "INSERT INTO stream_landings (stream_id, landing_id, weight) VALUES ($1, $2, $3)", streamID, wl.Landing.ID, wl.Weight)
		if err != nil {
			return err
		}
	}

	return nil
}
