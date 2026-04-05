/*
 * NEW: internal/pipeline/stage/12_save_lp_token.go
 * PURPOSE: Persists the click context (Campaign/Stream/Token) for Level 2
 *          routing when a landing page is involved. Enables L2 resolution.
 */
package stage

import (
	"github.com/skyplix/zai-tds/internal/lptoken"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/session"
)

// SaveLPTokenStage — Pipeline Stage 12.5
// Saves the current click context to Valkey using the click token.
// This is required for Landings to resolve the Offer on the second click.
type SaveLPTokenStage struct {
	LPToken *lptoken.Service
	Session *session.Service
}

func (s *SaveLPTokenStage) Name() string    { return "SaveLPToken" }
func (s *SaveLPTokenStage) AlwaysRun() bool { return false }

func (s *SaveLPTokenStage) Process(p *pipeline.Payload) error {
	if p.RawClick == nil || p.RawClick.ClickToken == "" || p.Landing == nil {
		return nil
	}

	// 1. Save LP context for L2 resolution
	ctx := &lptoken.LPContext{
		CampaignID:  p.Campaign.ID,
		StreamID:    p.Stream.ID,
		VisitorCode: p.VisitorCode,
		SubID1:      p.RawClick.SubID1,
		SubID2:      p.RawClick.SubID2,
		SubID3:      p.RawClick.SubID3,
		SubID4:      p.RawClick.SubID4,
		SubID5:      p.RawClick.SubID5,
	}

	if err := s.LPToken.Save(p.Ctx, p.RawClick.ClickToken, ctx); err != nil {
		return err
	}

	// 2. Save full click snapshot for behavioral continuity/persistence
	if s.Session != nil {
		return s.Session.SaveClickSnapshot(p.Ctx, p.RawClick.ClickToken, p.RawClick)
	}

	return nil
}
