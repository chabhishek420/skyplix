package filter

import (
	"strings"

	"github.com/skyplix/zai-tds/internal/model"
)

type DeviceTypeFilter struct{}
func (f *DeviceTypeFilter) Type() string { return "DeviceType" }
func (f *DeviceTypeFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIncludeExclude(rc.DeviceType, payload)
}

type DeviceModelFilter struct{}
func (f *DeviceModelFilter) Type() string { return "DeviceModel" }
func (f *DeviceModelFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIncludeExclude(rc.DeviceModel, payload)
}

type BrowserFilter struct{}
func (f *BrowserFilter) Type() string { return "Browser" }
func (f *BrowserFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIncludeExclude(rc.Browser, payload)
}

type OsFilter struct{}
func (f *OsFilter) Type() string { return "Os" }
func (f *OsFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIncludeExclude(rc.OS, payload)
}

type BrowserVersionFilter struct{}
func (f *BrowserVersionFilter) Type() string { return "BrowserVersion" }
func (f *BrowserVersionFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchVersion(rc.BrowserVersion, payload)
}

type OsVersionFilter struct{}
func (f *OsVersionFilter) Type() string { return "OsVersion" }
func (f *OsVersionFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchVersion(rc.OSVersion, payload)
}

func matchVersion(val string, payload map[string]interface{}) bool {
	// Simple version matching for now.
	// Future phase: add semver/comparative ranges
	if include, ok := payload["include"].([]interface{}); ok && len(include) > 0 {
		for _, v := range include {
			if vs, ok := v.(string); ok && strings.HasPrefix(val, vs) {
				return true
			}
		}
		return false
	}
	return true
}
