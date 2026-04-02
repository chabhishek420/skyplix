package stage

import (
	"strconv"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// UpdateCostsStage parses cost/cpc values from query params.
type UpdateCostsStage struct{}

func (s *UpdateCostsStage) Name() string      { return "UpdateCosts" }
func (s *UpdateCostsStage) AlwaysRun() bool { return false }

func (s *UpdateCostsStage) Process(p *pipeline.Payload) error {
	query := p.Request.URL.Query()
	
	// Read from standard TDS cost parameters
	costStr := query.Get("cost")
	if costStr == "" {
		costStr = query.Get("cpc")
	}

	if costStr != "" {
		if val, err := strconv.ParseFloat(costStr, 64); err == nil {
			p.RawClick.Cost = val
		}
	}

	return nil
}
