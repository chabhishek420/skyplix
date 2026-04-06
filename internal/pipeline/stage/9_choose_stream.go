/*
 * MODIFIED: internal/pipeline/stage/9_choose_stream.go
 * PURPOSE: Implemented 3-tier stream selection. Fixed memory safety bug
 *          by using heap-escaping copies for the selected stream.
 */
package stage

import (
	"encoding/json"
	"fmt"
	"net/http"
	"sort"

	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/binding"
	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/filter"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/redis/go-redis/v9"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/rotator"
)

// ChooseStreamStage implementing the 3-tier stream selection algorithm.
type ChooseStreamStage struct {
	Cache   *cache.Cache
	Filter  *filter.Engine
	Rotator *rotator.Rotator
	Binding *binding.Service
	Valkey  *redis.Client
	Logger  *zap.Logger
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
			p.AddTrace("Stream [%s] matched (FORCED)", streams[idx].Name)
			s.selectAndBind(p, &streams[idx])
			return nil
		}
		p.AddTrace("Stream [%s] filter mismatch (FORCED)", streams[idx].Name)
	}

	// Tier 2 — REGULAR
	if len(regularIdx) > 0 {
		if p.Campaign.Type == model.CampaignTypePosition {
			sort.Slice(regularIdx, func(i, j int) bool { return streams[regularIdx[i]].Position < streams[regularIdx[j]].Position })
			for _, idx := range regularIdx {
				if s.Filter.MatchAll(p.RawClick, streams[idx].Filters) {
					p.AddTrace("Stream [%s] matched (REGULAR POSITION)", streams[idx].Name)
					s.selectAndBind(p, &streams[idx])
					return nil
				}
				p.AddTrace("Stream [%s] filter mismatch (REGULAR POSITION)", streams[idx].Name)
			}
		} else if p.Campaign.Type == model.CampaignTypeWeight {
			var matching []interface{}
			var matchingIdx []int
			for _, idx := range regularIdx {
				if s.Filter.MatchAll(p.RawClick, streams[idx].Filters) {
					matching = append(matching, &streams[idx])
					matchingIdx = append(matchingIdx, idx)
				}
			}

			if len(matching) > 0 {
				// MAB Optimization check (Phase 8)
				if p.Campaign.IsOptimizationEnabled && s.Valkey != nil {
					key := fmt.Sprintf("optimized_weights:%s", p.Campaign.ID)
					val, err := s.Valkey.Get(p.Ctx, key).Result()
					if err == nil && val != "" {
						var optWeights map[string]int
						if err := json.Unmarshal([]byte(val), &optWeights); err == nil {
							// Build weighted list based on MAB results
							weights := make([]int, len(matching))
							total := 0
							for i, idx := range matchingIdx {
								w, ok := optWeights[streams[idx].ID.String()]
								if !ok || w <= 0 {
									w = 1 // Exploration fallback
								}
								weights[i] = w
								total += w
							}

							selIdx := rotator.PickIndex(weights, total)
							selected := matching[selIdx].(*model.Stream)
							s.selectAndBind(p, selected)
							return nil
						}
					}
				}

				// Standard weighted rotation fallback
				selected := s.Rotator.Pick(matching).(*model.Stream)
				s.selectAndBind(p, selected)
				return nil
			}
		}
	}

	// Tier 3 — DEFAULT
	if defIdx != -1 {
		p.AddTrace("Using default stream [%s]", streams[defIdx].Name)
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
	p.Stream = &selected
	p.RawClick.StreamID = selected.ID
	p.RawClick.CampaignID = selected.CampaignID

	if p.Campaign.BindVisitors && p.VisitorCode != "" && s.Binding != nil {
		s.Binding.SetBinding(p.Ctx, p.VisitorCode, "stream", p.Campaign.ID, selected.ID)
	}
}
