# Requirements: zai-tds

## Core Functional Requirements

### 1. Campaign & Stream Architecture (Parity with Keitaro)
- **FEAT-01: Hierarchical Traffic Routing.** Support Campaign -> Stream -> [Lander] -> Offer structure.
- **FEAT-02: Stream Tiers.** Implement Forced, Regular, and Default stream types.
- **FEAT-03: Multi-tier Selection.** Select streams based on priority: Forced (Position) -> Regular (Weight/Position) -> Default.
- **FEAT-04: Advanced Filtering.** Filter traffic by Geo (Country/Region/City), Device (OS/Browser/Type), Connection (ISP/Mobile/Proxy), Schedule, and Custom Parameters.
- **FEAT-05: Rotation Engines.** Weighted rotation for Regular streams, Landers, and Offers.
- **FEAT-06: Action Handlers.** ✅ **IMPLEMENTED** - 19 action types: HttpRedirect, Meta, DoubleMeta, BlankReferrer, Js, JsIframe, JsScript, FormSubmit, Frame, Iframe, ShowHtml, ShowText, LocalFile, Status404, DoNothing, Curl, RemoteProxy, SubId, ToCampaign.
- **FEAT-07: Macro Substitution.** ✅ **IMPLEMENTED** - 40+ macros: {click_id}, {subid}, {tid}, {campaign_id}, {stream_id}, {country}, {city}, {region}, {device}, {os}, {browser}, {ip}, {isp}, {user_agent}, {referrer}, {sub_id_1-5}, {cost}, {payout}, {timestamp}, {date}, {random}, and more.

### 2. Performance & Scale
- **PERF-01: Throughput.** Sustain 10k+ Requests Per Second (RPS) on a single optimized instance.
- **PERF-02: Latency.** Maintain sub-10ms processing latency for the hot-path click pipeline.
- **PERF-03: Resource Efficiency.** Minimize heap allocations in the pipeline to prevent GC pauses.
- **PERF-04: High Availability.** Support stateless pipeline instances behind a load balancer with shared Valkey state.

### 3. Security & Detection
- **SEC-01: CSPRNG Click IDs.** Generate cryptographically secure, collision-resistant click identifiers.
- **SEC-02: JA3/TLS Fingerprinting.** ⚠️ **PLANNED** - Fields exist (JA3, JA4, TLSHost in RawClick), but extraction logic not yet implemented.
- **SEC-03: Cloaking / Safe-Page Logic.** Serve "safe content" to detected bots/moderators while redirecting "money" traffic.
- **SEC-04: Behavioral Detection.** ✅ **IMPLEMENTED** - HideClickDetect, ImkloDetect, JsFingerprint filters check bot scores, proxy flags, and third-party detection signals.
- **SEC-05: IP/CIDR Blacklisting.** ✅ **PARTIAL** - Basic implementation exists; comprehensive databases planned for Phase 4.

### 4. Analytics & Tracking
- **DATA-01: Real-time Analytics.** ✅ **IMPLEMENTED** - ClickHouse queue writer with batch inserts.
- **DATA-02: Postback Processing.** ✅ **IMPLEMENTED** - Handler with HMAC validation.
- **DATA-03: Conversion Attribution.** ✅ **IMPLEMENTED** - Token-based attribution via {click_id} macros.
- **DATA-04: Payout/Cost Tracking.** ✅ **IMPLEMENTED** - Cost/payout calculated in pipeline stages 16-17.
- **DATA-05: Uniqueness Tracking.** ✅ **IMPLEMENTED** - Valkey-based with global/campaign/stream limits.

### 5. Management & UI
- **MGMT-01: Admin API.** ✅ **IMPLEMENTED** - 26 files: handlers for campaigns, streams, offers, landings, networks, domains, sources, users, bots, settings, reports, postbacks.
- **MGMT-02: Metadata Storage.** ✅ **IMPLEMENTED** - PostgreSQL via pgx with repository pattern.
- **MGMT-03: Dashboard Scaffold.** 🔄 **IN PROGRESS** - React SPA scaffold exists, needs completion.

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| FEAT-01 | Phase 1 | Implemented |
| FEAT-02 | Phase 2 | Implemented |
| FEAT-03 | Phase 2 | Implemented |
| FEAT-04 | Phase 2 | ⚠️ Partial (includes Phase 4 filter stubs) |
| FEAT-05 | Phase 3 | Pending |
| FEAT-06 | Phase 3 | ✅ Implemented (19 types) |
| FEAT-07 | Phase 3 | ✅ Implemented (40+ macros) |
| PERF-01 | Phase 7 | Pending |
| PERF-02 | Phase 7 | Pending |
| PERF-03 | Phase 7 | Pending |
| PERF-04 | Phase 7 | Pending |
| SEC-01 | Phase 1 | Implemented |
| SEC-02 | Phase 4 | ⚠️ Planned (fields exist, no extraction) |
| SEC-03 | Phase 4 | Pending |
| SEC-04 | Phase 4 | ✅ Implemented (bot score/proxy checks) |
| SEC-05 | Phase 4 | ⚠️ Partial (basic impl) |
| DATA-01 | Phase 5 | ✅ Implemented |
| DATA-02 | Phase 5 | ✅ Implemented |
| DATA-03 | Phase 5 | ✅ Implemented |
| DATA-04 | Phase 5 | ✅ Implemented |
| DATA-05 | Phase 2 | ✅ Implemented |
| MGMT-01 | Phase 6 | ✅ Implemented |
| MGMT-02 | Phase 1 | ✅ Implemented |
| MGMT-03 | Phase 6 | 🔄 In Progress |
