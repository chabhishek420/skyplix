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

// ListByCampaign returns all streams for a specific campaign.
func (r *StreamRepository) ListByCampaign(ctx context.Context, campaignID uuid.UUID) ([]model.Stream, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, campaign_id, name, type, position, weight, state, action_type, action_payload, filters, daily_limit, total_limit
		FROM streams
		WHERE campaign_id = $1
		ORDER BY position ASC
	`, campaignID)
	if err != nil {
		return nil, fmt.Errorf("query streams: %w", err)
	}
	defer rows.Close()

	var streams []model.Stream
	for rows.Next() {
		var s model.Stream
		err := rows.Scan(
			&s.ID, &s.CampaignID, &s.Name, &s.Type, &s.Position, &s.Weight,
			&s.State, &s.ActionType, &s.ActionPayload, &s.Filters, &s.DailyLimit, &s.TotalLimit,
		)
		if err != nil {
			return nil, fmt.Errorf("scan stream: %w", err)
		}
		streams = append(streams, s)
	}

	return streams, nil
}

// GetByID retrieves a single stream by uuid.
func (r *StreamRepository) GetByID(ctx context.Context, id uuid.UUID) (*model.Stream, error) {
	var s model.Stream
	err := r.db.QueryRow(ctx, `
		SELECT id, campaign_id, name, type, position, weight, state, action_type, action_payload, filters, daily_limit, total_limit
		FROM streams
		WHERE id = $1
	`, id).Scan(
		&s.ID, &s.CampaignID, &s.Name, &s.Type, &s.Position, &s.Weight,
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
		INSERT INTO streams (id, campaign_id, name, type, position, weight, state, action_type, action_payload, filters, daily_limit, total_limit)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)
		RETURNING id
	`, s.ID, s.CampaignID, s.Name, s.Type, s.Position, s.Weight, s.State, s.ActionType, s.ActionPayload, s.Filters, s.DailyLimit, s.TotalLimit).Scan(&s.ID)
}

// Update modifies an existing stream.
func (r *StreamRepository) Update(ctx context.Context, s *model.Stream) error {
	_, err := r.db.Exec(ctx, `
		UPDATE streams
		SET name = $2, type = $3, position = $4, weight = $5, state = $6, 
		    action_type = $7, action_payload = $8, filters = $9, 
		    daily_limit = $10, total_limit = $11, updated_at = NOW()
		WHERE id = $1
	`, s.ID, s.Name, s.Type, s.Position, s.Weight, s.State, s.ActionType, s.ActionPayload, s.Filters, s.DailyLimit, s.TotalLimit)
	return err
}

// Delete removes a stream.
func (r *StreamRepository) Delete(ctx context.Context, id uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM streams WHERE id = $1", id)
	return err
}

// GetOffers returns all offers associated with a stream.
func (r *StreamRepository) GetOffers(ctx context.Context, streamID uuid.UUID) ([]model.WeightedOffer, error) {
	rows, err := r.db.Query(ctx, `
		SELECT o.id, o.name, o.url, o.affiliate_network_id, o.payout, o.state, so.weight
		FROM offers o
		JOIN stream_offers so ON o.id = so.offer_id
		WHERE so.stream_id = $1
	`, streamID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var offers []model.WeightedOffer
	for rows.Next() {
		var wo model.WeightedOffer
		err := rows.Scan(&wo.Offer.ID, &wo.Offer.Name, &wo.Offer.URL, &wo.Offer.AffiliateNetworkID, &wo.Offer.Payout, &wo.Offer.State, &wo.Weight)
		if err != nil {
			return nil, err
		}
		offers = append(offers, wo)
	}
	return offers, nil
}

// GetLandings returns all landings associated with a stream.
func (r *StreamRepository) GetLandings(ctx context.Context, streamID uuid.UUID) ([]model.WeightedLanding, error) {
	rows, err := r.db.Query(ctx, `
		SELECT l.id, l.name, l.url, l.state, sl.weight
		FROM landings l
		JOIN stream_landings sl ON l.id = sl.landing_id
		WHERE sl.stream_id = $1
	`, streamID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var landings []model.WeightedLanding
	for rows.Next() {
		var wl model.WeightedLanding
		err := rows.Scan(&wl.Landing.ID, &wl.Landing.Name, &wl.Landing.URL, &wl.Landing.State, &wl.Weight)
		if err != nil {
			return nil, err
		}
		landings = append(landings, wl)
	}
	return landings, nil
}

// SyncOffers replaces all offers for a stream.
func (r *StreamRepository) SyncOffers(ctx context.Context, streamID uuid.UUID, offers []model.WeightedOffer) error {
	_, err := r.db.Exec(ctx, "DELETE FROM stream_offers WHERE stream_id = $1", streamID)
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

// SyncLandings replaces all landings for a stream.
func (r *StreamRepository) SyncLandings(ctx context.Context, streamID uuid.UUID, landings []model.WeightedLanding) error {
	_, err := r.db.Exec(ctx, "DELETE FROM stream_landings WHERE stream_id = $1", streamID)
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
