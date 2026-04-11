/*
 * MODIFIED: internal/pipeline/stage/3_build_raw_click.go
 * PURPOSE: Extracts request data into RawClick. Added nil-check for
 *          RawClick initialization to prevent panics in L2 pipelines.
 */
package stage

import (
	"context"
	"fmt"
	"net"
	"net/netip"
	"net/url"
	"strings"
	"time"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// BuildRawClickStage — Pipeline Stage 3
// Extracts all request data into RawClick: IP, UA, referrer, sub_ids.
// ALSO runs inline basic bot detection per ADR-008:
//   - Empty UA check
//   - UA pattern match (known crawlers/bots)
//   - IP blocklist check (upgraded in Phase 4 with botdb)
//   - ?referrer= URL parameter override (if allow_change_referrer is enabled)
type BuildRawClickStage struct {
	BotDB       interface{ Contains(net.IP) bool }
	CustomUA    interface{ Patterns() []string }
	Geo         interface{ IsDatacenter(net.IP) bool }
	CIDRFilter  interface{ ContainsIP(netip.Addr) bool }
	RateLimiter interface {
		CheckIPLimit(context.Context, net.IP, int, time.Duration) (bool, int64, error)
	}
	IPRateLimit         int
	IPRateWindow        time.Duration
	AllowChangeReferrer bool
}

func (s *BuildRawClickStage) AlwaysRun() bool { return false }
func (s *BuildRawClickStage) Name() string    { return "BuildRawClick" }

func (s *BuildRawClickStage) Process(payload *pipeline.Payload) error {
	r := payload.Request
	if payload.RawClick == nil {
		payload.RawClick = &model.RawClick{}
	}
	rc := payload.RawClick

	// IP is now extracted in NormalizeIPStage

	// User Agent
	rc.UserAgent = r.Header.Get("User-Agent")

	// Referrer (initial - may be overridden below)
	rc.Referrer = r.Header.Get("Referer")

	// Sub IDs from query params (avoiding r.URL.Query() allocations)
	rawQuery := r.URL.RawQuery
	rc.RawQuery = rawQuery

	// ?referrer= URL param override (spoof referrer for affiliate tracking)
	if s.AllowChangeReferrer {
		if urlReferrer := getQueryParam(rawQuery, "referrer"); urlReferrer != "" {
			rc.Referrer = urlReferrer
		}
	}

	// Source attribution parity with Keitaro:
	// 1) source/src query param
	// 2) site alias query param
	// 3) fallback to referrer host
	if rc.Source == "" {
		if source := getQueryParam(rawQuery, "source", "src"); source != "" {
			rc.Source = source
		} else if site := getQueryParam(rawQuery, "site"); site != "" {
			rc.Source = site
		} else if refHost := extractReferrerHost(rc.Referrer); refHost != "" {
			rc.Source = refHost
		}
	}

	// Fast path for sub_ids using a manual query string scanner to avoid map[string][]string allocation
	rc.SubID1 = getQueryParam(rawQuery, "sub_id_1", "sub1")
	rc.SubID2 = getQueryParam(rawQuery, "sub_id_2", "sub2")
	rc.SubID3 = getQueryParam(rawQuery, "sub_id_3", "sub3")
	rc.SubID4 = getQueryParam(rawQuery, "sub_id_4", "sub4")
	rc.SubID5 = getQueryParam(rawQuery, "sub_id_5", "sub5")

	// Cost from query param
	if costStr := getQueryParam(rawQuery, "cost"); costStr != "" {
		var cost float64
		if _, err := parseFloat(costStr, &cost); err == nil {
			rc.Cost = cost
		}
	}

	// --- Inline Bot Detection (ADR-008) ---
	isBot, reason := s.detectBot(rc.IP, rc.UserAgent)
	rc.IsBot = isBot
	rc.BotReason = reason

	return nil
}

// detectBot runs the basic bot detection checks inline.
// Returns (true, reason) if any check triggers.
func (s *BuildRawClickStage) detectBot(ip net.IP, ua string) (bool, string) {
	// 1. Empty User-Agent
	if strings.TrimSpace(ua) == "" {
		return true, "empty_ua"
	}

	// 2. Known bot UA patterns (case-insensitive substring match)
	uaLower := strings.ToLower(ua)
	for _, pattern := range botUAPatterns {
		if strings.Contains(uaLower, pattern) {
			return true, fmt.Sprintf("ua_pattern:%s", pattern)
		}
	}

	// 3. Known bot IP ranges (hardcoded starter list — fallback/fast-path)
	if ip != nil {
		for _, prefix := range botIPPrefixes {
			if prefix.Contains(ip) {
				return true, fmt.Sprintf("ip_prefix:%s", prefix.String())
			}
		}
	}

	// 3.5 CIDR Blocklist
	if s.CIDRFilter != nil && ip != nil {
		if addr, ok := netip.AddrFromSlice(ip); ok {
			if s.CIDRFilter.ContainsIP(addr.Unmap()) {
				return true, "cidr_blocklist"
			}
		}
	}

	// 4. Advanced bot IP database (Phase 4 upgrade)
	if s.BotDB != nil && ip != nil {
		if s.BotDB.Contains(ip) {
			return true, "bot_db_ip"
		}
	}

	// 5. Custom UA signatures from Valkey/Admin
	if s.CustomUA != nil {
		customPatterns := s.CustomUA.Patterns()
		for _, pattern := range customPatterns {
			if strings.Contains(uaLower, pattern) {
				return true, fmt.Sprintf("custom_ua:%s", pattern)
			}
		}
	}

	// 6. Datacenter/VPN heuristic check (Phase 4 upgrade)
	if s.Geo != nil && ip != nil {
		if s.Geo.IsDatacenter(ip) {
			return true, "datacenter_asn"
		}
	}

	// 7. Per-IP Rate Limiting (Phase 4 upgrade)
	if s.RateLimiter != nil && ip != nil {
		limit := s.IPRateLimit
		if limit <= 0 {
			limit = 60 // Default 60 req/min
		}
		window := s.IPRateWindow
		if window <= 0 {
			window = time.Minute
		}

		// Use a 500ms timeout for Valkey/Network rate limit hit
		ctx, cancel := context.WithTimeout(context.Background(), 500*time.Millisecond)
		defer cancel()

		allowed, _, err := s.RateLimiter.CheckIPLimit(ctx, ip, limit, window)
		if err != nil {
			// Fail open on rate limiting error
			return false, ""
		}
		if !allowed {
			return true, "rate_limited"
		}
	}

	return false, ""
}

// getQueryParam manually parses the query string to find a value without allocating a map.
// It also performs URL unescaping to ensure data integrity.
func getQueryParam(query, key1 string, optionalKey2 ...string) string {
	if query == "" {
		return ""
	}

	val := findInQuery(query, key1)
	if val == "" && len(optionalKey2) > 0 {
		val = findInQuery(query, optionalKey2[0])
	}

	if val != "" {
		if decoded, err := url.QueryUnescape(val); err == nil {
			return decoded
		}
	}
	return val
}

func findInQuery(query, key string) string {
	pos := strings.Index(query, key+"=")
	if pos == -1 {
		return ""
	}

	// Ensure it's a full key match (start of string or preceded by &)
	if pos > 0 && query[pos-1] != '&' {
		// Substring match, continue search (simple version, could be more robust)
		return findInQuery(query[pos+len(key):], key)
	}

	start := pos + len(key) + 1
	end := strings.IndexByte(query[start:], '&')
	if end == -1 {
		return query[start:]
	}
	return query[start : start+end]
}

func extractReferrerHost(rawReferrer string) string {
	if strings.TrimSpace(rawReferrer) == "" {
		return ""
	}

	parsed, err := url.Parse(rawReferrer)
	if err != nil {
		return ""
	}

	host := strings.TrimSpace(parsed.Hostname())
	if host == "" {
		host = strings.TrimSpace(parsed.Host)
	}

	return host
}

// parseFloat parses a string to float64 and stores result in dest.
func parseFloat(s string, dest *float64) (float64, error) {
	var v float64
	_, err := fmt.Sscanf(s, "%f", &v)
	*dest = v
	return v, err
}

// botUAPatterns is a curated list of known bot/crawler UA substrings.
// Source: cross-referenced with Keitaro UserBotListService reference implementation.
var botUAPatterns = []string{
	"googlebot", "bingbot", "slurp", "duckduckbot", "baiduspider", "yandexbot",
	"sogou", "exabot", "facebot", "ia_archiver", "facebookexternalhit", "twitterbot",
	"rogerbot", "linkedinbot", "embedly", "quora link preview", "outbrain", "pinterest",
	"slackbot", "vkshare", "w3c_validator", "whatsapp", "ahrefsbot", "semrushbot",
	"dotbot", "petalbot", "mj12bot", "archive.org_bot", "seznambot", "blexbot",
	"curl/", "python-requests", "python-urllib", "go-http-client", "libwww-perl",
	"wget/", "java/", "scrapy", "headlesschrome", "phantomjs", "selenium", "webdriver",
	"bot", "crawler", "spider", "scan", "scraper",
	// Keitaro expansion (Plan 4.2)
	"advisorbot", "obot", "ezooms", "flipboardproxy", "chtml proxy", "tweetmemebot",
	"sputnikbot", "webindex", "adsbot", "/bots", "ru_bot", "orangebot",
	"synapse", "seostats", "owler", "ltx71", "winhttprequest", "pageanalyzer",
	"openlinkprofiler", "bot for jce", "bubing", "nutch", "megaindex",
	"coccoc", "sleuth", "cmcm.com", "yandexmobilebot", "google-youtube-links",
	"mailruconnect", "surveybot", "appengine", "netcraftsurveyagent",
	"exabot-thumbnails", "bingpreview",
}

// botIPPrefixes are known datacenter/bot IP ranges (starter list).
// Phase 4 will load comprehensive databases (e.g., ipinfo.io ASN to bot mappings).
var botIPPrefixes = func() []*net.IPNet {
	prefixes := []string{
		// Google crawlers
		"66.249.64.0/19",
		"66.249.80.0/20",
		// Bing crawlers
		"157.55.0.0/16",
		"65.52.0.0/14",
		// Common datacenter ranges (starter)
		"64.233.160.0/19",
	}
	var nets []*net.IPNet
	for _, p := range prefixes {
		_, ipNet, err := net.ParseCIDR(p)
		if err == nil {
			nets = append(nets, ipNet)
		}
	}
	return nets
}()
