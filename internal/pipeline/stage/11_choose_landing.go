/*
 * MODIFIED: internal/pipeline/stage/11_choose_landing.go
 * PURPOSE: Selected landing page from weighted list. Fixed pointer bug
 *          by using heap-escaping copies for the selected landing.
 *          Updated names to rotator.Rotator and binding.Service.
 */
package stage

import (
	"fmt"
	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/binding"
	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/rotator"
)

// ChooseLandingStage selects a landing page for the click.
type ChooseLandingStage struct {
	Cache   *cache.Cache
	Rotator *rotator.Rotator
	Binding *binding.Service
	Logger  *zap.Logger
}

func (s *ChooseLandingStage) Name() string    { return "ChooseLanding" }
func (s *ChooseLandingStage) AlwaysRun() bool { return false }

func (s *ChooseLandingStage) Process(p *pipeline.Payload) error {
	if p.Abort || p.Stream == nil {
		return nil
	}

	// 1. Check for Visitor Binding
	if p.Campaign != nil && p.Campaign.BindVisitors && p.VisitorCode != "" && s.Binding != nil {
		boundID, err := s.Binding.GetBinding(p.Ctx, p.VisitorCode, "landing", p.Campaign.ID)
		if err == nil && boundID != uuid.Nil {
			landings, err := s.Cache.GetLandingsByStream(p.Ctx, p.Stream.ID)
			if err == nil {
				for _, wl := range landings {
					if wl.Landing.ID == boundID && wl.Landing.State == "active" {
						selected := wl.Landing
						p.Landing = &selected
						p.RawClick.LandingID = selected.ID
						return nil
					}
				}
			}
		}
	}

	// 2. Load and Pick
	landings, err := s.Cache.GetLandingsByStream(p.Ctx, p.Stream.ID)
	if err != nil {
		return fmt.Errorf("choose landing: %w", err)
	}
	if len(landings) == 0 {
		return nil
	}

	var items []interface{}
	for i := range landings {
		items = append(items, &landings[i])
	}
	lnd := s.Rotator.Pick(items).(*model.WeightedLanding).Landing

	// 3. Update payload with heap-escaping copy
	selected := lnd
	p.Landing = &selected
	p.RawClick.LandingID = selected.ID

	// 4. Bind
	if p.Campaign != nil && p.Campaign.BindVisitors && p.VisitorCode != "" && s.Binding != nil {
		s.Binding.SetBinding(p.Ctx, p.VisitorCode, "landing", p.Campaign.ID, selected.ID)
	}

	return nil
}
