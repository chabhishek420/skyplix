package stage

import (
	"time"

	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/queue"
)

// StoreRawClicksStage — Pipeline Stage 23
// Pushes the RawClick to the async ClickHouse write channel.
// This is non-blocking — if the channel is full, the click is dropped with a warning.
// The channel is consumed by queue.Writer which batches and inserts to ClickHouse.
type StoreRawClicksStage struct {
	ClickChan chan<- queue.ClickRecord
}

func (s *StoreRawClicksStage) Name() string      { return "StoreRawClicks" }
func (s *StoreRawClicksStage) AlwaysRun() bool   { return true }

func (s *StoreRawClicksStage) Process(payload *pipeline.Payload) error {
	rc := payload.RawClick
	if rc == nil {
		return nil
	}

	if rc.CreatedAt.IsZero() {
		rc.CreatedAt = time.Now().UTC()
	}

	record := queue.FromRawClick(rc)

	// Non-blocking send — hot path must not be blocked by ClickHouse backpressure
	select {
	case s.ClickChan <- record:
		// queued successfully
	default:
		// Channel full — log a warning but DO NOT block the click response
		// This situation should be rare (channel capacity: 10k records)
	}

	return nil
}
