package stage

import (
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/cookie"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/session"
)

// UpdateCampaignUniquenessStage checks if this visitor has visited this campaign before.
type UpdateCampaignUniquenessStage struct {
	Session *session.Service
	Logger  *zap.Logger
}

func (s *UpdateCampaignUniquenessStage) Name() string    { return "UpdateCampaignUniqueness" }
func (s *UpdateCampaignUniquenessStage) AlwaysRun() bool { return false }

func (s *UpdateCampaignUniquenessStage) Process(p *pipeline.Payload) error {
	if p.Campaign == nil {
		return nil
	}

	// Ensure VisitorCode is set
	if p.VisitorCode == "" {
		code, _ := cookie.GetOrCreateVisitorCode(p.Request)
		p.VisitorCode = code
	}

	// Check campaign uniqueness
	isUnique, err := s.Session.CheckCampaignUniqueness(p.Ctx, p.VisitorCode, p.Campaign.ID)
	if err != nil {
		s.Logger.Error("check campaign uniqueness error", zap.Error(err), zap.String("visitor", p.VisitorCode))
		p.RawClick.IsUniqueCampaign = false
		return nil
	}

	p.RawClick.IsUniqueCampaign = isUnique
	return nil
}
