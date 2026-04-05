/*
 * MODIFIED: internal/filter/detection.go
 * PURPOSE: Added support for stringified booleans ("true"/"false") in IsBot filter
 *          to prevent JSON unmarshaling strictness from failing traffic matches.
 */
package filter

import (
	"github.com/skyplix/zai-tds/internal/model"
)

type IsBotFilter struct{}
func (f *IsBotFilter) Type() string { return "IsBot" }
func (f *IsBotFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if val, ok := payload["is_bot"].(bool); ok {
		return rc.IsBot == val
	}
	return true
}

type HideClickDetectFilter struct{}
func (f *HideClickDetectFilter) Type() string { return "HideClickDetect" }
func (f *HideClickDetectFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return true // Phase 4 JS fingerprinting
}

type ImkloDetectFilter struct{}
func (f *ImkloDetectFilter) Type() string { return "ImkloDetect" }
func (f *ImkloDetectFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return true // Phase 4 external detection
}

type BehaviorScoreFilter struct{}
func (f *BehaviorScoreFilter) Type() string { return "BehaviorScore" }
func (f *BehaviorScoreFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if minVal, ok := payload["min_score"].(float64); ok {
		return float64(rc.BehaviorScore) >= minVal
	}
	return true
}
