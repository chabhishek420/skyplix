/*
 * MODIFIED: internal/pipeline/stage/12_choose_offer.go
 * PURPOSE: Selected offer from weighted list. Fixed pointer bug 
 *          by using heap-escaping copies for the selected offer.
 *          Updated names to rotator.Rotator and binding.Service.
 */
package stage

import (
	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/binding"
	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/rotator"
)

// ChooseOfferStage selects an offer from the stream's weighted offers.
type ChooseOfferStage struct {
	Cache   *cache.Cache
	Rotator *rotator.Rotator
	Binding *binding.Service
	Logger  *zap.Logger
}

func (s *ChooseOfferStage) Name() string    { return "ChooseOffer" }
func (s *ChooseOfferStage) AlwaysRun() bool { return false }

func (s *ChooseOfferStage) Process(p *pipeline.Payload) error {
	if p.Abort || p.Stream == nil {
		return nil
	}

	// 1. Check for Visitor Binding
	if p.Campaign.BindVisitors && p.VisitorCode != "" && s.Binding != nil {
		boundID, err := s.Binding.GetBinding(p.Ctx, p.VisitorCode, "offer", p.Campaign.ID)
		if err == nil && boundID != uuid.Nil {
			offers, _ := s.Cache.GetOffersByStream(p.Ctx, p.Stream.ID)
			for _, wo := range offers {
				if wo.Offer.ID == boundID {
					// Use heap-escaping copy
					selected := wo.Offer
					p.Offer = &selected
					p.RawClick.OfferID = selected.ID
					p.RawClick.Payout = selected.Payout
					return nil
				}
			}
		}
	}

	// 2. Load and Pick
	offers, err := s.Cache.GetOffersByStream(p.Ctx, p.Stream.ID)
	if err != nil {
		s.Logger.Error("get stream offers error", zap.Error(err), zap.String("stream", p.Stream.ID.String()))
		return nil
	}
	if len(offers) == 0 {
		return nil
	}

	var items []interface{}
	for i := range offers {
		items = append(items, &offers[i])
	}
	off := s.Rotator.Pick(items).(*model.WeightedOffer).Offer

	// 3. Update payload with heap-escaping copy
	selected := off
	p.Offer = &selected
	p.RawClick.OfferID = selected.ID
	p.RawClick.Payout = selected.Payout

	// 4. Bind
	if p.Campaign != nil && p.Campaign.BindVisitors && p.VisitorCode != "" && s.Binding != nil {
		s.Binding.SetBinding(p.Ctx, p.VisitorCode, "offer", p.Stream.ID, selected.ID)
	}

	return nil
}
