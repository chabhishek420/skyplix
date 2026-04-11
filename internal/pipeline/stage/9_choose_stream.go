/*
 * MODIFIED: internal/pipeline/stage/9_choose_stream.go
 * PURPOSE: Implemented 3-tier stream selection. Fixed memory safety bug
 *          by using heap-escaping copies for the selected stream.
 */
package stage

import (
	"net/http"
	"sort"
	"strings"

	"github.com/google/uuid"
	"go.uber.org/zap"

	actionpkg "github.com/skyplix/zai-tds/internal/action"
	"github.com/skyplix/zai-tds/internal/binding"
	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/filter"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/rotator"
)

// ChooseStreamStage implementing the 3-tier stream selection algorithm.
type ChooseStreamStage struct {
	Cache   *cache.Cache
	Filter  *filter.Engine
	Rotator *rotator.Rotator
	Binding *binding.Service
	Logger  *zap.Logger
	// BadTrafficAction is the global fallback action for bot-flagged traffic.
	// Stream-level override is supported via action_payload.bad_traffic_action.
	BadTrafficAction string
}

func (s *ChooseStreamStage) Name() string    { return "ChooseStream" }
func (s *ChooseStreamStage) AlwaysRun() bool { return false }

func (s *ChooseStreamStage) Process(p *pipeline.Payload) error {
	if p.Campaign == nil {
		return nil
	}

	streams, err := s.Cache.GetStreamsByCampaign(p.Ctx, p.Campaign.ID)
	if err != nil {
		s.Logger.Error("get campaigns streams error", zap.Error(err))
		p.Abort = true
		p.Response = &pipeline.Response{StatusCode: http.StatusInternalServerError}
		return nil
	}

	if len(streams) == 0 {
		p.Abort = true
		p.Response = &pipeline.Response{StatusCode: http.StatusNotFound}
		return nil
	}

	// Check entity binding first (Phase 2.5)
	if p.Campaign.BindVisitors && p.VisitorCode != "" && s.Binding != nil {
		boundID, err := s.Binding.GetBinding(p.Ctx, p.VisitorCode, "stream", p.Campaign.ID)
		if err == nil && boundID != uuid.Nil {
			for i, st := range streams {
				if st.ID == boundID && st.State == "active" {
					s.selectAndBind(p, &streams[i])
					return nil
				}
			}
		}
	}

	// Separate into tiers
	var forcedIdx, regularIdx []int
	var defIdx = -1

	for i, st := range streams {
		switch st.Type {
		case model.StreamTypeForced:
			forcedIdx = append(forcedIdx, i)
		case model.StreamTypeRegular:
			regularIdx = append(regularIdx, i)
		case model.StreamTypeDefault:
			defIdx = i
		}
	}

	sort.Slice(forcedIdx, func(i, j int) bool { return streams[forcedIdx[i]].Position < streams[forcedIdx[j]].Position })

	// Tier 1 — FORCED
	for _, idx := range forcedIdx {
		if s.Filter.MatchAll(p.RawClick, streams[idx].Filters) {
			s.selectAndBind(p, &streams[idx])
			return nil
		}
	}

	// Tier 2 — REGULAR
	if len(regularIdx) > 0 {
		if p.Campaign.Type == model.CampaignTypePosition {
			sort.Slice(regularIdx, func(i, j int) bool { return streams[regularIdx[i]].Position < streams[regularIdx[j]].Position })
			for _, idx := range regularIdx {
				if s.Filter.MatchAll(p.RawClick, streams[idx].Filters) {
					s.selectAndBind(p, &streams[idx])
					return nil
				}
			}
		} else if p.Campaign.Type == model.CampaignTypeWeight {
			var matching []interface{}
			for _, idx := range regularIdx {
				if s.Filter.MatchAll(p.RawClick, streams[idx].Filters) {
					matching = append(matching, &streams[idx])
				}
			}
			if len(matching) > 0 {
				selected := s.Rotator.Pick(matching).(*model.Stream)
				s.selectAndBind(p, selected)
				return nil
			}
		}
	}

	// Tier 3 — DEFAULT
	if defIdx != -1 {
		s.selectAndBind(p, &streams[defIdx])
		return nil
	}

	p.Abort = true
	p.Response = &pipeline.Response{StatusCode: http.StatusNotFound}
	return nil
}

func (s *ChooseStreamStage) selectAndBind(p *pipeline.Payload, stream *model.Stream) {
	// Create a heap-escaping copy to ensure the pointer survives pipeline transitions
	selected := *stream
	if p.RawClick != nil && p.RawClick.IsBot {
		s.applyBadTrafficPolicy(&selected)
	}

	p.Stream = &selected
	p.RawClick.StreamID = selected.ID
	p.RawClick.CampaignID = selected.CampaignID

	if p.Campaign != nil && p.Campaign.BindVisitors && p.VisitorCode != "" && s.Binding != nil {
		s.Binding.SetBinding(p.Ctx, p.VisitorCode, "stream", p.Campaign.ID, selected.ID)
	}
}

func (s *ChooseStreamStage) applyBadTrafficPolicy(stream *model.Stream) {
	if stream == nil {
		return
	}

	if stream.ActionPayload != nil {
		if override, ok := stream.ActionPayload["bad_traffic_action"].(string); ok && strings.TrimSpace(override) != "" {
			stream.ActionType = strings.TrimSpace(override)
			return
		}
	}

	if isSafeTrafficAction(stream.ActionType) {
		return
	}

	actionType := strings.TrimSpace(s.BadTrafficAction)
	if actionType == "" {
		actionType = "Status404"
	}
	stream.ActionType = actionType
}

func isSafeTrafficAction(actionType string) bool {
	switch actionpkg.CanonicalKey(actionType) {
	case "safepage", "status404":
		return true
	default:
		return false
	}
}
