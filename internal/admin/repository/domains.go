package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// DomainRepository handles SQL operations for the domains table.
type DomainRepository struct {
	db DB
}

// NewDomainRepository creates a new repository.
func NewDomainRepository(db DB) *DomainRepository {
	return &DomainRepository{db: db}
}

// List returns a paginated list of domains for a specific workspace.
func (r *DomainRepository) List(ctx context.Context, workspaceID uuid.UUID, limit, offset int) ([]model.Domain, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, workspace_id, domain, campaign_id, state, created_at
		FROM domains
		WHERE workspace_id = $1 AND state != 'archived'
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`, workspaceID, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query domains: %w", err)
	}
	defer rows.Close()

	var domains []model.Domain
	for rows.Next() {
		var d model.Domain
		err := rows.Scan(&d.ID, &d.WorkspaceID, &d.Domain, &d.CampaignID, &d.State, &d.CreatedAt)
		if err != nil {
			return nil, fmt.Errorf("scan domain: %w", err)
		}
		domains = append(domains, d)
	}

	return domains, nil
}

// GetByID retrieves a single domain by uuid and workspace.
func (r *DomainRepository) GetByID(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) (*model.Domain, error) {
	var d model.Domain
	err := r.db.QueryRow(ctx, `
		SELECT id, workspace_id, domain, campaign_id, state, created_at
		FROM domains
		WHERE id = $1 AND workspace_id = $2
	`, id, workspaceID).Scan(&d.ID, &d.WorkspaceID, &d.Domain, &d.CampaignID, &d.State, &d.CreatedAt)
	if err != nil {
		return nil, fmt.Errorf("get domain: %w", err)
	}
	return &d, nil
}

// Create inserts a new domain.
func (r *DomainRepository) Create(ctx context.Context, d *model.Domain) error {
	if d.ID == uuid.Nil {
		d.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO domains (id, workspace_id, domain, campaign_id, state)
		VALUES ($1, $2, $3, $4, $5)
		RETURNING id
	`, d.ID, d.WorkspaceID, d.Domain, d.CampaignID, d.State).Scan(&d.ID)
}

// Update modifies an existing domain within a workspace.
func (r *DomainRepository) Update(ctx context.Context, d *model.Domain) error {
	_, err := r.db.Exec(ctx, `
		UPDATE domains
		SET domain = $3, campaign_id = $4, state = $5
		WHERE id = $1 AND workspace_id = $2
	`, d.ID, d.WorkspaceID, d.Domain, d.CampaignID, d.State)
	return err
}

// Delete archives a domain within a workspace.
func (r *DomainRepository) Delete(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "UPDATE domains SET state = 'archived' WHERE id = $1 AND workspace_id = $2", id, workspaceID)
	return err
}

// Restore unarchives a domain within a workspace.
func (r *DomainRepository) Restore(ctx context.Context, id uuid.UUID, workspaceID uuid.UUID) error {
	_, err := r.db.Exec(ctx, "UPDATE domains SET state = 'active' WHERE id = $1 AND workspace_id = $2", id, workspaceID)
	return err
}

// ListDeleted returns a paginated list of archived domains for a specific workspace.
func (r *DomainRepository) ListDeleted(ctx context.Context, workspaceID uuid.UUID, limit, offset int) ([]model.Domain, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, workspace_id, domain, campaign_id, state, created_at
		FROM domains
		WHERE workspace_id = $1 AND state = 'archived'
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`, workspaceID, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query deleted domains: %w", err)
	}
	defer rows.Close()

	var domains []model.Domain
	for rows.Next() {
		var d model.Domain
		err := rows.Scan(&d.ID, &d.WorkspaceID, &d.Domain, &d.CampaignID, &d.State, &d.CreatedAt)
		if err != nil {
			return nil, fmt.Errorf("scan deleted domain: %w", err)
		}
		domains = append(domains, d)
	}

	return domains, nil
}
