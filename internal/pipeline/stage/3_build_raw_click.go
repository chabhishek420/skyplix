/*
 * MODIFIED: internal/pipeline/stage/3_build_raw_click.go
 * PURPOSE: Extracts request data into RawClick. Added nil-check for 
 *          RawClick initialization to prevent panics in L2 pipelines.
 */
package stage

import (
	"context"
	"fmt"
	"math"
	"net"
	"net/netip"
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
type BuildRawClickStage struct {
	BotDB       interface{ Contains(net.IP) bool }
	CustomUA    interface{ Patterns() []string }
	Geo         interface{ IsDatacenter(net.IP) bool }
	CIDRFilter  interface{ ContainsIP(netip.Addr) bool }
	RateLimiter interface {
		CheckIPLimit(context.Context, net.IP, int, time.Duration) (bool, int64, error)
	}
	IPRateLimit  int
	IPRateWindow time.Duration
}

func (s *BuildRawClickStage) AlwaysRun() bool { return false }
func (s *BuildRawClickStage) Name() string { return "BuildRawClick" }

func (s *BuildRawClickStage) Process(payload *pipeline.Payload) error {
	r := payload.Request
	if payload.RawClick == nil {
		payload.RawClick = &model.RawClick{}
	}
	rc := payload.RawClick

	// IP is now extracted in NormalizeIPStage

	// User Agent
	rc.UserAgent = r.Header.Get("User-Agent")

	// Referrer
	rc.Referrer = r.Header.Get("Referer")

	// Sub IDs from query params
	q := r.URL.Query()
	rc.RawQuery = r.URL.RawQuery
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
			rc.Cost = int64(math.Round(cost * 100))
		}
	}

	// --- Inline Bot Detection (ADR-008) ---
	isBot, reason, score := s.detectBot(rc.IP, rc.UserAgent)
	rc.IsBot = isBot
	rc.BotReason = reason
	rc.BehaviorScore = score

	return nil
}

// detectBot runs the basic bot detection checks inline.
// Returns (isBot, reason, score).
func (s *BuildRawClickStage) detectBot(ip net.IP, ua string) (bool, string, uint8) {
	var score uint8 = 0

	// 1. Empty User-Agent
	if strings.TrimSpace(ua) == "" {
		return true, "empty_ua", 100
	}

	// 2. Known bot UA patterns (case-insensitive substring match)
	uaLower := strings.ToLower(ua)
	for _, pattern := range botUAPatterns {
		if strings.Contains(uaLower, pattern) {
			return true, fmt.Sprintf("ua_pattern:%s", pattern), 90
		}
	}

	// 3. Known bot IP ranges (hardcoded starter list — fallback/fast-path)
	if ip != nil {
		for _, prefix := range botIPPrefixes {
			if prefix.Contains(ip) {
				return true, fmt.Sprintf("ip_prefix:%s", prefix.String()), 95
			}
		}
	}

	// 3.5 CIDR Blocklist
	if s.CIDRFilter != nil && ip != nil {
		if addr, ok := netip.AddrFromSlice(ip); ok {
			if s.CIDRFilter.ContainsIP(addr.Unmap()) {
				return true, "cidr_blocklist", 95
			}
		}
	}

	// 4. Advanced bot IP database (Phase 4 upgrade)
	if s.BotDB != nil && ip != nil {
		if s.BotDB.Contains(ip) {
			return true, "bot_db_ip", 100
		}
	}

	// 5. Custom UA signatures from Valkey/Admin
	if s.CustomUA != nil {
		customPatterns := s.CustomUA.Patterns()
		for _, pattern := range customPatterns {
			if strings.Contains(uaLower, pattern) {
				return true, fmt.Sprintf("custom_ua:%s", pattern), 90
			}
		}
	}

	// 6. Datacenter/VPN heuristic check (Phase 4 upgrade)
	if s.Geo != nil && ip != nil {
		if s.Geo.IsDatacenter(ip) {
			score += 40
			if score >= 80 {
				return true, "datacenter_asn", score
			}
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

		allowed, count, err := s.RateLimiter.CheckIPLimit(ctx, ip, limit, window)
		if err == nil {
			if !allowed {
				return true, "rate_limited", 100
			}
			// Progressive score for high velocity even if not yet limited
			if count > int64(limit/2) {
				score += 20
			}
		}
	}

	if score >= 80 {
		return true, "high_behavior_score", score
	}

	return false, "", score
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
