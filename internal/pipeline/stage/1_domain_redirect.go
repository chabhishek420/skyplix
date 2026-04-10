package stage

import (
	"github.com/go-chi/chi/v5"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// DomainRedirectStage — Pipeline Stage 1
// Extracts the campaign alias from the URL path.
// Handles the "gateway context" — bare domain access (GET /).
type DomainRedirectStage struct{}

func (s *DomainRedirectStage) AlwaysRun() bool { return false }
func (s *DomainRedirectStage) Name() string { return "DomainRedirect" }

func (s *DomainRedirectStage) Process(payload *pipeline.Payload) error {
	alias := chi.URLParam(payload.Request, "alias")

	// Support query parameter resolution for bare domain/script hits
	if alias == "" {
		rawQuery := payload.Request.URL.RawQuery
		alias = getQueryParam(rawQuery, "campaign_id", "alias")
		if alias == "" {
			alias = getQueryParam(rawQuery, "id")
		}
	}

	if payload.RawClick == nil {
		payload.RawClick = &model.RawClick{}
	}
	payload.RawClick.CampaignAlias = alias
	return nil
}
