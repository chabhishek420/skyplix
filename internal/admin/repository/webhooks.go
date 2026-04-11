package repository

import (
	"context"
	"fmt"
	"strings"
	"time"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

// WebhookRepository handles SQL operations for tenant webhook endpoints.
type WebhookRepository struct {
	db DB
}

// NewWebhookRepository creates a webhook repository.
func NewWebhookRepository(db DB) *WebhookRepository {
	return &WebhookRepository{db: db}
}

// ListByTenant returns webhook endpoints scoped to a tenant.
func (r *WebhookRepository) ListByTenant(ctx context.Context, tenantID string, limit, offset int) ([]model.WebhookEndpoint, error) {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	rows, err := r.db.Query(ctx, `
		SELECT id, tenant_id, name, url, secret, is_active, max_retries, timeout_seconds, created_at, updated_at
		FROM tenant_webhooks
		WHERE tenant_id = $1
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`, tenantID, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("list webhooks: %w", err)
	}
	defer rows.Close()

	out := make([]model.WebhookEndpoint, 0)
	for rows.Next() {
		var endpoint model.WebhookEndpoint
		if err := rows.Scan(
			&endpoint.ID,
			&endpoint.TenantID,
			&endpoint.Name,
			&endpoint.URL,
			&endpoint.Secret,
			&endpoint.IsActive,
			&endpoint.MaxRetries,
			&endpoint.TimeoutSeconds,
			&endpoint.CreatedAt,
			&endpoint.UpdatedAt,
		); err != nil {
			return nil, fmt.Errorf("scan webhook: %w", err)
		}
		out = append(out, endpoint)
	}

	return out, nil
}

// ListActiveByTenant returns active webhook endpoints for delivery.
func (r *WebhookRepository) ListActiveByTenant(ctx context.Context, tenantID string) ([]model.WebhookEndpoint, error) {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	rows, err := r.db.Query(ctx, `
		SELECT id, tenant_id, name, url, secret, is_active, max_retries, timeout_seconds, created_at, updated_at
		FROM tenant_webhooks
		WHERE tenant_id = $1 AND is_active = true
		ORDER BY created_at ASC
	`, tenantID)
	if err != nil {
		return nil, fmt.Errorf("list active webhooks: %w", err)
	}
	defer rows.Close()

	out := make([]model.WebhookEndpoint, 0)
	for rows.Next() {
		var endpoint model.WebhookEndpoint
		if err := rows.Scan(
			&endpoint.ID,
			&endpoint.TenantID,
			&endpoint.Name,
			&endpoint.URL,
			&endpoint.Secret,
			&endpoint.IsActive,
			&endpoint.MaxRetries,
			&endpoint.TimeoutSeconds,
			&endpoint.CreatedAt,
			&endpoint.UpdatedAt,
		); err != nil {
			return nil, fmt.Errorf("scan active webhook: %w", err)
		}
		out = append(out, endpoint)
	}

	return out, nil
}

// GetByID returns a webhook endpoint by id in the tenant scope.
func (r *WebhookRepository) GetByID(ctx context.Context, tenantID string, id uuid.UUID) (*model.WebhookEndpoint, error) {
	ctx, cancel := context.WithTimeout(ctx, 3*time.Second)
	defer cancel()

	var endpoint model.WebhookEndpoint
	err := r.db.QueryRow(ctx, `
		SELECT id, tenant_id, name, url, secret, is_active, max_retries, timeout_seconds, created_at, updated_at
		FROM tenant_webhooks
		WHERE tenant_id = $1 AND id = $2
	`, tenantID, id).Scan(
		&endpoint.ID,
		&endpoint.TenantID,
		&endpoint.Name,
		&endpoint.URL,
		&endpoint.Secret,
		&endpoint.IsActive,
		&endpoint.MaxRetries,
		&endpoint.TimeoutSeconds,
		&endpoint.CreatedAt,
		&endpoint.UpdatedAt,
	)
	if err != nil {
		return nil, fmt.Errorf("get webhook: %w", err)
	}

	return &endpoint, nil
}

// Create inserts a new webhook endpoint.
func (r *WebhookRepository) Create(ctx context.Context, endpoint *model.WebhookEndpoint) error {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	normalizeWebhookDefaults(endpoint)
	if endpoint.ID == uuid.Nil {
		endpoint.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO tenant_webhooks (id, tenant_id, name, url, secret, is_active, max_retries, timeout_seconds)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
		RETURNING created_at, updated_at
	`,
		endpoint.ID,
		endpoint.TenantID,
		endpoint.Name,
		endpoint.URL,
		endpoint.Secret,
		endpoint.IsActive,
		endpoint.MaxRetries,
		endpoint.TimeoutSeconds,
	).Scan(&endpoint.CreatedAt, &endpoint.UpdatedAt)
}

// Update modifies an existing webhook endpoint in tenant scope.
func (r *WebhookRepository) Update(ctx context.Context, endpoint *model.WebhookEndpoint) error {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	normalizeWebhookDefaults(endpoint)

	_, err := r.db.Exec(ctx, `
		UPDATE tenant_webhooks
		SET name = $3,
		    url = $4,
		    secret = CASE WHEN $5 = '' THEN secret ELSE $5 END,
		    is_active = $6,
		    max_retries = $7,
		    timeout_seconds = $8,
		    updated_at = NOW()
		WHERE tenant_id = $1 AND id = $2
	`,
		endpoint.TenantID,
		endpoint.ID,
		endpoint.Name,
		endpoint.URL,
		strings.TrimSpace(endpoint.Secret),
		endpoint.IsActive,
		endpoint.MaxRetries,
		endpoint.TimeoutSeconds,
	)
	if err != nil {
		return fmt.Errorf("update webhook: %w", err)
	}
	return nil
}

// Delete removes a tenant webhook endpoint.
func (r *WebhookRepository) Delete(ctx context.Context, tenantID string, id uuid.UUID) error {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	_, err := r.db.Exec(ctx, `DELETE FROM tenant_webhooks WHERE tenant_id = $1 AND id = $2`, tenantID, id)
	if err != nil {
		return fmt.Errorf("delete webhook: %w", err)
	}
	return nil
}

func normalizeWebhookDefaults(endpoint *model.WebhookEndpoint) {
	if endpoint == nil {
		return
	}
	if endpoint.MaxRetries < 0 {
		endpoint.MaxRetries = 0
	}
	if endpoint.MaxRetries > 10 {
		endpoint.MaxRetries = 10
	}
	if endpoint.TimeoutSeconds <= 0 {
		endpoint.TimeoutSeconds = 5
	}
	if endpoint.TimeoutSeconds > 60 {
		endpoint.TimeoutSeconds = 60
	}
}
