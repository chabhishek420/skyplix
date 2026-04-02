package stage

import (
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// FindCampaignStage — Pipeline Stage 4
// Looks up the campaign from the Valkey cache by alias.
// Falls back to gateway context (domain→campaign mapping) if no alias.
type FindCampaignStage struct {
	Cache  *cache.Cache
	Logger *zap.Logger
}

func (s *FindCampaignStage) AlwaysRun() bool { return false }
func (s *FindCampaignStage) Name() string    { return "FindCampaign" }

func (s *FindCampaignStage) Process(payload *pipeline.Payload) error {
	alias := payload.RawClick.CampaignAlias
	if alias == "" {
		// Gateway context: bare domain → campaign mapping
		campaign, err := s.Cache.GetCampaignByDomain(payload.Ctx, payload.Request.Host)
		if err != nil {
			s.Logger.Error("gateway domain lookup failed", zap.String("host", payload.Request.Host), zap.Error(err))
		}
		if campaign != nil {
			payload.Campaign = campaign
			payload.RawClick.CampaignID = campaign.ID
			payload.RawClick.CampaignAlias = campaign.Alias
			return nil
		}
		payload.Abort = true
		payload.AbortCode = 404
		return nil
	}

	campaign, err := s.Cache.GetCampaignByAlias(payload.Ctx, alias)
	if err != nil {
		s.Logger.Error("campaign lookup failed", zap.String("alias", alias), zap.Error(err))
		payload.Abort = true
		payload.AbortCode = 500
		return nil
	}

	if campaign == nil {
		s.Logger.Debug("campaign not found", zap.String("alias", alias))
		payload.Abort = true
		payload.AbortCode = 404
		return nil
	}

	payload.Campaign = campaign
	payload.RawClick.CampaignID = campaign.ID
	return nil
}
