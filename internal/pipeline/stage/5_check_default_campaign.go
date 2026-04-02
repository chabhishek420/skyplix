package stage

import (
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// CheckDefaultCampaignStage — Pipeline Stage 5
// If no campaign was found in stage 4 but there's a default campaign configured,
// fall back to it. Phase 1: simple abort if no campaign.
type CheckDefaultCampaignStage struct{}

func (s *CheckDefaultCampaignStage) AlwaysRun() bool { return false }
func (s *CheckDefaultCampaignStage) Name() string { return "CheckDefaultCampaign" }

func (s *CheckDefaultCampaignStage) Process(payload *pipeline.Payload) error {
	if payload.Campaign == nil {
		// No campaign found and no default — abort with 404
		payload.Abort = true
		payload.AbortCode = 404
	}
	return nil
}
