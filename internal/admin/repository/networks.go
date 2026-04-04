package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// NetworkRepository handles SQL operations for the affiliate_networks table.
type NetworkRepository struct {
	db DB
}

// NewNetworkRepository creates a new repository.
func NewNetworkRepository(db DB) *NetworkRepository {
	return &NetworkRepository{db: db}
}

// List returns a paginated list of affiliate networks for a specific workspace.
func (r *NetworkRepository) List(ctx context.Context, workspaceID uuid.UUID, limit, offset int) ([]model.AffiliateNetwork, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, workspace_id, name, postback_url, state, created_at, updated_at
		FROM affiliate_networks
		WHERE workspace_id = $1
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`, workspaceID, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query affiliate networks: %w", err)
	}
	defer rows.Close()

	var networks []model.AffiliateNetwork
	for rows.Next() {
		var n model.AffiliateNetwork
		err := rows.Scan(&n.ID, &n.WorkspaceID, &n.Name, &n.PostbackURL, &n.State, &n.CreatedAt, &n.UpdatedAt)
		if err != nil {
			return nil, fmt.Errorf("scan affiliate network: %w", err)
		}
		networks = append(networks, n)
	}

	return networks, nil
}

// GetByID retrieves a single affiliate network by uuid and workspace.
func (r *NetworkRepository) GetByID(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) (*model.AffiliateNetwork, error) {
	var n model.AffiliateNetwork
	err := r.db.QueryRow(ctx, `
		SELECT id, workspace_id, name, postback_url, state, created_at, updated_at
		FROM affiliate_networks
		WHERE id = $1 AND workspace_id = $2
	`, id, workspaceID).Scan(&n.ID, &n.WorkspaceID, &n.Name, &n.PostbackURL, &n.State, &n.CreatedAt, &n.UpdatedAt)
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
		INSERT INTO affiliate_networks (id, workspace_id, name, postback_url, state)
		VALUES ($1, $2, $3, $4, $5)
		RETURNING id
	`, n.ID, n.WorkspaceID, n.Name, n.PostbackURL, n.State).Scan(&n.ID)
}

// Update modifies an existing affiliate network within a workspace.
func (r *NetworkRepository) Update(ctx context.Context, n *model.AffiliateNetwork) error {
	_, err := r.db.Exec(ctx, `
		UPDATE affiliate_networks
		SET name = $3, postback_url = $4, state = $5, updated_at = NOW()
		WHERE id = $1 AND workspace_id = $2
	`, n.ID, n.WorkspaceID, n.Name, n.PostbackURL, n.State)
	return err
}

// Delete archives or deletes an affiliate network within a workspace.
func (r *NetworkRepository) Delete(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM affiliate_networks WHERE id = $1 AND workspace_id = $2", id, workspaceID)
	return err
}
