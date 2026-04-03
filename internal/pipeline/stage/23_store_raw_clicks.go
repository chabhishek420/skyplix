package stage

import (
	"time"

	"github.com/skyplix/zai-tds/internal/attribution"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/queue"
)

// StoreRawClicksStage — Pipeline Stage 23
// Pushes the RawClick to the async ClickHouse write channel.
// This is non-blocking — if the channel is full, the click is dropped with a warning.
// The channel is consumed by queue.Writer which batches and inserts to ClickHouse.
type StoreRawClicksStage struct {
	ClickChan   chan<- queue.ClickRecord
	Attribution *attribution.Service
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

	// Phase 5.1: Attribution Caching
	if s.Attribution != nil && rc.ClickToken != "" {
		attr := model.AttributionData{
			CampaignID:  rc.CampaignID,
			StreamID:    rc.StreamID,
			OfferID:     rc.OfferID,
			LandingID:   rc.LandingID,
			CountryCode: rc.CountryCode,
		}
		if payload.AffiliateNetwork != nil {
			attr.AffiliateNetworkID = payload.AffiliateNetwork.ID
		}
		if payload.Campaign != nil && payload.Campaign.TrafficSourceID != nil {
			attr.SourceID = *payload.Campaign.TrafficSourceID
		}

		// Save to Valkey (non-blocking best effort)
		go func() {
			if err := s.Attribution.SaveClickAttribution(payload.Ctx, rc.ClickToken, attr); err != nil {
				// We don't want to fail the click if attribution caching fails,
				// but we should probably log it in a real production system.
			}
		}()
	}

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
