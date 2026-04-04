package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// OfferRepository handles SQL operations for the offers table.
type OfferRepository struct {
	db DB
}

// NewOfferRepository creates a new repository.
func NewOfferRepository(db DB) *OfferRepository {
	return &OfferRepository{db: db}
}

// List returns a paginated list of offers for a specific workspace.
func (r *OfferRepository) List(ctx context.Context, workspaceID uuid.UUID, limit, offset int) ([]model.Offer, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, workspace_id, name, url, affiliate_network_id, payout, daily_cap, state, notes, created_at, updated_at
		FROM offers
		WHERE workspace_id = $1
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`, workspaceID, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query offers: %w", err)
	}
	defer rows.Close()

	var offers []model.Offer
	for rows.Next() {
		var o model.Offer
		err := rows.Scan(
			&o.ID, &o.WorkspaceID, &o.Name, &o.URL, &o.AffiliateNetworkID,
			&o.Payout, &o.DailyCap, &o.State, &o.Notes, &o.CreatedAt, &o.UpdatedAt,
		)
		if err != nil {
			return nil, fmt.Errorf("scan offer: %w", err)
		}
		offers = append(offers, o)
	}

	return offers, nil
}

// GetByID retrieves a single offer by uuid and workspace.
func (r *OfferRepository) GetByID(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) (*model.Offer, error) {
	var o model.Offer
	err := r.db.QueryRow(ctx, `
		SELECT id, workspace_id, name, url, affiliate_network_id, payout, daily_cap, state, notes, created_at, updated_at
		FROM offers
		WHERE id = $1 AND workspace_id = $2
	`, id, workspaceID).Scan(
		&o.ID, &o.WorkspaceID, &o.Name, &o.URL, &o.AffiliateNetworkID,
		&o.Payout, &o.DailyCap, &o.State, &o.Notes, &o.CreatedAt, &o.UpdatedAt,
	)
	if err != nil {
		return nil, fmt.Errorf("get offer: %w", err)
	}
	return &o, nil
}

// Create inserts a new offer.
func (r *OfferRepository) Create(ctx context.Context, o *model.Offer) error {
	if o.ID == uuid.Nil {
		o.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO offers (id, workspace_id, name, url, affiliate_network_id, payout, daily_cap, state, notes)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
		RETURNING id
	`, o.ID, o.WorkspaceID, o.Name, o.URL, o.AffiliateNetworkID, o.Payout, o.DailyCap, o.State, o.Notes).Scan(&o.ID)
}

// Update modifies an existing offer within a workspace.
func (r *OfferRepository) Update(ctx context.Context, o *model.Offer) error {
	_, err := r.db.Exec(ctx, `
		UPDATE offers
		SET name = $3, url = $4, affiliate_network_id = $5, payout = $6, daily_cap = $7, state = $8, notes = $9, updated_at = NOW()
		WHERE id = $1 AND workspace_id = $2
	`, o.ID, o.WorkspaceID, o.Name, o.URL, o.AffiliateNetworkID, o.Payout, o.DailyCap, o.State, o.Notes)
	return err
}

// Delete archives or deletes an offer within a workspace.
func (r *OfferRepository) Delete(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM offers WHERE id = $1 AND workspace_id = $2", id, workspaceID)
	return err
}
