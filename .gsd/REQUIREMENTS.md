# Requirements

<!-- Auto-generated from GSD database; do not edit directly -->

## Active

- **R001** — Click pipeline processing p99 latency <5ms at 1k RPS — **Active** (Phase 4.9.4 proven: 2.06ms p99)
- **R002** — Campaign/Stream/Offer CRUD with full admin API — **Validated** (Phase 3 complete)
- **R003** — Bot detection + cloaking (IP ranges, UA patterns, datacenter detection, safe page delivery) — **Validated** (Phase 4 complete)
- **R004** — Postback conversion tracking with S2S signature validation — **Active** (Phase 5 in progress)
- **R005** — Real-time analytics dashboard with drilldowns by campaign/geo/device/source — **Active** (Phase 5-6 pending)
- **R006** — Single binary deployment with embedded admin UI — **Active** (Phase 6-7 pending)
- **R007** — GeoIP detection and device classification on click ingestion — **Validated** (Phase 1 complete)
- **R008** — Async ClickHouse click batch ingestion with reliability — **Validated** (Phase 1, 4.9.4 complete)
- **R009** — Session affinity binding (recurring visitor → same stream/landing/offer) — **Validated** (Phase 2 complete)
- **R010** — Global uniqueness tracking across all clicks — **Validated** (Phase 4.9.4 complete)

## Deferred

- **R011** — Advanced behavioral analysis (timing, header consistency) — deferred to Phase 7
- **R012** — JS fingerprint challenges for bot verification — deferred to Phase 7
- **R013** — Third-party API integration (HideClick, IMKLO) — deferred to Phase 7
- **R014** — Pipeline recursion with state reset — deferred to Phase 7

## Out of Scope

- Consumer-facing product surfaces beyond TDS/admin operations
- Major architecture rewrite away from Go monolith + pipeline model
