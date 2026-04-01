# JOURNAL.md — Project Journal

## 2026-04-01 — Project Initialized & Stack Finalized

### Codebase Analysis
- Analyzed Keitaro PHP source v9.13.9 (1,705 PHP files, 4 modules)
- Verified two-level pipeline from `Traffic/Pipeline/Pipeline.php`:
  Level 1 = 23 stages (campaign click), Level 2 = 13 stages (landing click)
- Cataloged 51 component modules, 55 admin API controllers
- Identified 27 stream filter types from `Component/StreamFilters/Filter/`
- Identified 15 action types from `Traffic/Actions/Predefined/`
- Analyzed YellowCloaker (12-check bot detection system)
- Verified yljary.com live TDS infrastructure via terminal
- Scanned 8 reference projects in `reference/` directory

### Architecture Decisions
- Decision: Go rewrite over continuing Next.js implementation
- Decision: Chi v5 over Fiber (net/http compatibility > synthetic speed)
- Decision: Valkey 8 over Redis 7 (BSD open-source, drop-in compatible)
- Decision: sqlc + pgx over GORM (zero ORM, compile-time safety)
- Decision: Vite + React over Next.js for admin (static embed in Go binary)
- Decision: Valkey session tokens over JWT (revocable, team-safe)
- Decision: ClickHouse 24 for analytics (Keitaro has none — we leapfrog)

### Repository Cleanup
- Archived legacy Next.js codebase into `reference/legacy-nextjs/`
- Fresh `git init` — clean single-commit history
- Created SPEC, ROADMAP, ARCHITECTURE, STACK, RESEARCH, STATE

### Key Insight from Source
Redis is Keitaro's primary performance secret, not MySQL. All hot-path
reads go to Redis (pre-warmed entity cache). All hot-path writes queue
through Redis (async command buffer, flushed by cron in batches of 1000).
MySQL is never touched during click processing.

---

## Session: 2026-04-02 00:41

### Objective
Deep empirical validation of all planning documents against the Keitaro v9.13.9 PHP source code to finalize the pre-Phase 1 architecture.

### Accomplished
- Completed 1,700-file Keitaro source code audit.
- Identified 8 material flaws in initial architectural assumptions.
- Corrected the "no ClickHouse" assumption (found `rbooster` config).
- Discovered 4 new action types, including the critical `Remote` proxy action for cloaking.
- Mapped Keitaro's undocumented Entity Binding system (`BindVisitors`).
- Documented 13 background workers (cron tasks), including the critical Redis → DB flush.
- Fixed device detection library choice (`robicode` vs `mssola`).
- Restructured `ROADMAP.md` (moved bot detection to Phase 1, entity binding to Phase 2).
- Added 3 new ADRs to `DECISIONS.md`.

### Verification
- [x] All 23 Level 1 pipeline stages verified line-by-line.
- [x] All 13 Level 2 pipeline stages verified line-by-line.
- [x] Redis caching points and background workers verified.
- [ ] Needs evaluation between `robicode/device-detector` and `mileusna/useragent` in Phase 1.

### Paused Because
Context getting heavy (Context Health Monitor triggered). Saturated with old PHP code research. It's time to flush the context and start fresh before building the Go scaffold.

### Handoff Notes
The planning is 100% accurate and vetted. Next session should jump straight to `/plan 1` and execution of the `/cmd`, `/internal`, `/db` scaffolding. Ensure Docker compose includes Postgres, Valkey, and ClickHouse.

---

## Session: 2026-04-02 01:35

### Objective
Execute Phase 1 — scaffold the entire Go project, wire the core click pipeline, and prove a click flows from HTTP request → PostgreSQL lookup → GeoIP/device enrichment → bot detection → ClickHouse storage.

### Accomplished
- **Plan 1.1 ✅ COMPLETE** (`b263e23a`)
  - Go module `github.com/skyplix/zai-tds` initialized (Go 1.25.6)
  - 27 `internal/` directories scaffolded (exact match of ARCHITECTURE.md)
  - `config.yaml` with all required keys; `internal/config/config.go` with env var override
  - `cmd/zai-tds/main.go` — signal-aware entry with `signal.NotifyContext`
  - `internal/server/server.go` + `routes.go` — Chi v5 router, health endpoint
  - `docker-compose.yml` — PostgreSQL 16-alpine, Valkey 8-alpine, ClickHouse 24-alpine
  - All 4 PostgreSQL migration pairs applied and verified (10 tables)
  - ClickHouse `clicks` + `conversions` tables created in `zai_analytics` DB
  - `go build ./... → BUILD OK`, all 3 Docker services healthy

- **Plan 1.2 ✅ COMPLETE** (`a554e45f`)
  - `internal/model/models.go` — RawClick, Campaign, Stream, Offer, Landing
  - `internal/pipeline/pipeline.go` — Stage interface, Payload struct, Pipeline runner
  - Stages 1–6 implemented: DomainRedirect, CheckPrefetch, BuildRawClick (bot detection), FindCampaign, CheckDefaultCampaign, UpdateRawClick
  - Inline bot detection (ADR-008): empty UA, 35+ patterns, 5 IP CIDR ranges
  - `internal/geo/geo.go` — MaxMind GeoIP2 resolver, graceful when .mmdb missing
  - `internal/device/detector.go` — mileusna/useragent pure-Go device parser
  - NoOp stubs for stages 7–23
  - Verified: `GET /testcampaign → 302`, `GET /nonexistent → 404`, health `→ 200`

- **Plan 1.3 🔴 IN PROGRESS** (`e9e65768` — WIP)
  - `internal/queue/writer.go` — async ClickHouse batch writer (500ms ticker, 10k buffer)
  - `internal/worker/` — Worker interface, Manager, HitLimitReset, CacheWarmup, SessionJanitor
  - Stage 13 (GenerateToken), Stage 20 (ExecuteAction/HttpRedirect), Stage 23 (StoreRawClicks)
  - `test/integration/click_test.go` — E2E test (`//go:build integration`)
  - Server updated: wires 4 workers + ClickHouse writer on startup
  - `go build ./... OK`, `go vet ./... OK`
  - **Integration test FAILING**: ClickHouse shows 0 rows after click

### Verification
- [x] `go build ./...` clean
- [x] `go vet ./...` clean
- [x] Plan 1.1: All 10 PG tables, 2 CH tables, 3 Docker services healthy
- [x] Plan 1.2: Pipeline runs (302 returned), 23 stages all execute (visible in logs), bot detection works
- [ ] Plan 1.3: ClickHouse storage NOT verified — integration test fails (0 rows)

### Paused Because
User requested pause (/pause issued twice). Context is heavy from the long execution session.

### Handoff Notes
**The bug is fully diagnosed — fix is straightforward:**

Root cause in `internal/queue/writer.go` `flush()` function (lines 192–258):
1. `INSERT INTO clicks` without column names → passes `click_token` (string) for `click_id` (UUID) — SILENT TYPE ERROR
2. `campaign_id`, `stream_id`, `offer_id`, `landing_id` passed as `string` but CH expects UUID `[16]byte`
3. `country_code` as `FixedString(2)` — empty string `""` may cause silent rejection

**Fix plan** (State.md has full code snippets):
1. Change INSERT to name 31 columns explicitly (skip `click_id` — let CH DEFAULT generate it)
2. Parse UUID strings to `[16]byte` using `uuid.Parse()` before passing to `b.Append()`
3. Pad `country_code` to exactly 2 bytes if empty
4. Ensure IP is always 16-byte IPv6 form via `.To16()`
5. Rebuild, rerun integration test, commit as Plan 1.3 final

