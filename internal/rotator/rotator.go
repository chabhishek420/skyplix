package rotator

import (
	"math/rand/v2"

	"github.com/google/uuid"
)

// Item is anything with a weight (stream, landing, offer).
type Item interface {
	GetWeight() int
	GetID() uuid.UUID
}

// Rotator handles weighted selection.
type Rotator struct{}

// New creates a new Rotator.
func New() *Rotator {
	return &Rotator{}
}

// Pick selects one item from a weighted list.
// Returns nil if items is empty.
func (r *Rotator) Pick(items []interface{}) interface{} {
	if len(items) == 0 {
		return nil
	}
	if len(items) == 1 {
		return items[0]
	}

	weights := make([]int, len(items))
	totalWeight := 0
	for i, item := range items {
		w := 1
		if weighted, ok := item.(Item); ok {
			w = weighted.GetWeight()
		}
		if w <= 0 {
			w = 1 // Treat 0 or negative weight as 1 for equal probability
		}
		weights[i] = w
		totalWeight += w
	}

	idx := PickIndex(weights, totalWeight)
	if idx < 0 || idx >= len(items) {
		return items[0]
	}
	return items[idx]
}

// PickIndex returns the index of the selected item given its weights and total.
func PickIndex(weights []int, totalWeight int) int {
	if totalWeight <= 0 {
		return 0
	}

	// Use math/rand/v2 for fast scalable randomness without locks
	target := rand.IntN(totalWeight)

	current := 0
	for i, w := range weights {
		current += w
		if target < current {
			return i
		}
	}

	return 0
}

// Generic Pick for typed slices
func PickTyped[T Item](items []T) T {
	if len(items) == 0 {
		var zero T
		return zero
	}
	if len(items) == 1 {
		return items[0]
	}

	weights := make([]int, len(items))
	totalWeight := 0
	for i, item := range items {
		w := item.GetWeight()
		if w <= 0 {
			w = 1
		}
		weights[i] = w
		totalWeight += w
	}

	idx := PickIndex(weights, totalWeight)
	return items[idx]
}
