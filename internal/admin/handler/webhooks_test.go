package handler

import (
	"testing"
	"time"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
)

func TestValidateWebhookRequest(t *testing.T) {
	valid := webhookRequest{
		Name:           "Primary Endpoint",
		URL:            "https://example.com/webhook",
		Secret:         "super-secret",
		IsActive:       true,
		MaxRetries:     3,
		TimeoutSeconds: 5,
	}

	if err := validateWebhookRequest(valid, true); err != nil {
		t.Fatalf("expected valid request, got error: %v", err)
	}

	invalidURL := valid
	invalidURL.URL = "ftp://example.com"
	if err := validateWebhookRequest(invalidURL, true); err == nil {
		t.Fatal("expected invalid URL error")
	}

	missingSecret := valid
	missingSecret.Secret = ""
	if err := validateWebhookRequest(missingSecret, true); err == nil {
		t.Fatal("expected missing secret error")
	}

	invalidRetries := valid
	invalidRetries.MaxRetries = 11
	if err := validateWebhookRequest(invalidRetries, false); err == nil {
		t.Fatal("expected max_retries validation error")
	}
}

func TestToWebhookResponse_HidesSecret(t *testing.T) {
	now := time.Now().UTC()
	endpoint := model.WebhookEndpoint{
		ID:             uuid.New(),
		TenantID:       "tenant-a",
		Name:           "Primary",
		URL:            "https://example.com/hook",
		Secret:         "abc12345",
		IsActive:       true,
		MaxRetries:     2,
		TimeoutSeconds: 10,
		CreatedAt:      now,
		UpdatedAt:      now,
	}

	resp := toWebhookResponse(endpoint)
	if resp.Secret.Configured != true {
		t.Fatal("expected secret to be marked configured")
	}
	if resp.Secret.Last4 != "2345" {
		t.Fatalf("expected last4=2345, got %q", resp.Secret.Last4)
	}
}
