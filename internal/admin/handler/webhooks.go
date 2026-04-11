package handler

import (
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"strings"
	"time"

	"github.com/go-chi/chi/v5"

	"github.com/skyplix/zai-tds/internal/model"
)

type webhookRequest struct {
	Name           string `json:"name"`
	URL            string `json:"url"`
	Secret         string `json:"secret"`
	IsActive       bool   `json:"is_active"`
	MaxRetries     int    `json:"max_retries"`
	TimeoutSeconds int    `json:"timeout_seconds"`
}

type webhookSecretInfo struct {
	Configured bool   `json:"configured"`
	Last4      string `json:"last4,omitempty"`
}

type webhookResponse struct {
	ID             string            `json:"id"`
	TenantID       string            `json:"tenant_id"`
	Name           string            `json:"name"`
	URL            string            `json:"url"`
	IsActive       bool              `json:"is_active"`
	MaxRetries     int               `json:"max_retries"`
	TimeoutSeconds int               `json:"timeout_seconds"`
	Secret         webhookSecretInfo `json:"secret"`
	CreatedAt      time.Time         `json:"created_at"`
	UpdatedAt      time.Time         `json:"updated_at"`
}

// HandleListWebhooks returns tenant-scoped webhook endpoints.
func (h *Handler) HandleListWebhooks(w http.ResponseWriter, r *http.Request) {
	tenantID, err := resolveTenantID(r)
	if err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	limit, offset := h.parsePagination(r)
	items, err := h.webhooks.ListByTenant(r.Context(), tenantID, limit, offset)
	if err != nil {
		h.respondError(w, http.StatusInternalServerError, "failed to list webhooks")
		return
	}

	resp := make([]webhookResponse, 0, len(items))
	for _, item := range items {
		resp = append(resp, toWebhookResponse(item))
	}

	h.respondJSON(w, http.StatusOK, resp)
}

// HandleCreateWebhook creates a tenant-scoped webhook endpoint.
func (h *Handler) HandleCreateWebhook(w http.ResponseWriter, r *http.Request) {
	tenantID, err := resolveTenantID(r)
	if err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	var req webhookRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if err := validateWebhookRequest(req, true); err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	endpoint := model.WebhookEndpoint{
		TenantID:       tenantID,
		Name:           strings.TrimSpace(req.Name),
		URL:            normalizeWebhookURL(req.URL),
		Secret:         strings.TrimSpace(req.Secret),
		IsActive:       req.IsActive,
		MaxRetries:     req.MaxRetries,
		TimeoutSeconds: req.TimeoutSeconds,
	}

	if err := h.webhooks.Create(r.Context(), &endpoint); err != nil {
		h.respondError(w, http.StatusInternalServerError, "failed to create webhook")
		return
	}

	h.respondJSON(w, http.StatusCreated, toWebhookResponse(endpoint))
}

// HandleUpdateWebhook updates a tenant-scoped webhook endpoint.
func (h *Handler) HandleUpdateWebhook(w http.ResponseWriter, r *http.Request) {
	tenantID, err := resolveTenantID(r)
	if err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid webhook id")
		return
	}

	existing, err := h.webhooks.GetByID(r.Context(), tenantID, id)
	if err != nil {
		h.respondError(w, http.StatusNotFound, "webhook not found")
		return
	}

	var req webhookRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	if err := validateWebhookRequest(req, false); err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	existing.Name = strings.TrimSpace(req.Name)
	existing.URL = normalizeWebhookURL(req.URL)
	existing.IsActive = req.IsActive
	existing.MaxRetries = req.MaxRetries
	existing.TimeoutSeconds = req.TimeoutSeconds
	if strings.TrimSpace(req.Secret) != "" {
		existing.Secret = strings.TrimSpace(req.Secret)
	}

	if err := h.webhooks.Update(r.Context(), existing); err != nil {
		h.respondError(w, http.StatusInternalServerError, "failed to update webhook")
		return
	}

	updated, err := h.webhooks.GetByID(r.Context(), tenantID, id)
	if err != nil {
		h.respondError(w, http.StatusInternalServerError, "failed to load updated webhook")
		return
	}

	h.respondJSON(w, http.StatusOK, toWebhookResponse(*updated))
}

// HandleDeleteWebhook deletes a tenant-scoped webhook endpoint.
func (h *Handler) HandleDeleteWebhook(w http.ResponseWriter, r *http.Request) {
	tenantID, err := resolveTenantID(r)
	if err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid webhook id")
		return
	}

	if err := h.webhooks.Delete(r.Context(), tenantID, id); err != nil {
		h.respondError(w, http.StatusInternalServerError, "failed to delete webhook")
		return
	}

	h.respondJSON(w, http.StatusNoContent, nil)
}

func validateWebhookRequest(req webhookRequest, requireSecret bool) error {
	if strings.TrimSpace(req.Name) == "" {
		return fmt.Errorf("name is required")
	}
	if len(strings.TrimSpace(req.Name)) > 120 {
		return fmt.Errorf("name must be <= 120 characters")
	}
	if strings.TrimSpace(req.URL) == "" {
		return fmt.Errorf("url is required")
	}
	if _, err := parseWebhookURL(req.URL); err != nil {
		return err
	}
	if requireSecret && strings.TrimSpace(req.Secret) == "" {
		return fmt.Errorf("secret is required")
	}
	if req.MaxRetries < 0 || req.MaxRetries > 10 {
		return fmt.Errorf("max_retries must be between 0 and 10")
	}
	if req.TimeoutSeconds < 1 || req.TimeoutSeconds > 60 {
		return fmt.Errorf("timeout_seconds must be between 1 and 60")
	}
	return nil
}

func normalizeWebhookURL(raw string) string {
	u, err := parseWebhookURL(raw)
	if err != nil {
		return strings.TrimSpace(raw)
	}
	u.RawQuery = strings.TrimSpace(u.RawQuery)
	return u.String()
}

func parseWebhookURL(raw string) (*url.URL, error) {
	parsed, err := url.Parse(strings.TrimSpace(raw))
	if err != nil {
		return nil, fmt.Errorf("url is invalid")
	}
	if parsed.Scheme != "https" && parsed.Scheme != "http" {
		return nil, fmt.Errorf("url must use http or https")
	}
	if strings.TrimSpace(parsed.Host) == "" {
		return nil, fmt.Errorf("url host is required")
	}
	return parsed, nil
}

func toWebhookResponse(endpoint model.WebhookEndpoint) webhookResponse {
	secret := strings.TrimSpace(endpoint.Secret)
	meta := webhookSecretInfo{Configured: secret != ""}
	if len(secret) >= 4 {
		meta.Last4 = secret[len(secret)-4:]
	}

	return webhookResponse{
		ID:             endpoint.ID.String(),
		TenantID:       endpoint.TenantID,
		Name:           endpoint.Name,
		URL:            endpoint.URL,
		IsActive:       endpoint.IsActive,
		MaxRetries:     endpoint.MaxRetries,
		TimeoutSeconds: endpoint.TimeoutSeconds,
		Secret:         meta,
		CreatedAt:      endpoint.CreatedAt,
		UpdatedAt:      endpoint.UpdatedAt,
	}
}
