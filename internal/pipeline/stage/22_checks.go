package stage

import (
	"fmt"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// CheckSendingToAnotherCampaignStage — Pipeline Stage 22
// Verified re-dispatch status and log campaign transitions.
type CheckSendingToAnotherCampaignStage struct{}

func (s *CheckSendingToAnotherCampaignStage) Name() string      { return "CheckSendingToAnotherCampaign" }
func (s *CheckSendingToAnotherCampaignStage) AlwaysRun() bool   { return true }

func (s *CheckSendingToAnotherCampaignStage) Process(payload *pipeline.Payload) error {
	if payload.ReDispatch {
		// Log the transition for debug. In a production system, this could track 
		// "internal hops" metrics to ensure campaigns are not looping excessively.
		fmt.Printf("Redispatching from campaign %s to %s (Hop %d)\n", 
			payload.Campaign.Alias, payload.RawClick.CampaignAlias, payload.Hops+1)
	}
	return nil
}
