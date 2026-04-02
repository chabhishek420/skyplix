package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"

	"github.com/skyplix/zai-tds/internal/model"
)

// DomainRepository handles SQL operations for the domains table.
type DomainRepository struct {
	db *pgxpool.Pool
}

// NewDomainRepository creates a new repository.
func NewDomainRepository(db *pgxpool.Pool) *DomainRepository {
	return &DomainRepository{db: db}
}

// List returns a paginated list of domains.
func (r *DomainRepository) List(ctx context.Context, limit, offset int) ([]model.Domain, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, domain, campaign_id, state
		FROM domains
		WHERE state != 'archived'
		ORDER BY created_at DESC
		LIMIT $1 OFFSET $2
	`, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query domains: %w", err)
	}
	defer rows.Close()

	var domains []model.Domain
	for rows.Next() {
		var d model.Domain
		err := rows.Scan(&d.ID, &d.Domain, &d.CampaignID, &d.State)
		if err != nil {
			return nil, fmt.Errorf("scan domain: %w", err)
		}
		domains = append(domains, d)
	}

	return domains, nil
}

// GetByID retrieves a single domain by uuid.
func (r *DomainRepository) GetByID(ctx context.Context, id uuid.UUID) (*model.Domain, error) {
	var d model.Domain
	err := r.db.QueryRow(ctx, `
		SELECT id, domain, campaign_id, state
		FROM domains
		WHERE id = $1
	`, id).Scan(&d.ID, &d.Domain, &d.CampaignID, &d.State)
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
		INSERT INTO domains (id, domain, campaign_id, state)
		VALUES ($1, $2, $3, $4)
		RETURNING id
	`, d.ID, d.Domain, d.CampaignID, d.State).Scan(&d.ID)
}

// Update modifies an existing domain.
func (r *DomainRepository) Update(ctx context.Context, d *model.Domain) error {
	_, err := r.db.Exec(ctx, `
		UPDATE domains
		SET domain = $2, campaign_id = $3, state = $4
		WHERE id = $1
	`, d.ID, d.Domain, d.CampaignID, d.State)
	return err
}

// Delete archives a domain instead of hard deleting.
func (r *DomainRepository) Delete(ctx context.Context, id uuid.UUID) error {
	_, err := r.db.Exec(ctx, "UPDATE domains SET state = 'archived' WHERE id = $1", id)
	return err
}

// Restore unarchives a domain.
func (r *DomainRepository) Restore(ctx context.Context, id uuid.UUID) error {
	_, err := r.db.Exec(ctx, "UPDATE domains SET state = 'active' WHERE id = $1", id)
	return err
}

// ListDeleted returns a paginated list of archived domains.
func (r *DomainRepository) ListDeleted(ctx context.Context, limit, offset int) ([]model.Domain, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, domain, campaign_id, state
		FROM domains
		WHERE state = 'archived'
		ORDER BY created_at DESC
		LIMIT $1 OFFSET $2
	`, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query deleted domains: %w", err)
	}
	defer rows.Close()

	var domains []model.Domain
	for rows.Next() {
		var d model.Domain
		err := rows.Scan(&d.ID, &d.Domain, &d.CampaignID, &d.State)
		if err != nil {
			return nil, fmt.Errorf("scan deleted domain: %w", err)
		}
		domains = append(domains, d)
	}

	return domains, nil
}
