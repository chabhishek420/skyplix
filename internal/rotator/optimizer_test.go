package rotator

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/skyplix/zai-tds/internal/analytics"
)

func TestOptimizer_CalculateWeights(t *testing.T) {
	o := NewOptimizer(0.1) // 10% exploration

	perf := map[string]analytics.StreamPerformance{
		"s1": {StreamID: "s1", Clicks: 100, Revenue: 50.0, EPC: 0.5},   // Winner
		"s2": {StreamID: "s2", Clicks: 100, Revenue: 10.0, EPC: 0.1},   // Loser
		"s3": {StreamID: "s3", Clicks: 100, Revenue: 20.0, EPC: 0.2},   // Runner-up
	}

	weights := o.CalculateWeights(perf)

	// Total weight should be 1000
	total := 0
	for _, w := range weights {
		total += w
	}
	assert.Equal(t, 1000, total)

	// Winner (s1) should have ~900 (exploitation) + share of 100 (exploration)
	// Exploration: 100 / 3 = 33 each, remainder 1 to winner
	// s1: 900 + 33 + 1 = 934
	// s2: 33
	// s3: 33
	assert.Equal(t, 934, weights["s1"])
	assert.Equal(t, 33, weights["s2"])
	assert.Equal(t, 33, weights["s3"])
}

func TestOptimizer_CalculateWeights_SingleStream(t *testing.T) {
	o := NewOptimizer(0.2)
	perf := map[string]analytics.StreamPerformance{
		"s1": {StreamID: "s1", Clicks: 10, Revenue: 1.0, EPC: 0.1},
	}

	weights := o.CalculateWeights(perf)
	assert.Equal(t, 1000, weights["s1"])
}
