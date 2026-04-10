package filter

import (
	"github.com/skyplix/zai-tds/internal/model"
)

type CountryFilter struct{}
func (f *CountryFilter) Type() string { return "Country" }
func (f *CountryFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	// Special case: country names are sometimes used instead of codes in UI
	return matchIncludeExclude(rc.CountryCode, payload)
}

type RegionFilter struct{}
func (f *RegionFilter) Type() string { return "Region" }
func (f *RegionFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIncludeExclude(rc.Region, payload)
}

type CityFilter struct{}
func (f *CityFilter) Type() string { return "City" }
func (f *CityFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIncludeExclude(rc.City, payload)
}
