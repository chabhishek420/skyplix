package stage

import (
	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// FindAffiliateNetworkStage fetches the affiliate network for the chosen offer.
type FindAffiliateNetworkStage struct {
	Cache *cache.Cache
}

func (s *FindAffiliateNetworkStage) Name() string      { return "FindAffiliateNetwork" }
func (s *FindAffiliateNetworkStage) AlwaysRun() bool { return false }

func (s *FindAffiliateNetworkStage) Process(p *pipeline.Payload) error {
	if p.Offer == nil || p.Offer.AffiliateNetworkID == nil {
		return nil
	}

	network, err := s.Cache.GetAffiliateNetwork(p.Ctx, *p.Offer.AffiliateNetworkID)
	if err == nil && network != nil {
		p.AffiliateNetwork = network
	}

	return nil
}
