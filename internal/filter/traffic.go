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

type ReferrerStopwordFilter struct{}
func (f *ReferrerStopwordFilter) Type() string { return "ReferrerStopword" }
func (f *ReferrerStopwordFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	stopwords, ok := payload["stopwords"].([]interface{})
	if !ok || len(stopwords) == 0 {
		return true
	}

	target := strings.ToLower(rc.Referrer)
	for _, v := range stopwords {
		if s, ok := v.(string); ok && s != "" {
			if strings.Contains(target, strings.ToLower(s)) {
				return false // Blocked
			}
		}
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

type UrlTokenFilter struct{}
func (f *UrlTokenFilter) Type() string { return "UrlToken" }
func (f *UrlTokenFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	tokens, ok := payload["blocked_params"].([]interface{})
	if !ok || len(tokens) == 0 {
		return true
	}

	query := strings.ToLower(rc.RawQuery)
	for _, v := range tokens {
		if s, ok := v.(string); ok && s != "" {
			pattern := strings.ToLower(s)
			if strings.Contains(query, pattern+"=") || strings.HasPrefix(query, pattern+"=") || strings.Contains(query, "&"+pattern+"=") {
				return false // Blocked
			}
		}
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
