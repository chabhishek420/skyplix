/*
 * MODIFIED: internal/filter/filter.go
 * PURPOSE: Implemented case-insensitive (Title Case) normalization for filter
 *          registration and lookups to ensure compatibility with various DB/UI sources.
 */
package filter

import (
	"strings"

	"golang.org/x/text/cases"
	"golang.org/x/text/language"

	"github.com/skyplix/zai-tds/internal/model"
)

// Filter evaluates whether a click matches a single filter condition.
type Filter interface {
	Type() string
	Match(click *model.RawClick, payload map[string]interface{}) bool
}

// Engine evaluates all filters for a stream against a click.
type Engine struct {
	filters map[string]Filter
}

// NewEngine creates and registers all 27 filter types.
func NewEngine() *Engine {
	e := &Engine{
		filters: make(map[string]Filter),
	}

	// Register all filter implementations
	e.register(
		// Geo (geo.go)
		&CountryFilter{}, &RegionFilter{}, &CityFilter{},
		// Device (device.go)
		&DeviceTypeFilter{}, &DeviceModelFilter{}, &BrowserFilter{}, &BrowserVersionFilter{}, &OsFilter{}, &OsVersionFilter{},
		// Network (network.go)
		&IpFilter{}, &Ipv6Filter{}, &IspFilter{}, &OperatorFilter{}, &ConnectionTypeFilter{}, &ProxyFilter{}, &IspBlacklistFilter{}, &TlsFingerprintFilter{},
		// Traffic (traffic.go)
		&ReferrerFilter{}, &EmptyReferrerFilter{}, &ReferrerStopwordFilter{}, &UrlTokenFilter{}, &LanguageFilter{}, &UserAgentFilter{},
		// Tracking (tracking.go)
		&UniquenessFilter{}, &LimitFilter{}, &IntervalFilter{},
		// Parameters (params.go)
		&AnyParamFilter{}, &ParameterFilter{},
		// Schedule (schedule.go)
		&ScheduleFilter{},
		// Detection (detection.go)
		&IsBotFilter{}, &HideClickDetectFilter{}, &ImkloDetectFilter{}, &BehaviorScoreFilter{},
	)

	return e
}

func (e *Engine) register(filters ...Filter) {
	for _, f := range filters {
		e.filters[cases.Title(language.Und).String(strings.ToLower(f.Type()))] = f
	}
}

// MatchAll evaluates ALL filters for a stream against a click (AND logic).
// If no filters are provided, it returns true (matches everything).
func (e *Engine) MatchAll(click *model.RawClick, filters []model.StreamFilter) bool {
	if len(filters) == 0 {
		return true
	}

	for _, sf := range filters {
		f, ok := e.filters[cases.Title(language.Und).String(strings.ToLower(sf.Type))]
		if !ok {
			// If we don't know the filter type, we skip it (doesn't fail the match).
			// This matches Keitaro's behavior for unknown filters.
			continue
		}

		if !f.Match(click, sf.Payload) {
			return false
		}
	}

	return true
}

func matchIncludeExclude(val string, payload map[string]interface{}) bool {
	if include, ok := payload["include"].([]interface{}); ok && len(include) > 0 {
		found := false
		for _, v := range include {
			if vs, ok := v.(string); ok && vs == val {
				found = true
				break
			}
		}
		if !found {
			return false
		}
	}

	if exclude, ok := payload["exclude"].([]interface{}); ok && len(exclude) > 0 {
		for _, v := range exclude {
			if vs, ok := v.(string); ok && vs == val {
				return false
			}
		}
	}

	return true
}
