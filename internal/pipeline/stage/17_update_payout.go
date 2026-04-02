package stage

import (
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// UpdatePayoutStage sets the click payout based on offer and network settings.
type UpdatePayoutStage struct{}

func (s *UpdatePayoutStage) Name() string      { return "UpdatePayout" }
func (s *UpdatePayoutStage) AlwaysRun() bool { return false }

func (s *UpdatePayoutStage) Process(p *pipeline.Payload) error {
	if p.Offer != nil {
		// Offer payout is the base.
		// ChooseOffer (Stage 12) already sets this initially from model.
		p.RawClick.Payout = p.Offer.Payout
	}

	// Future: add network-level payout overrides if AffiliateNetwork includes rate mapping
	
	return nil
}
