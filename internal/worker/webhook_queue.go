package worker

import (
	"errors"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

var ErrWebhookQueueFull = errors.New("webhook queue is full")

// WebhookQueue buffers conversion webhook events for asynchronous delivery.
type WebhookQueue struct {
	logger *zap.Logger
	ch     chan model.WebhookConversionEvent
}

// NewWebhookQueue creates a webhook queue with the provided buffer size.
func NewWebhookQueue(bufferSize int, logger *zap.Logger) *WebhookQueue {
	if bufferSize <= 0 {
		bufferSize = 1024
	}
	return &WebhookQueue{
		logger: logger,
		ch:     make(chan model.WebhookConversionEvent, bufferSize),
	}
}

// Enqueue adds a conversion event for background delivery.
func (q *WebhookQueue) Enqueue(event model.WebhookConversionEvent) error {
	select {
	case q.ch <- event:
		return nil
	default:
		if q.logger != nil {
			q.logger.Warn("webhook queue full", zap.String("event_id", event.EventID), zap.String("tenant_id", event.TenantID))
		}
		return ErrWebhookQueueFull
	}
}

// Channel returns the receive-only queue channel for worker consumption.
func (q *WebhookQueue) Channel() <-chan model.WebhookConversionEvent {
	return q.ch
}
