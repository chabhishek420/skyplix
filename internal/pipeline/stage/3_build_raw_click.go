package stage

import (
	"fmt"
	"net"
	"net/http"
	"strings"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// BuildRawClickStage — Pipeline Stage 3
// Extracts all request data into RawClick: IP, UA, referrer, sub_ids.
// ALSO runs inline basic bot detection per ADR-008:
//   - Empty UA check
//   - UA pattern match (known crawlers/bots)
//   - IP blocklist check (starter list — Phase 4 upgrades with full DB)
type BuildRawClickStage struct{}

func (s *BuildRawClickStage) Name() string { return "BuildRawClick" }

func (s *BuildRawClickStage) Process(payload *pipeline.Payload) error {
	r := payload.Request
	rc := payload.RawClick

	// Extract real IP (respects X-Forwarded-For, X-Real-IP)
	rc.IP = extractRealIP(r)

	// User Agent
	rc.UserAgent = r.Header.Get("User-Agent")

	// Referrer
	rc.Referrer = r.Header.Get("Referer")

	// Sub IDs from query params
	q := r.URL.Query()
	rc.SubID1 = q.Get("sub_id_1")
	if rc.SubID1 == "" {
		rc.SubID1 = q.Get("sub1")
	}
	rc.SubID2 = q.Get("sub_id_2")
	if rc.SubID2 == "" {
		rc.SubID2 = q.Get("sub2")
	}
	rc.SubID3 = q.Get("sub_id_3")
	if rc.SubID3 == "" {
		rc.SubID3 = q.Get("sub3")
	}
	rc.SubID4 = q.Get("sub_id_4")
	if rc.SubID4 == "" {
		rc.SubID4 = q.Get("sub4")
	}
	rc.SubID5 = q.Get("sub_id_5")
	if rc.SubID5 == "" {
		rc.SubID5 = q.Get("sub5")
	}

	// Cost from query param
	if costStr := q.Get("cost"); costStr != "" {
		var cost float64
		if _, err := parseFloat(costStr, &cost); err == nil {
			rc.Cost = cost
		}
	}

	// --- Inline Bot Detection (ADR-008) ---
	rc.IsBot = detectBot(rc.IP, rc.UserAgent)

	return nil
}

// detectBot runs the basic bot detection checks inline.
// Returns true if any check triggers.
func detectBot(ip net.IP, ua string) bool {
	// 1. Empty User-Agent
	if strings.TrimSpace(ua) == "" {
		return true
	}

	// 2. Known bot UA patterns (case-insensitive substring match)
	uaLower := strings.ToLower(ua)
	for _, pattern := range botUAPatterns {
		if strings.Contains(uaLower, pattern) {
			return true
		}
	}

	// 3. Known bot IP ranges (hardcoded starter list — upgraded in Phase 4)
	if ip != nil {
		for _, prefix := range botIPPrefixes {
			if prefix.Contains(ip) {
				return true
			}
		}
	}

	return false
}

// extractRealIP extracts the real client IP from the request.
// Checks X-Forwarded-For, X-Real-IP, then falls back to RemoteAddr.
func extractRealIP(r *http.Request) net.IP {
	// X-Forwarded-For may contain multiple IPs (client, proxy1, proxy2...)
	if xff := r.Header.Get("X-Forwarded-For"); xff != "" {
		parts := strings.Split(xff, ",")
		if ip := net.ParseIP(strings.TrimSpace(parts[0])); ip != nil {
			return ip
		}
	}

	if xri := r.Header.Get("X-Real-IP"); xri != "" {
		if ip := net.ParseIP(strings.TrimSpace(xri)); ip != nil {
			return ip
		}
	}

	// Fall back to RemoteAddr (strip port)
	host, _, err := net.SplitHostPort(r.RemoteAddr)
	if err != nil {
		return net.ParseIP(r.RemoteAddr)
	}
	return net.ParseIP(host)
}

// parseFloat parses a string to float64 and stores result in dest.
func parseFloat(s string, dest *float64) (float64, error) {
	var v float64
	_, err := fmt.Sscanf(s, "%f", &v)
	*dest = v
	return v, err
}

// botUAPatterns is a curated list of known bot/crawler UA substrings.
// Source: cross-referenced with YellowCloaker reference implementation.
// Phase 4 will replace with a more complete, database-backed approach.
var botUAPatterns = []string{
	"googlebot", "bingbot", "slurp", "duckduckbot", "baiduspider",
	"yandexbot", "sogou", "exabot", "facebot", "ia_archiver",
	"facebookexternalhit", "twitterbot", "rogerbot", "linkedinbot",
	"embedly", "quora link preview", "outbrain", "pinterest",
	"slackbot", "vkshare", "w3c_validator", "whatsapp",
	"ahrefsbot", "semrushbot", "dotbot", "petalbot",
	"mj12bot", "archive.org_bot", "seznambot", "blexbot",
	"curl/", "python-requests", "python-urllib", "go-http-client",
	"libwww-perl", "wget/", "java/", "scrapy",
	"headlesschrome", "phantomjs", "selenium", "webdriver",
	"bot", "crawler", "spider", "scan", "scraper",
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
