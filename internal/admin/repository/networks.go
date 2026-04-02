package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"

	"github.com/skyplix/zai-tds/internal/model"
)

// NetworkRepository handles SQL operations for the affiliate_networks table.
type NetworkRepository struct {
	db *pgxpool.Pool
}

// NewNetworkRepository creates a new repository.
func NewNetworkRepository(db *pgxpool.Pool) *NetworkRepository {
	return &NetworkRepository{db: db}
}

// List returns a paginated list of affiliate networks.
func (r *NetworkRepository) List(ctx context.Context, limit, offset int) ([]model.AffiliateNetwork, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, name, postback_url, state
		FROM affiliate_networks
		ORDER BY created_at DESC
		LIMIT $1 OFFSET $2
	`, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query affiliate networks: %w", err)
	}
	defer rows.Close()

	var networks []model.AffiliateNetwork
	for rows.Next() {
		var n model.AffiliateNetwork
		err := rows.Scan(&n.ID, &n.Name, &n.PostbackURL, &n.State)
		if err != nil {
			return nil, fmt.Errorf("scan affiliate network: %w", err)
		}
		networks = append(networks, n)
	}

	return networks, nil
}

// GetByID retrieves a single affiliate network by uuid.
func (r *NetworkRepository) GetByID(ctx context.Context, id uuid.UUID) (*model.AffiliateNetwork, error) {
	var n model.AffiliateNetwork
	err := r.db.QueryRow(ctx, `
		SELECT id, name, postback_url, state
		FROM affiliate_networks
		WHERE id = $1
	`, id).Scan(&n.ID, &n.Name, &n.PostbackURL, &n.State)
	if err != nil {
		return nil, fmt.Errorf("get affiliate network: %w", err)
	}
	return &n, nil
}

// Create inserts a new affiliate network.
func (r *NetworkRepository) Create(ctx context.Context, n *model.AffiliateNetwork) error {
	if n.ID == uuid.Nil {
		n.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO affiliate_networks (id, name, postback_url, state)
		VALUES ($1, $2, $3, $4)
		RETURNING id
	`, n.ID, n.Name, n.PostbackURL, n.State).Scan(&n.ID)
}

// Update modifies an existing affiliate network.
func (r *NetworkRepository) Update(ctx context.Context, n *model.AffiliateNetwork) error {
	_, err := r.db.Exec(ctx, `
		UPDATE affiliate_networks
		SET name = $2, postback_url = $3, state = $4, updated_at = NOW()
		WHERE id = $1
	`, n.ID, n.Name, n.PostbackURL, n.State)
	return err
}

// Delete archives or deletes an affiliate network.
func (r *NetworkRepository) Delete(ctx context.Context, id uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM affiliate_networks WHERE id = $1", id)
	return err
}
