package stage

import (
	"fmt"

	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/session"
)

// SaveUniquenessSessionStage persists the current session state and binding to Valkey.
type SaveUniquenessSessionStage struct {
	Session *session.Service
}

func (s *SaveUniquenessSessionStage) Name() string      { return "SaveUniquenessSession" }
func (s *SaveUniquenessSessionStage) AlwaysRun() bool { return false }

func (s *SaveUniquenessSessionStage) Process(p *pipeline.Payload) error {
	if p.VisitorCode == "" {
		return nil
	}

	// 1. Save standard session hash
	data := map[string]string{
		"campaign_id": p.Campaign.ID.String(),
		"is_unique":   fmt.Sprintf("%v", p.RawClick.IsUniqueCampaign),
	}
	if p.Stream != nil {
		data["stream_id"] = p.Stream.ID.String()
	}
	_ = s.Session.SaveSession(p.Ctx, p.VisitorCode, data)

	// 2. Handle Entity Binding (Phase 2.5)
	if p.Campaign.BindVisitors && p.Stream != nil {
		// Only bind if not already bound in this request (check context)
		if val := p.Ctx.Value("bound_stream_id"); val == nil {
			_ = s.Session.BindToStream(p.Ctx, p.VisitorCode, p.Campaign.ID, p.Stream.ID)
		}
	}

	return nil
}
