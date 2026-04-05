package rotator

import (
	"sort"

	"github.com/skyplix/zai-tds/internal/analytics"
)

// Optimizer handles Multi-Armed Bandit (MAB) weight calculation.
type Optimizer struct {
	// Epsilon is the probability of exploration (e.g., 0.1 for 10% exploration).
	Epsilon float64
}

// NewOptimizer creates a new MAB optimizer.
func NewOptimizer(epsilon float64) *Optimizer {
	if epsilon <= 0 || epsilon > 1 {
		epsilon = 0.1 // Default 10%
	}
	return &Optimizer{Epsilon: epsilon}
}

// CalculateWeights assigns new weights to streams based on their performance using Epsilon-Greedy.
// - Exploitation: 100 * (1 - Epsilon) % weight to the best stream.
// - Exploration: 100 * Epsilon % weight distributed among all streams.
func (o *Optimizer) CalculateWeights(perf map[string]analytics.StreamPerformance) map[string]int {
	if len(perf) == 0 {
		return nil
	}

	results := make(map[string]int)

	// 1. Find the best performer based on EPC (Earnings Per Click)
	var bestID string
	var maxEPC float64 = -1

	// Sort IDs for deterministic behavior in tests
	var ids []string
	for id := range perf {
		ids = append(ids, id)
	}
	sort.Strings(ids)

	for _, id := range ids {
		p := perf[id]
		if p.EPC > maxEPC {
			maxEPC = p.EPC
			bestID = id
		}
	}

	// 2. Distribute weights (Total weight = 1000 for precision)
	const totalWeight = 1000
	explorationWeight := int(float64(totalWeight) * o.Epsilon)
	exploitationWeight := totalWeight - explorationWeight

	// Each stream gets an equal share of the exploration weight
	share := explorationWeight / len(perf)
	remainder := explorationWeight % len(perf)

	for _, id := range ids {
		results[id] = share
	}

	// Add exploitation weight to the best stream
	results[bestID] += exploitationWeight

	// Distribute remainder to the best stream as well
	results[bestID] += remainder

	return results
}
