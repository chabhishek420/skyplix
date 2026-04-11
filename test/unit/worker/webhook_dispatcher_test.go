package worker_test

import (
	"context"
	"net/http"
	"net/http/httptest"
	"strings"
	"sync/atomic"
	"testing"
	"time"

	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/worker"
)

type fakeWebhookSource struct {
	endpoints map[string][]model.WebhookEndpoint
}

func (f *fakeWebhookSource) ListActiveByTenant(_ context.Context, tenantID string) ([]model.WebhookEndpoint, error) {
	return append([]model.WebhookEndpoint(nil), f.endpoints[tenantID]...), nil
}

func TestWebhookDispatcher_DeliversSignedPayload(t *testing.T) {
	var gotSignature string
	var gotEventID string
	reqDone := make(chan struct{}, 1)

	ts := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		gotSignature = r.Header.Get("X-ZTDS-Signature")
		gotEventID = r.Header.Get("X-ZTDS-Event-Id")
		w.WriteHeader(http.StatusOK)
		reqDone <- struct{}{}
	}))
	defer ts.Close()

	endpoint := model.WebhookEndpoint{
		ID:             uuid.New(),
		TenantID:       "tenant-a",
		Name:           "primary",
		URL:            ts.URL,
		Secret:         "test-secret",
		IsActive:       true,
		MaxRetries:     0,
		TimeoutSeconds: 2,
	}
	source := &fakeWebhookSource{endpoints: map[string][]model.WebhookEndpoint{"tenant-a": {endpoint}}}

	queue := worker.NewWebhookQueue(4, zap.NewNop())
	dispatcher := worker.NewWebhookDispatcher(queue.Channel(), source, ts.Client(), zap.NewNop())

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()
	go func() { _ = dispatcher.Run(ctx) }()

	event := model.WebhookConversionEvent{EventID: "evt-1", TenantID: "tenant-a", OccurredAt: time.Now().UTC(), ConversionID: "conv-1"}
	if err := queue.Enqueue(event); err != nil {
		t.Fatalf("enqueue failed: %v", err)
	}

	select {
	case <-reqDone:
	case <-time.After(2 * time.Second):
		t.Fatal("timed out waiting for webhook delivery")
	}

	if gotEventID != "evt-1" {
		t.Fatalf("expected event id header evt-1, got %q", gotEventID)
	}
	if !strings.HasPrefix(gotSignature, "sha256=") {
		t.Fatalf("expected sha256 signature header, got %q", gotSignature)
	}
}

func TestWebhookDispatcher_RetriesOnServerError(t *testing.T) {
	var attempts int32
	reqDone := make(chan struct{}, 1)

	ts := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		current := atomic.AddInt32(&attempts, 1)
		if current == 1 {
			w.WriteHeader(http.StatusInternalServerError)
			return
		}
		w.WriteHeader(http.StatusOK)
		reqDone <- struct{}{}
	}))
	defer ts.Close()

	endpoint := model.WebhookEndpoint{
		ID:             uuid.New(),
		TenantID:       "tenant-a",
		Name:           "primary",
		URL:            ts.URL,
		Secret:         "test-secret",
		IsActive:       true,
		MaxRetries:     1,
		TimeoutSeconds: 2,
	}
	source := &fakeWebhookSource{endpoints: map[string][]model.WebhookEndpoint{"tenant-a": {endpoint}}}

	queue := worker.NewWebhookQueue(4, zap.NewNop())
	dispatcher := worker.NewWebhookDispatcher(queue.Channel(), source, ts.Client(), zap.NewNop())

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()
	go func() { _ = dispatcher.Run(ctx) }()

	event := model.WebhookConversionEvent{EventID: "evt-retry", TenantID: "tenant-a", OccurredAt: time.Now().UTC(), ConversionID: "conv-retry"}
	if err := queue.Enqueue(event); err != nil {
		t.Fatalf("enqueue failed: %v", err)
	}

	select {
	case <-reqDone:
	case <-time.After(4 * time.Second):
		t.Fatal("timed out waiting for retry delivery")
	}

	if got := atomic.LoadInt32(&attempts); got != 2 {
		t.Fatalf("expected 2 attempts, got %d", got)
	}
}

func TestWebhookDispatcher_DeadLettersPermanentFailures(t *testing.T) {
	ts := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusBadRequest)
	}))
	defer ts.Close()

	endpoint := model.WebhookEndpoint{
		ID:             uuid.New(),
		TenantID:       "tenant-a",
		Name:           "primary",
		URL:            ts.URL,
		Secret:         "test-secret",
		IsActive:       true,
		MaxRetries:     3,
		TimeoutSeconds: 2,
	}
	source := &fakeWebhookSource{endpoints: map[string][]model.WebhookEndpoint{"tenant-a": {endpoint}}}

	queue := worker.NewWebhookQueue(4, zap.NewNop())
	dispatcher := worker.NewWebhookDispatcher(queue.Channel(), source, ts.Client(), zap.NewNop())

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()
	go func() { _ = dispatcher.Run(ctx) }()

	event := model.WebhookConversionEvent{EventID: "evt-dead", TenantID: "tenant-a", OccurredAt: time.Now().UTC(), ConversionID: "conv-dead"}
	if err := queue.Enqueue(event); err != nil {
		t.Fatalf("enqueue failed: %v", err)
	}

	select {
	case dead := <-dispatcher.DeadLetter():
		if dead.Attempts != 1 {
			t.Fatalf("expected 1 attempt for non-retryable failure, got %d", dead.Attempts)
		}
		if !strings.Contains(dead.Reason, "non-retryable status") {
			t.Fatalf("expected non-retryable reason, got %q", dead.Reason)
		}
	case <-time.After(2 * time.Second):
		t.Fatal("timed out waiting for dead-letter event")
	}
}
