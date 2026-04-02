package filter

import (
	"time"

	"github.com/skyplix/zai-tds/internal/model"
)

type UniquenessFilter struct{}
func (f *UniquenessFilter) Type() string { return "Uniqueness" }
func (f *UniquenessFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if val, ok := payload["scope"].(string); ok {
		switch val {
		case "campaign":
			return rc.IsUniqueCampaign
		case "stream":
			return rc.IsUniqueStream
		case "global":
			return rc.IsUniqueGlobal
		}
	}
	return true
}

type LimitFilter struct{}
func (f *LimitFilter) Type() string { return "Limit" }
func (f *LimitFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	// Enforcement happens in UpdateHitLimit stage.
	// This filter is a stub for the engine to register.
	return true
}

type IntervalFilter struct{}
func (f *IntervalFilter) Type() string { return "Interval" }
func (f *IntervalFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if from, ok := payload["from"].(string); ok {
		if to, ok := payload["to"].(string); ok {
			now := time.Now().Format("15:04:05")
			return now >= from && now <= to
		}
	}
	return true
}
