package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"

	"github.com/skyplix/zai-tds/internal/model"
)

// OfferRepository handles SQL operations for the offers table.
type OfferRepository struct {
	db *pgxpool.Pool
}

// NewOfferRepository creates a new repository.
func NewOfferRepository(db *pgxpool.Pool) *OfferRepository {
	return &OfferRepository{db: db}
}

// List returns a paginated list of offers.
func (r *OfferRepository) List(ctx context.Context, limit, offset int) ([]model.Offer, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, name, url, affiliate_network_id, payout, state
		FROM offers
		ORDER BY created_at DESC
		LIMIT $1 OFFSET $2
	`, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query offers: %w", err)
	}
	defer rows.Close()

	var offers []model.Offer
	for rows.Next() {
		var o model.Offer
		err := rows.Scan(&o.ID, &o.Name, &o.URL, &o.AffiliateNetworkID, &o.Payout, &o.State)
		if err != nil {
			return nil, fmt.Errorf("scan offer: %w", err)
		}
		offers = append(offers, o)
	}

	return offers, nil
}

// GetByID retrieves a single offer by uuid.
func (r *OfferRepository) GetByID(ctx context.Context, id uuid.UUID) (*model.Offer, error) {
	var o model.Offer
	err := r.db.QueryRow(ctx, `
		SELECT id, name, url, affiliate_network_id, payout, state
		FROM offers
		WHERE id = $1
	`, id).Scan(&o.ID, &o.Name, &o.URL, &o.AffiliateNetworkID, &o.Payout, &o.State)
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
		INSERT INTO offers (id, name, url, affiliate_network_id, payout, state)
		VALUES ($1, $2, $3, $4, $5, $6)
		RETURNING id
	`, o.ID, o.Name, o.URL, o.AffiliateNetworkID, o.Payout, o.State).Scan(&o.ID)
}

// Update modifies an existing offer.
func (r *OfferRepository) Update(ctx context.Context, o *model.Offer) error {
	_, err := r.db.Exec(ctx, `
		UPDATE offers
		SET name = $2, url = $3, affiliate_network_id = $4, payout = $5, state = $6, updated_at = NOW()
		WHERE id = $1
	`, o.ID, o.Name, o.URL, o.AffiliateNetworkID, o.Payout, o.State)
	return err
}

// Delete archives or deletes an offer.
func (r *OfferRepository) Delete(ctx context.Context, id uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM offers WHERE id = $1", id)
	return err
}
