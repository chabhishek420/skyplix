## Current Position
- **Phase**: 1 — Foundation
- **Task**: Plan 1.3 — ClickHouse writer bug fix (integration test failing)
- **Status**: Paused at 2026-04-02T01:35 IST

## Last Session Summary
Executed Phase 1 Plans 1.1 and 1.2 fully. Started Plan 1.3.

**Plan 1.1 ✅ DONE (commit b263e23a)**
- Go module init (`github.com/skyplix/zai-tds`, Go 1.25.6)
- 27 internal/ directories scaffolded
- `config.yaml` + `internal/config/config.go` (YAML + env override, no globals)
- `cmd/zai-tds/main.go` (signal-aware, uses `signal.NotifyContext`)
- `internal/server/server.go` + `routes.go` (Chi v5, health endpoint)
- `docker-compose.yml`: PostgreSQL 16, Valkey 8, ClickHouse 24 (all 3 healthy)
- 4 PostgreSQL migration pairs (campaigns, streams, offers/landings, domains/users)
- ClickHouse clicks + conversions tables
- All migrations applied and verified

**Plan 1.2 ✅ DONE (commit a554e45f)**
- `internal/model/models.go`: RawClick (~40 fields), Campaign, Stream, Offer, Landing
- `internal/pipeline/pipeline.go`: Stage interface + Payload + Pipeline runner (abort support)
- All 6 real stages: DomainRedirect, CheckPrefetch, BuildRawClick, FindCampaign, CheckDefaultCampaign, UpdateRawClick
- Stage 3 bot detection: empty UA, 35+ bot UA patterns, 5 bot IP CIDR ranges (ADR-008)
- `internal/geo/geo.go`: MaxMind mmdb resolver (graceful if path empty)
- `internal/device/detector.go`: mileusna/useragent pure-Go parser
- NoOp stubs for stages 7-23
- `GET /testcampaign` → 302 ✓, `GET /nonexistent` → 404 ✓, health → 200 ✓

**Plan 1.3 🔴 IN PROGRESS (commit e9e65768 — WIP)**
All code written, `go build` + `go vet` clean, but integration test fails:
- `internal/queue/writer.go` — async ClickHouse batch writer (500ms flush)
- `internal/worker/worker.go` + hitlimit_reset.go + cache_warmup.go
- `internal/pipeline/stage/13_generate_token.go` — crypto/rand 32-char hex
- `internal/pipeline/stage/20_execute_action.go` — HttpRedirect action
- `internal/pipeline/stage/23_store_raw_clicks.go` — non-blocking chan push
- `test/integration/click_test.go` — E2E test (build tag: integration)
- server.go + routes.go updated to wire all of the above

## In-Progress Work
- Files modified: all committed in e9e65768
- Tests status: **FAILING** — `TestEndToEndClick` sees 0 rows in ClickHouse after click

## Blockers
**Bug: ClickHouse batch INSERT silently failing — 3 root causes identified:**

1. `INSERT INTO clicks` without column names → hits `click_id UUID` first column, but we pass `r.ClickToken` (a string, not UUID). Type mismatch causes silent drop.
2. `campaign_id`, `stream_id`, `offer_id`, `landing_id` are `UUID` type in ClickHouse but we pass Go strings — type mismatch.
3. `country_code` is `FixedString(2)` — if empty string `""` is passed, CH may reject.

**ClickHouse table column order (from `DESCRIBE TABLE clicks`):**
```
1  click_id        UUID
2  created_at      DateTime64(3, 'UTC')
3  campaign_id     UUID
4  campaign_alias  String
5  stream_id       UUID
6  offer_id        UUID
7  landing_id      UUID
8  ip              IPv6
9  country_code    FixedString(2)
10 city            String
11 isp             String
12 device_type     String
13 device_model    String
14 os              String
15 os_version      String
16 browser         String
17 browser_version String
18 user_agent      String
19 referrer        String
20 is_bot          UInt8
21 is_unique_global    UInt8
22 is_unique_campaign  UInt8
23 is_unique_stream    UInt8
24 sub_id_1..5     String
29 cost            Decimal(10,4)
30 payout          Decimal(10,4)
31 action_type     String
32 click_token     String
```

## Context Dump

### Fix Required
In `internal/queue/writer.go`, the `flush()` function must be rewritten:

**1. Change INSERT to name columns (skip click_id — let CH auto-generate UUID):**
```go
b, err := w.conn.PrepareBatch(ctx, `INSERT INTO clicks
  (created_at, campaign_id, campaign_alias, stream_id, offer_id, landing_id,
   ip, country_code, city, isp, device_type, device_model, os, os_version,
   browser, browser_version, user_agent, referrer, is_bot, is_unique_global,
   is_unique_campaign, is_unique_stream, sub_id_1, sub_id_2, sub_id_3,
   sub_id_4, sub_id_5, cost, payout, action_type, click_token)`)
```

**2. Pass `[16]byte` for UUID columns (clickhouse-go v2 requires this):**
```go
import "github.com/google/uuid"

campaignID, _ := uuid.Parse(r.CampaignID)
// pass campaignID directly (it's [16]byte)
```

**3. Ensure country_code is always exactly 2 bytes:**
```go
cc := r.CountryCode
if len(cc) != 2 { cc = "  " }  // FixedString(2) requires exactly 2 bytes
```

**4. IP as net.IP (IPv6) — already correct approach, just ensure IPv4-mapped:**
```go
ip := net.ParseIP(r.IP).To16()  // always return 16-byte IPv6 form
```

### Files of Interest
- `internal/queue/writer.go` — flush() needs full rewrite (lines 192-258)
- `internal/queue/writer.go` — ClickRecord struct UUIDs should stay as strings (converted at flush time)
- `test/integration/click_test.go` — test itself is correct
- `db/clickhouse/migrations/001_create_clicks.sql` — source of truth for schema

### What Was Verified Working
- Manual HTTP INSERT to ClickHouse works: `curl ... INSERT INTO clicks (...) VALUES (...)`
- PostgreSQL, Valkey, ClickHouse all healthy
- `go build ./...` clean, `go vet ./...` clean
- The click pipeline runs (302 returned), so stage 23 is executing
- The batch channel is being populated (stage 23 runs, server logs show "click processed")

### Current Hypothesis
The `b.Append()` call in flush() is failing silently on the first row due to type mismatch on `click_id` (string vs UUID). The error IS being logged but we didn't capture it in the test run logs. Fix the INSERT statement to name columns + fix type conversions.

## Next Steps
1. **Fix `internal/queue/writer.go`** — rewrite `flush()` with named column INSERT + proper UUID/IP types (see Context Dump above — solution is fully mapped out)
2. **Rebuild + rerun integration test** — `go test -v -tags integration ./test/integration/ -run TestEndToEndClick -timeout 60s`
3. **Verify ClickHouse row**: `SELECT click_token, is_bot, country_code FROM zai_analytics.clicks LIMIT 5`
4. Once passing: commit Plan 1.3 final, create SUMMARY.md, run `/verify 1`
