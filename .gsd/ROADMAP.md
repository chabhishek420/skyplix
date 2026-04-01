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

### Phase 1: Foundation — Go Project + Click Pipeline Core
**Status**: ⬜ Not Started
**Objective**: Scaffold the Go project, implement the core click pipeline (stages 1-6, 13, 22), achieve a working `/click` endpoint that receives a request, builds a RawClick, looks up a campaign, resolves geo/device, generates a click ID, and returns a 302 redirect.
**Deliverable**: `./zai-tds` binary that processes clicks with hardcoded campaign config
**Requirements**: Go project structure, HTTP server, RawClick model, pipeline framework, GeoIP integration, UA parser, PostgreSQL schema + migrations

### Phase 2: Campaign Engine — Streams, Filters, Rotators
**Status**: ⬜ Not Started
**Objective**: Implement the campaign routing engine — stream filter matching (geo, device, OS, language, IP, ISP, referer, time), weighted stream rotation, offer/landing rotation, affiliate network resolution. Pipeline stages 7-12, 14-15.
**Deliverable**: Full traffic routing: click → match filters → pick stream → rotate offer → redirect
**Requirements**: Stream filter engine, weighted rotator, macro replacement, action execution (redirect/show page/proxy)

### Phase 3: Admin API — CRUD for All P0/P1 Entities
**Status**: ⬜ Not Started
**Objective**: RESTful JSON API for managing campaigns, streams, stream filters, offers, landings, domains, affiliate networks, traffic sources, users, settings. Auth via API key + session cookies.
**Deliverable**: Complete admin API that the frontend can consume
**Requirements**: All P0+P1 entity CRUD, Zod-equivalent validation in Go, pagination, filtering, auth middleware

### Phase 4: Bot Detection & Cloaking
**Status**: ⬜ Not Started
**Objective**: Multi-layer bot detection engine: IP database checks (datacenter/VPN/Tor ranges), ISP blacklisting, UA pattern matching, JS fingerprint challenges, rate limiting. Safe page system with configurable strategies (fake 404, redirect, proxy, local HTML).
**Deliverable**: Bots/scanners see safe pages, real users see offers
**Requirements**: Bot IP databases, FingerprintJS integration, safe page templates, cloaking configuration per campaign

### Phase 5: Conversion Tracking & Analytics
**Status**: ⬜ Not Started
**Objective**: Postback (S2S) conversion tracking, ClickHouse integration for click storage, real-time stats aggregation (daily/hourly), reporting API with drilldowns by campaign/geo/device/source/time.
**Deliverable**: Full click→conversion attribution, live revenue dashboards
**Requirements**: Postback endpoint, ClickHouse schema, async click writer, stats aggregation, report builder

### Phase 6: Admin Dashboard UI
**Status**: ⬜ Not Started
**Objective**: React SPA admin dashboard with campaign management, click log viewer, conversion reports, real-time stats. Served as static files from the Go binary.
**Deliverable**: Production admin interface matching Keitaro's UX
**Requirements**: React + shadcn/ui, embedded in Go binary via `embed`, responsive dark theme

### Phase 7: Production Hardening
**Status**: ⬜ Not Started
**Objective**: Production readiness — graceful shutdown, health checks, metrics (Prometheus), structured logging, config validation, Docker image, systemd unit, backup/restore, performance benchmarks.
**Deliverable**: Battle-tested binary ready for live affiliate traffic
**Requirements**: Benchmarks proving <5ms p99, load testing, monitoring, deployment docs
