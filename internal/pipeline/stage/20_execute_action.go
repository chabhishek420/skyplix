package stage

import (
	"net/http"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// ExecuteActionStage — Pipeline Stage 20
// Executes the response action. Phase 1 implements HttpRedirect only.
// The redirect URL comes from:
//  1. Offer URL (if an offer was selected in Stage 12)
//  2. Hardcoded fallback for Phase 1 (when no offer selected yet)
type ExecuteActionStage struct {
	Logger *zap.Logger
}

func (s *ExecuteActionStage) Name() string { return "ExecuteAction" }

func (s *ExecuteActionStage) Process(payload *pipeline.Payload) error {
	var redirectURL string

	if payload.Offer != nil {
		redirectURL = payload.Offer.URL
	} else if payload.Campaign != nil {
		// Phase 1 fallback: no offer selected (stream selection in Phase 2)
		// Use a placeholder — Phase 2 will wire real offer rotation
		redirectURL = "https://example.com"
		s.Logger.Debug("no offer selected — using fallback URL",
			zap.String("campaign", payload.Campaign.Alias),
		)
	} else {
		payload.Abort = true
		payload.AbortCode = http.StatusNotFound
		return nil
	}

	payload.RawClick.ActionType = "HttpRedirect"

	// Send the redirect — set Abort to stop remaining stages from overwriting
	http.Redirect(payload.Writer, payload.Request, redirectURL, http.StatusFound)
	payload.Abort = true

	return nil
}
