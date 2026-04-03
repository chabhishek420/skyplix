# ROADMAP.md

> **Current Phase**: 4 — Advanced Cloaking & Bot Detection
> **Milestone**: v1.0 — Production TDS

## Must-Haves (from SPEC)

- [ ] Click pipeline processing <5ms p99
- [ ] Campaign/Stream/Offer CRUD
- [ ] Bot detection + cloaking
- [ ] Postback conversion tracking
- [ ] Real-time analytics dashboard
- [ ] Single binary deployment

## Phases

### Phase 1: Foundation — Go Project + Core Pipeline + Background Workers
**Status**: ✅ Complete
**Objective**: Scaffold the Go project, Docker Compose environment (PG, Valkey, ClickHouse), implement the core click pipeline (stages 1-6, 13, 20-23), achieve a working `/click` endpoint that receives a request, builds a RawClick, resolves geo/device, detects bots (IP + UA — inline in BuildRawClickStage), generates a click token, and returns a 302 redirect. Also implement the background worker framework: async click writer (Go channel → ClickHouse batch), Valkey cache warmup trigger, and hit limit reset.
**Deliverable**: `./skyplix` binary that processes clicks with hardcoded campaign config, writes to ClickHouse asynchronously, and runs background workers
**Requirements**:
- Go project structure (`cmd/`, `internal/`, `db/`)
- Docker Compose: PostgreSQL 16, Valkey 8, ClickHouse 24
- HTTP server (Chi v5) with click/admin route split
- RawClick model (~60 fields, mirrors Keitaro's)
- Pipeline framework (ordered stage slice, Payload struct, abort/early-exit)
- GeoIP integration (MaxMind mmdb, in-memory)
- Device detection (evaluate `robicode/device-detector` vs `mileusna/useragent`)
- **Basic bot detection** (5 IP CIDR ranges + 43 UA pattern matches + empty UA check — runs inside BuildRawClickStage, per ADR-008)
- PostgreSQL schema + migrations (campaigns, streams, offers, landings — core entity tables)
- **Stream↔Landing and Stream↔Offer association tables** (join tables with weights)
- ClickHouse click schema + async batch writer (buffered Go channel, flush every 500ms or 5000 clicks)
- **Background worker goroutines**: click writer, cache warmup, hit limit daily reset
- Campaign type field (POSITION vs WEIGHT) in data model

### Phase 1.5: Maintenance — Reliability & Robustness
**Status**: ✅ Complete
**Objective**: Fix critical flaws in shutdown, data integrity, and analytics to ensure Phase 1 foundation is truly solid before adding campaign complexity.
**Deliverable**: Bulletproof shutdown logic, hardened ClickHouse ingestion, and acknowledged uniqueness debt.
**Requirements**:
- **Inverted Shutdown Dependency**: Cancel HTTP server first, then drain workers.
- **UUID Validation**: Validate all incoming UUID strings in `writer.go` to prevent batch-level ingestion failures.
- **Technical Debt Logging**: Formally acknowledge the "default-duplicate" analytics status of Phase 1 traffic.

### Phase 2: Campaign Engine — Streams, Filters, Rotators, Entity Binding
**Status**: ✅ Complete
**Objective**: Implement the campaign routing engine — 3-tier stream selection (FORCED → REGULAR → DEFAULT), stream filter matching (27 filter types including IsBot), position-based AND weight-based rotation, offer/landing weighted rotation, affiliate network resolution, entity binding (bind returning visitors to same stream/landing/offer via Valkey + cookies). Pipeline stages 7-12, 14-18.
**Deliverable**: Full traffic routing: click → bot check → match filters → pick stream → rotate offer → bind visitor → redirect
**Requirements**:
- [x] Correct test seed data casing and JSON structure in `seed.sql.bak`
- [x] Add case-insensitive normalization to filter engine in `filter.go`
- [x] Refine `ExecuteActionStage` (logic + logging) in `20_execute_action.go`
- [x] Add GeoIP test-override headers in `6_update_raw_click.go`
- [x] Implement case-insensitive normalization in `action.go`
- [x] Update integration tests in `routing_test.go`
- [x] Fix critical pointer bugs across all selection stages (heap-copy)
- [x] Implement cache fallbacks in `cache.go` for reliability
- [x] Integrate `SaveLPTokenStage` and brute-force L2 resolution
- [x] Resolve Level 2 landing-to-offer redirect
- [x] Verify full Phase 2 routing engine (100% GREEN)
- [x] Phase 2 Hardened Foundation Complete

### Phase 3: Admin API — CRUD for All P0/P1 Entities
**Status**: ✅ Complete
**Objective**: RESTful JSON API for managing campaigns, streams, stream filters, offers, landings, domains, affiliate networks, traffic sources, users, settings. Auth via API key (session cookies deferred to Phase 6). On entity save: trigger Valkey cache warmup (async via warmup scheduler, matching Keitaro's `WarmupScheduler` pattern).
**Deliverable**: Complete admin API that the frontend can consume, with entity save → Valkey cache invalidation
**Requirements**: All P0+P1 entity CRUD, validation, pagination, filtering, auth middleware, **cache warmup trigger on entity mutations**

### Phase 4: Advanced Cloaking & Bot Detection
**Status**: ✅ Complete
**Objective**: Upgrade bot detection beyond Phase 1's basic IP+UA checks. Implement production-grade cloaking with multi-layer detection (modeled after YellowCloaker's 12-check engine + Keitaro's bot system) and safe page delivery (modeled after Keitaro's `Remote` action). This builds on the basic bot detection already running in BuildRawClickStage.
**Deliverable**: Production cloaking — compliance bots/scanners see safe pages, real users see offers
**Requirements**:
- **P0 — Bot IP Management**: IP range/CIDR/single management with merge/exclude ops (port Keitaro `UserBotsService.php` pattern — sorted int ranges with binary search)
- **P0 — Datacenter/VPN/Tor Detection**: Integrate datacenter IP database (MaxMind ASN type lookup) + optional external VPN detection API (YellowCloaker uses `ipinfo.app/lookup`)
- **P0 — UA Signature Expansion**: Expand from 43 to 54+ patterns (full Keitaro `UserBotListService.php` list) + user-defined custom UA signatures stored in Valkey
- **P0 — Safe Page System**: Configurable per stream — 4 modes from Keitaro reference:
  - `Remote` — **enhance** existing `RemoteProxyAction` with TTL cache (60s, Keitaro `Remote.php` pattern — basic proxy already implemented in `proxy.go`)
  - `LocalFile` — serve static HTML from filesystem
  - `Status404` / `DoNothing` — return HTTP error codes (already implemented)
  - `ShowHtml` — inline HTML content (already implemented)
- **P1 — ISP Blacklisting**: Filter by ISP name (substring match against MaxMind ASN/ISP data)
- **P1 — Referrer Analysis**: Empty referrer blocking + referrer stopword matching (YellowCloaker pattern)
- **P1 — URL Token Blacklisting**: Block clicks containing specific URL query parameters (debug tokens, scanner tokens)
- **P1 — Rate Limiting**: Per-IP and per-campaign rate limiting via Valkey counters
- **P2 — JS Fingerprint Challenges**: Browser verification via JS challenge page (timezone check, basic WebGL/Canvas fingerprint)
- **P2 — Third-Party API Integration**: HideClick/IMKLO-style external detection APIs (Keitaro `StreamFilters/Filter/ImkloDetect.php` pattern)
- **P3 — Pipeline Recursion**: **Convert** existing `ToCampaignAction` from simple 302 redirect → recursive pipeline re-entry with state reset (up to 10 levels, Keitaro `Pipeline.php` L60-73)
- **P3 — Behavioral Analysis**: Request timing, header consistency checks (lesson from yljary investigation — operators don't rely on UA/referrer alone)

### Phase 4.9.4: Gap Closure & Uniqueness Hardening
**Status**: ✅ Complete (Latency: 2.06ms p99)
**Objective**: Address critical gaps from v1.0 milestone audit (Global Uniqueness + Performance Benchmarks).
**Gaps to Close:**
- [x] Implement `UpdateGlobalUniquenessStage` (Global TDS uniqueness check).
- [x] Implement `IsUniqueGlobal` check in `session.Service`.
- [x] Establish p99 latency baseline under 1k RPS load.
- [x] Research ClickHouse partitioning for Phase 5.


### Phase 5: Conversion Tracking & Analytics
**Status**: ⬜ Not Started
**Objective**: Postback (S2S) conversion tracking, real-time stats aggregation (daily/hourly materialized views in ClickHouse), reporting API with drilldowns by campaign/geo/device/source/time.
**Deliverable**: Full click→conversion attribution, live revenue dashboards
**Requirements**: Postback endpoint, ClickHouse materialized views, stats aggregation, report builder, conversion→click linking via click_token

### Phase 6: Admin Dashboard UI
**Status**: ⬜ Not Started
**Objective**: React SPA admin dashboard with campaign management, click log viewer, conversion reports, real-time stats. Served as static files from the Go binary via `//go:embed`.
**Deliverable**: Production admin interface matching Keitaro's UX
**Requirements**: Vite + React 19 + shadcn/ui, embedded in Go binary, responsive dark theme

### Phase 7: Production Hardening
**Status**: ⬜ Not Started
**Objective**: Production readiness — graceful shutdown, health checks, metrics (Prometheus), structured logging, config validation, Docker image, systemd unit, backup/restore, performance benchmarks, Keitaro→SkyPlix data migration script.
**Deliverable**: Battle-tested binary ready for live affiliate traffic
**Requirements**: Benchmarks proving <5ms p99, load testing, monitoring, deployment docs, data migration from Keitaro MySQL

