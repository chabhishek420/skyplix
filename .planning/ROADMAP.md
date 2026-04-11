# ROADMAP: zai-tds

## Milestones

- ✅ **v1.0 Core TDS** — Phases 1-8 (shipped)
- 🚧 **v1.1 Enterprise Features** — Phases 9-12 (in progress)

<details>
<summary>Shipped Milestone: v1.0 Core TDS</summary>

### v1.0 Phases

- [x] **Phase 1: Foundation** - Config, basic models, click pipeline skeleton, and click ID generation.
- [x] **Phase 2: Campaign Engine** - Multi-tier stream selection, weights, filters (Geo, Device, Network), and uniqueness tracking.
- [x] **Phase 3: Actions & Landers** - Redirect types (302/Meta/JS), A/B rotation for landers/offers, and macro expansion.
- [x] **Phase 4: Advanced Bot Detection & Cloaking** - JA3 fingerprinting, behavioral analysis, IP blacklists, and safe-page serving.
- [x] **Phase 5: Conversion Tracking & Analytics** - Postback processing, ClickHouse integration, and real-time click/conv streams.
- [x] **Phase 6: Admin API & Dashboard Scaffold** - REST API for management and a basic UI interface.
- [x] **Phase 7: Production Hardening** - Performance benchmarking, high availability setup, and final documentation.
- [x] **Phase 8: Layer Parity & Tracking** - URL param injection, referrer spoofing, sticky rotation binding.

### v1.0 Phase Details

### Phase 1: Foundation
**Goal**: Establish core infrastructure and a baseline click pipeline.
**Depends on**: Nothing
**Requirements**: FEAT-01, SEC-01, MGMT-02
**Success Criteria**:
  1. Server accepts incoming requests and generates a secure CSPRNG click ID.
  2. Baseline configuration (Viper) and structured logging (Zap) are initialized.
  3. PostgreSQL schema is defined for campaigns and streams.
**Plans**: Done

### Phase 2: Campaign Engine
**Goal**: Implement hierarchical routing and traffic filtering.
**Depends on**: Phase 1
**Requirements**: FEAT-02, FEAT-03, FEAT-04, DATA-05
**Success Criteria**:
  1. Traffic is correctly routed through Forced -> Regular -> Default streams.
  2. Filters for Geo, OS, Browser, and ISP correctly include/exclude traffic.
  3. Regular streams respect configured weights/positions during rotation.
  4. Visitor uniqueness is tracked and enforced using Valkey.
**Status**: Partial historical completion
**Plans**: Historical

### Phase 3: Actions & Landers
**Goal**: Support landing pages, offers, and various redirection methods.
**Depends on**: Phase 2
**Requirements**: FEAT-05, FEAT-06, FEAT-07
**Success Criteria**:
  1. Support 302, Meta-refresh, and JS-based redirects.
  2. Rotation between multiple landing pages and offers using weights.
  3. Dynamic macros (e.g., `{click_id}`) are expanded in destination URLs.
**Plans**: Historical

### Phase 4: Advanced Bot Detection & Cloaking
**Goal**: Implement advanced security measures to detect bots and moderators.
**Depends on**: Phase 2
**Requirements**: SEC-02, SEC-03, SEC-04, SEC-05
**Success Criteria**:
  1. Detect bots using JA3/TLS fingerprints.
  2. Implement safe-page serving logic for detected bots.
  3. Filter traffic using global CIDR/IP blacklists.
  4. Support basic JS-based behavioral fingerprinting.
**Plans**: Done

### Phase 5: Conversion Tracking & Analytics
**Goal**: Real-time data processing and conversion attribution.
**Depends on**: Phase 3
**Requirements**: DATA-01, DATA-02, DATA-03, DATA-04
**Success Criteria**:
  1. Postbacks are received, validated, and attributed to specific clicks.
  2. Clicks and conversions are streamed to ClickHouse in real-time.
  3. Analytics queries provide sub-second responses for basic metrics.
**Plans**: Historical

### Phase 6: Admin API & Dashboard Scaffold
**Goal**: Provide management interfaces for system administration.
**Depends on**: Phase 1
**Requirements**: MGMT-01, MGMT-03
**Success Criteria**:
  1. REST API endpoints for CRUD operations on campaigns, streams, and offers.
  2. Basic dashboard UI loads and displays campaign list.
  3. User authentication (JWT) for the admin interface.
**Plans**: Historical

### Phase 7: Production Hardening
**Goal**: Ensure the system meets performance and availability targets.
**Depends on**: Phase 5, Phase 6
**Requirements**: PERF-01, PERF-02, PERF-03, PERF-04
**Success Criteria**:
  1. Single instance handles 10k+ RPS with sub-10ms latency.
  2. Zero-downtime deployments and support for horizontal scaling.
  3. Comprehensive documentation for deployment and operations.
**Plans**: Historical

### Phase 8: Layer Parity & Tracking
**Goal**: Close remaining gaps between zai-tds and Keitaro's 5-layer architecture.
**Depends on**: Phase 4, Phase 3
**Requirements**: FEAT-05, SEC-02
**Success Criteria**:
  1. `_token` and `_subid` auto-injected into outgoing affiliate network URLs.
  2. `allow_change_referrer` config enables `?referrer=` URL override.
  3. Sticky rotation binding keeps same visitor on same landing/offer.
**Plans**: Done (.planning/phases/08-layer-parity/PLAN.md)

### v1.0 Progress Table

| Phase | Plans Complete | Status | Completed | Notes |
|-------|----------------|--------|-----------|-------|
| 1. Foundation | 1/1 | Completed | 2026-03-20 | |
| 2. Campaign Engine | 1/1 | Completed | 2026-03-22 | |
| 3. Actions & Landers | 1/1 | Completed | 2026-03-25 | |
| 4. Bot Detection | 1/1 | Completed | 2026-03-29 | |
| 5. Analytics | 1/1 | Completed | 2026-04-01 | |
| 6. Admin Interface | 1/1 | Completed | 2026-04-04 | |
| 7. Hardening | 1/1 | Completed | 2026-04-06 | |
| 8. Layer Parity | 1/1 | Completed | 2026-04-10 | PHP parity validated |

</details>

## Roadmap v1.1: Enterprise Features

### Phases

- [x] **Phase 9: Multi-Tenant Support** - Organization isolation, API keys, and per-tenant rate limits.
- [ ] **Phase 10: Advanced Analytics** - Real-time dashboards, cohort analysis, and funnel visualization.
- [ ] **Phase 11: Webhook Notifications** - Real-time conversion alerts via webhooks.
- [ ] **Phase 12: ML Optimization** - AI-based traffic allocation based on performance data.

### Phase Details

### Phase 9: Multi-Tenant Support
**Goal**: Introduce tenant-aware isolation for data, auth, and runtime limits.
**Depends on**: Phase 8
**Requirements**: MGMT-04, SEC-06, API-01
**Success Criteria**:
  1. Every mutable/admin request resolves an authenticated tenant context.
  2. Tenant boundaries are enforced across PostgreSQL, ClickHouse, and Valkey access paths.
  3. Per-tenant API key and rate-limit policies are configurable and enforced.
**Plans**: Completed (`09-01-SUMMARY.md`).

### Phase 10: Advanced Analytics
**Goal**: Provide higher-order analytics experiences on top of existing click/conversion data.
**Depends on**: Phase 9
**Requirements**: DATA-06, DATA-07
**Success Criteria**:
  1. Dashboard endpoints return near-real-time campaign and stream metrics.
  2. Cohort and funnel views are queryable for configurable date windows.
  3. Core analytics endpoints stay within sub-second response budgets on baseline datasets.
**Plans**: Not started

### Phase 11: Webhook Notifications
**Goal**: Emit reliable real-time conversion notifications to external systems.
**Depends on**: Phase 9
**Requirements**: INTEGRATION-02, RELIABILITY-01
**Success Criteria**:
  1. Webhook endpoints can be configured per tenant with secret signing.
  2. Delivery retries and dead-letter behavior are observable and testable.
  3. Conversion events are delivered at-least-once with idempotency guidance.
**Plans**: Not started

### Phase 12: ML Optimization
**Goal**: Add model-assisted traffic allocation with safe rollout controls.
**Depends on**: Phase 10, Phase 11
**Requirements**: OPT-01, DATA-08
**Success Criteria**:
  1. Optimization mode can be toggled per campaign with clear fallback to deterministic routing.
  2. Allocation decisions are explainable via logged feature inputs and selected outcomes.
  3. Offline/online validation demonstrates measurable lift over baseline rules on sample cohorts.
**Plans**: Not started

### Progress Table

| Phase | Plans Complete | Status | Completed | Notes |
|-------|----------------|--------|-----------|-------|
| 9. Multi-Tenant Support | 1/1 | Completed | 2026-04-11 | Tenant context foundation shipped |
| 10. Advanced Analytics | 0/1 | Not started | - | |
| 11. Webhook Notifications | 0/1 | Not started | - | |
| 12. ML Optimization | 0/1 | Not started | - | |
