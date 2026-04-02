# ROADMAP.md

> **Current Phase**: Not started
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
- **Basic bot detection** (IP list check + UA pattern match + empty UA check + proxy detection — runs inside BuildRawClickStage)
- PostgreSQL schema + migrations (campaigns, streams, offers, landings — core entity tables)
- **Stream↔Landing and Stream↔Offer association tables** (join tables with weights)
- ClickHouse click schema + async batch writer (buffered Go channel, flush every 500ms or 5000 clicks)
- **Background worker goroutines**: click writer, cache warmup, hit limit daily reset
- Campaign type field (POSITION vs WEIGHT) in data model

### Phase 2: Campaign Engine — Streams, Filters, Rotators, Entity Binding
**Status**: ⬜ Not Started
**Objective**: Implement the campaign routing engine — 3-tier stream selection (FORCED → REGULAR → DEFAULT), stream filter matching (27 filter types including IsBot), position-based AND weight-based rotation, offer/landing weighted rotation, affiliate network resolution, entity binding (bind returning visitors to same stream/landing/offer via Valkey + cookies). Pipeline stages 7-12, 14-18.
**Deliverable**: Full traffic routing: click → bot check → match filters → pick stream → rotate offer → bind visitor → redirect
**Requirements**:
- Stream filter engine (27 types: geo, device, network, traffic, tracking, parameters, schedule, detection)
- **3-tier stream selection**: FORCED streams (position), REGULAR streams (position or weight by campaign type), DEFAULT stream (fallback)
- Weighted rotator for streams, landings, offers
- **Entity binding service**: bind visitor→stream, visitor→landing, visitor→offer in Valkey with cookie fallback (EntityBindingService pattern from Keitaro)
- Macro replacement engine ({click_id}, {country}, {sub_id_1}, etc.)
- **All 19 action types** including `Remote` (reverse proxy for safe pages) and `SubId`
- Level 2 pipeline (13 stages — landing→offer click linking via LpToken)
- Uniqueness tracking (campaign-level, stream-level)
- Hit limit enforcement (daily/total caps)
- Gateway context handling (bare domain → campaign redirect)

### Phase 3: Admin API — CRUD for All P0/P1 Entities
**Status**: ⬜ Not Started
**Objective**: RESTful JSON API for managing campaigns, streams, stream filters, offers, landings, domains, affiliate networks, traffic sources, users, settings. Auth via API key + session cookies. On entity save: trigger Valkey cache warmup (async via warmup scheduler, matching Keitaro's `WarmupScheduler` pattern).
**Deliverable**: Complete admin API that the frontend can consume, with entity save → Valkey cache invalidation
**Requirements**: All P0+P1 entity CRUD, validation, pagination, filtering, auth middleware, **cache warmup trigger on entity mutations**

### Phase 4: Advanced Cloaking & Bot Detection
**Status**: ⬜ Not Started
**Objective**: Upgrade bot detection beyond Phase 1's basic IP+UA checks: datacenter/VPN/Tor IP databases, ISP blacklisting, JS fingerprint challenges, rate limiting per IP/campaign. Safe page system with configurable strategies per campaign (Remote proxy of real site, LocalFile, Status404, ShowHtml). This builds on the basic bot detection already running in BuildRawClickStage.
**Deliverable**: Production cloaking — compliance bots/scanners see safe pages, real users see offers
**Requirements**: Bot IP databases (datacenter ranges, VPN providers), FingerprintJS open-source alternative, safe page configuration per campaign/stream, `Remote` action type fully wired for reverse-proxying real websites

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

