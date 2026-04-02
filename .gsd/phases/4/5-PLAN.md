---
phase: 4
plan: 5
wave: 3
---

# Plan 4.5: Rate Limiting + Integration Testing + Phase Verification

## Objective
Implement per-IP rate limiting via Valkey counters (P1 requirement), then run comprehensive integration tests to verify the entire Phase 4 bot detection and cloaking system works end-to-end. This is the final plan before Phase 4 completion.

## Context
- All Phase 4 plans 4.1-4.4 must be complete before this plan
- `internal/pipeline/stage/3_build_raw_click.go` — Bot detection with all new checks
- `internal/action/proxy.go` — Enhanced Remote proxy with TTL cache
- `internal/botdb/` — IP range + UA store
- `test/integration/` — Existing integration test patterns

## Tasks

<task type="auto">
  <name>Per-IP rate limiting via Valkey</name>
  <files>internal/ratelimit/ratelimit.go, internal/pipeline/stage/3_build_raw_click.go, internal/server/server.go</files>
  <action>
    1. Create `internal/ratelimit/ratelimit.go`:
       - `Service struct { client *redis.Client; logger *zap.Logger }`
       - `New(client *redis.Client, logger *zap.Logger) *Service`
       - `CheckIPRate(ctx context.Context, ip net.IP, limit int, window time.Duration) (allowed bool, count int64, err error)`:
         - Key: `ratelimit:ip:{ip_string}`
         - Use Valkey `INCR` + `EXPIRE` pattern (atomic increment with TTL)
         - If count > limit, return false
         - Default: 60 requests per minute per IP
       - `CheckCampaignRate(ctx context.Context, campaignID uuid.UUID, limit int, window time.Duration) (allowed bool, count int64, err error)`:
         - Key: `ratelimit:campaign:{campaign_id}`
         - Same INCR + EXPIRE pattern

    2. **Wire into `BuildRawClickStage`:**
       - Add `RateLimiter` interface field (nil-safe)
       - Add check #6 (after all other bot checks): if IP exceeds rate limit, set `rc.IsBot = true`
       - Default limit: 60 req/min per IP. Configurable via config.yaml.

    3. Wire in `server.go` — Create `ratelimit.Service`, pass to `BuildRawClickStage`.

    4. Add config fields: `system.rate_limit_per_ip` (default 60), `system.rate_limit_window` (default "1m").
  </action>
  <verify>go build ./internal/ratelimit/... && go build ./...</verify>
  <done>Rate limiter compiles, wired into pipeline, configurable limits</done>
</task>

<task type="auto">
  <name>Phase 4 integration test suite</name>
  <files>test/integration/cloaking_test.go, test/integration/testdata/seed_phase4.sql</files>
  <action>
    Create `test/integration/cloaking_test.go` with `//go:build integration`:

    1. **Test seed data** (`test/integration/testdata/seed_phase4.sql`):
       - Campaign "cloaked" with alias "cloaked-test"
       - Stream 1 (REGULAR): filter `IsBot=false`, action `HttpRedirect` to "https://real-offer.com"
       - Stream 2 (DEFAULT): filter `IsBot=true`, action `ShowHtml` with safe page HTML
       - This is the canonical cloaking setup: bots see safe page, humans see offer

    2. **Test cases:**
       - `TestCloaking/HumanGetsOffer` — Normal UA + real IP → 302 to offer URL
       - `TestCloaking/BotUAGetsSafePage` — UA="Googlebot" → 200 with safe page HTML
       - `TestCloaking/EmptyUAGetsSafePage` — Empty UA → safe page
       - `TestCloaking/BotIPGetsSafePage` — Admin adds IP via API, then click from that IP → safe page
       - `TestCloaking/ExpandedUAPatterns` — UA="SputnikBot/1.0" (new pattern from Plan 4.2) → safe page
       - `TestCloaking/CustomUAPattern` — Admin adds custom UA "mycrawler" via API, click with UA "mycrawler/1.0" → safe page
       - `TestCloaking/RateLimitedIPGetsSafePage` — Send 61 clicks from same IP in 1 second → last ones get safe page

    3. **Test structure:**
       - Use same setup pattern as existing `click_test.go` and `routing_test.go`
       - Start server, seed database, run clicks, verify responses
       - Clean up bot IPs after each test to avoid interference

    4. **Verify ClickHouse recording:**
       - After cloaking tests, query ClickHouse to verify `is_bot=true` recorded for bot clicks
       - Verify `action_type` recorded correctly (HttpRedirect vs ShowHtml)
  </action>
  <verify>go test -v -tags integration ./test/integration/ -run TestCloaking -timeout 120s</verify>
  <done>All 7 cloaking test cases pass, ClickHouse records correct bot flags</done>
</task>

<task type="checkpoint:human-verify">
  <name>Phase 4 visual verification</name>
  <files>N/A</files>
  <action>
    Verify the cloaking system manually:
    1. Start server with a cloaked campaign
    2. Access with normal browser → see redirect to offer
    3. Access with curl (bot UA) → see safe page
    4. Add bot IP via admin API → access from that IP → see safe page
    5. Check ClickHouse for correct bot classification
  </action>
  <verify>Manual browser + curl verification</verify>
  <done>User confirms cloaking works correctly in manual testing</done>
</task>

## Success Criteria
- [ ] Rate limiting: IPs exceeding 60 req/min flagged as bots
- [ ] Integration tests: 7/7 cloaking scenarios pass
- [ ] ClickHouse: bot flags recorded correctly
- [ ] End-to-end: human → offer, bot → safe page
- [ ] `go build ./...` clean, `go vet ./...` clean
- [ ] Phase 4 P0+P1 complete
