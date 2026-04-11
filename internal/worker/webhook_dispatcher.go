package worker

import (
	"bytes"
	"context"
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// WebhookEndpointSource resolves tenant-scoped endpoint configuration for webhook delivery.
type WebhookEndpointSource interface {
	ListActiveByTenant(ctx context.Context, tenantID string) ([]model.WebhookEndpoint, error)
}

type webhookHTTPClient interface {
	Do(req *http.Request) (*http.Response, error)
}

// DeliveryState describes the terminal or in-flight state of a webhook attempt.
type DeliveryState string

const (
	DeliveryStateDelivered DeliveryState = "delivered"
	DeliveryStateRetrying  DeliveryState = "retrying"
	DeliveryStateDead      DeliveryState = "dead_letter"
)

// FailedDelivery carries dead-letter details for observability.
type FailedDelivery struct {
	Event      model.WebhookConversionEvent
	EndpointID string
	Attempts   int
	Reason     string
}

// WebhookDispatcher delivers conversion notifications to tenant endpoints with retry semantics.
type WebhookDispatcher struct {
	queue      <-chan model.WebhookConversionEvent
	endpoints  WebhookEndpointSource
	httpClient webhookHTTPClient
	logger     *zap.Logger
	deadLetter chan FailedDelivery
}

// NewWebhookDispatcher creates a new dispatcher worker.
func NewWebhookDispatcher(
	queue <-chan model.WebhookConversionEvent,
	endpoints WebhookEndpointSource,
	httpClient webhookHTTPClient,
	logger *zap.Logger,
) *WebhookDispatcher {
	if httpClient == nil {
		httpClient = &http.Client{}
	}

	return &WebhookDispatcher{
		queue:      queue,
		endpoints:  endpoints,
		httpClient: httpClient,
		logger:     logger,
		deadLetter: make(chan FailedDelivery, 1024),
	}
}

// Name returns the worker name.
func (d *WebhookDispatcher) Name() string { return "webhook-dispatcher" }

// DeadLetter returns a read-only stream of terminal delivery failures.
func (d *WebhookDispatcher) DeadLetter() <-chan FailedDelivery {
	return d.deadLetter
}

// Run starts the dispatcher loop.
func (d *WebhookDispatcher) Run(ctx context.Context) error {
	if d.queue == nil || d.endpoints == nil {
		if d.logger != nil {
			d.logger.Info("webhook dispatcher disabled", zap.Bool("queue_missing", d.queue == nil), zap.Bool("endpoint_source_missing", d.endpoints == nil))
		}
		<-ctx.Done()
		return nil
	}

	for {
		select {
		case <-ctx.Done():
			return nil
		case event := <-d.queue:
			d.dispatchEvent(ctx, event)
		}
	}
}

func (d *WebhookDispatcher) dispatchEvent(ctx context.Context, event model.WebhookConversionEvent) {
	if strings.TrimSpace(event.TenantID) == "" {
		if d.logger != nil {
			d.logger.Warn("skipping webhook event without tenant", zap.String("event_id", event.EventID))
		}
		return
	}

	endpoints, err := d.endpoints.ListActiveByTenant(ctx, event.TenantID)
	if err != nil {
		if d.logger != nil {
			d.logger.Error("list tenant webhooks failed", zap.String("tenant_id", event.TenantID), zap.Error(err))
		}
		return
	}
	if len(endpoints) == 0 {
		return
	}

	for _, endpoint := range endpoints {
		state, reason, attempts := d.deliverWithRetry(ctx, event, endpoint)
		fields := []zap.Field{
			zap.String("event_id", event.EventID),
			zap.String("tenant_id", event.TenantID),
			zap.String("endpoint_id", endpoint.ID.String()),
			zap.String("state", string(state)),
			zap.Int("attempts", attempts),
		}
		if reason != "" {
			fields = append(fields, zap.String("reason", reason))
		}

		switch state {
		case DeliveryStateDelivered:
			if d.logger != nil {
				d.logger.Info("webhook delivered", fields...)
			}
		case DeliveryStateRetrying:
			if d.logger != nil {
				d.logger.Warn("webhook exhausted retries", fields...)
			}
		default:
			if d.logger != nil {
				d.logger.Error("webhook moved to dead letter", fields...)
			}
			select {
			case d.deadLetter <- FailedDelivery{Event: event, EndpointID: endpoint.ID.String(), Attempts: attempts, Reason: reason}:
			default:
				if d.logger != nil {
					d.logger.Warn("dead-letter channel full", zap.String("event_id", event.EventID), zap.String("endpoint_id", endpoint.ID.String()))
				}
			}
		}
	}
}

func (d *WebhookDispatcher) deliverWithRetry(
	ctx context.Context,
	event model.WebhookConversionEvent,
	endpoint model.WebhookEndpoint,
) (DeliveryState, string, int) {
	maxAttempts := endpoint.MaxRetries + 1
	if maxAttempts < 1 {
		maxAttempts = 1
	}
	if maxAttempts > 11 {
		maxAttempts = 11
	}

	var lastReason string
	for attempt := 1; attempt <= maxAttempts; attempt++ {
		state, reason := d.deliverOnce(ctx, event, endpoint, attempt)
		if state == DeliveryStateDelivered {
			return state, "", attempt
		}
		lastReason = reason

		if state == DeliveryStateRetrying && attempt < maxAttempts {
			if !sleepWithContext(ctx, retryBackoff(attempt)) {
				return DeliveryStateDead, "context cancelled during retry", attempt
			}
			continue
		}

		if state == DeliveryStateRetrying {
			return DeliveryStateDead, lastReason, attempt
		}

		return state, reason, attempt
	}

	return DeliveryStateDead, lastReason, maxAttempts
}

func (d *WebhookDispatcher) deliverOnce(
	ctx context.Context,
	event model.WebhookConversionEvent,
	endpoint model.WebhookEndpoint,
	attempt int,
) (DeliveryState, string) {
	body, err := json.Marshal(map[string]any{
		"id":          event.EventID,
		"type":        "conversion",
		"tenant_id":   event.TenantID,
		"occurred_at": event.OccurredAt.UTC().Format(time.RFC3339Nano),
		"conversion": map[string]any{
			"id":              event.ConversionID,
			"click_token":     event.ClickToken,
			"campaign_id":     event.CampaignID,
			"stream_id":       event.StreamID,
			"offer_id":        event.OfferID,
			"landing_id":      event.LandingID,
			"country_code":    event.CountryCode,
			"status":          event.Status,
			"payout":          event.Payout,
			"revenue":         event.Revenue,
			"external_id":     event.ExternalID,
			"conversion_type": event.ConversionType,
		},
	})
	if err != nil {
		return DeliveryStateDead, fmt.Sprintf("marshal payload: %v", err)
	}

	timeoutSeconds := endpoint.TimeoutSeconds
	if timeoutSeconds <= 0 {
		timeoutSeconds = 5
	}

	reqCtx, cancel := context.WithTimeout(ctx, time.Duration(timeoutSeconds)*time.Second)
	defer cancel()

	req, err := http.NewRequestWithContext(reqCtx, http.MethodPost, endpoint.URL, bytes.NewReader(body))
	if err != nil {
		return DeliveryStateDead, fmt.Sprintf("build request: %v", err)
	}

	signature := webhookSignature(endpoint.Secret, body)
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-ZTDS-Event-Id", event.EventID)
	req.Header.Set("X-ZTDS-Event-Type", "conversion")
	req.Header.Set("X-ZTDS-Delivery-Attempt", fmt.Sprintf("%d", attempt))
	req.Header.Set("X-ZTDS-Signature", "sha256="+signature)

	resp, err := d.httpClient.Do(req)
	if err != nil {
		return DeliveryStateRetrying, fmt.Sprintf("http request failed: %v", err)
	}
	defer resp.Body.Close()
	_, _ = io.Copy(io.Discard, resp.Body)

	if resp.StatusCode >= 200 && resp.StatusCode < 300 {
		return DeliveryStateDelivered, ""
	}
	if resp.StatusCode == http.StatusTooManyRequests || resp.StatusCode >= 500 {
		return DeliveryStateRetrying, fmt.Sprintf("retryable status: %d", resp.StatusCode)
	}

	return DeliveryStateDead, fmt.Sprintf("non-retryable status: %d", resp.StatusCode)
}

func retryBackoff(attempt int) time.Duration {
	if attempt < 1 {
		attempt = 1
	}
	d := time.Second * time.Duration(1<<(attempt-1))
	if d > 8*time.Second {
		d = 8 * time.Second
	}
	return d
}

func sleepWithContext(ctx context.Context, d time.Duration) bool {
	if d <= 0 {
		return true
	}
	t := time.NewTimer(d)
	defer t.Stop()

	select {
	case <-ctx.Done():
		return false
	case <-t.C:
		return true
	}
}

func webhookSignature(secret string, body []byte) string {
	mac := hmac.New(sha256.New, []byte(secret))
	mac.Write(body)
	return hex.EncodeToString(mac.Sum(nil))
}
