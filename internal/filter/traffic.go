package filter

import (
	"regexp"
	"strings"

	"github.com/skyplix/zai-tds/internal/model"
)

type ReferrerFilter struct{}
func (f *ReferrerFilter) Type() string { return "Referrer" }
func (f *ReferrerFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if val, ok := payload["match"].(string); ok && val != "" {
		if strings.HasPrefix(val, "/") && strings.HasSuffix(val, "/") {
			// Regex match
			pattern := val[1 : len(val)-1]
			re, err := regexp.Compile(pattern)
			if err != nil {
				return true // Ignore invalid regex
			}
			return re.MatchString(rc.Referrer)
		}
		// Substring match
		return strings.Contains(rc.Referrer, val)
	}
	return true
}

type EmptyReferrerFilter struct{}
func (f *EmptyReferrerFilter) Type() string { return "EmptyReferrer" }
func (f *EmptyReferrerFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if val, ok := payload["empty"].(bool); ok {
		return (rc.Referrer == "") == val
	}
	return true
}

type LanguageFilter struct{}
func (f *LanguageFilter) Type() string { return "Language" }
func (f *LanguageFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIncludeExclude(rc.Language, payload)
}

type UserAgentFilter struct{}
func (f *UserAgentFilter) Type() string { return "UserAgent" }
func (f *UserAgentFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if val, ok := payload["match"].(string); ok && val != "" {
		return strings.Contains(strings.ToLower(rc.UserAgent), strings.ToLower(val))
	}
	return true
}
