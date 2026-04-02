package stage

import (
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// CheckParamAliasesStage resolves traffic source parameter aliases into standard sub_id fields.
type CheckParamAliasesStage struct {
	Cache  *cache.Cache
	Logger *zap.Logger
}

func (s *CheckParamAliasesStage) Name() string      { return "CheckParamAliases" }
func (s *CheckParamAliasesStage) AlwaysRun() bool { return false }

func (s *CheckParamAliasesStage) Process(p *pipeline.Payload) error {
	if p.Campaign == nil || p.Campaign.TrafficSourceID == nil {
		return nil
	}

	// In a real implementation, we would load the traffic source from cache
	// and map its parameters.
	return nil
}
