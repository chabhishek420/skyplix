package stage

import (
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// UpdateCostsStage parses cost/cpc values from query params.
// Optimized to use zero-allocation getQueryParam scanner.
type UpdateCostsStage struct{}

func (s *UpdateCostsStage) Name() string      { return "UpdateCosts" }
func (s *UpdateCostsStage) AlwaysRun() bool { return false }

func (s *UpdateCostsStage) Process(p *pipeline.Payload) error {
	if p.RawClick == nil {
		return nil
	}

	// Read from standard TDS cost parameters using optimized scanner
	costStr := getQueryParam(p.RawClick.RawQuery, "cost", "cpc")

	if costStr != "" {
		var cost float64
		if _, err := parseFloat(costStr, &cost); err == nil {
			p.RawClick.Cost = cost
		}
	}

	return nil
}
