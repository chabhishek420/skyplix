package filter

import (
	"github.com/skyplix/zai-tds/internal/model"
)

type AnyParamFilter struct{}
func (f *AnyParamFilter) Type() string { return "AnyParam" }
func (f *AnyParamFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	// Any sub_id is non-empty
	return rc.SubID1 != "" || rc.SubID2 != "" || rc.SubID3 != "" || rc.SubID4 != "" || rc.SubID5 != ""
}

type ParameterFilter struct{}
func (f *ParameterFilter) Type() string { return "Parameter" }
func (f *ParameterFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	paramName, _ := payload["name"].(string)
	var val string
	switch paramName {
	case "sub_id_1": val = rc.SubID1
	case "sub_id_2": val = rc.SubID2
	case "sub_id_3": val = rc.SubID3
	case "sub_id_4": val = rc.SubID4
	case "sub_id_5": val = rc.SubID5
	}
	return matchIncludeExclude(val, payload)
}
