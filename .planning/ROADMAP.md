# ROADMAP.md

> **Milestone**: v1.0 — Conversion Tracking & Analytics
> **Goal**: Close the attribution loop (postbacks + reporting) while preserving the low-latency click pipeline.

## Phases

### Phase 01: Foundation
**Status**: ✅ Complete
**Objective**: Establish the core Go service, config loading, and baseline runtime wiring.

### Phase 01.5: Maintenance, Reliability & Robustness
**Status**: ✅ Complete
**Objective**: Hardening passes (timeouts, error handling, basic observability) without changing core architecture.

### Phase 02: Campaign Engine
**Status**: ✅ Complete
**Objective**: Deterministic click pipeline stages and action execution for routing traffic.

### Phase 03: Admin API
**Status**: ✅ Complete
**Objective**: CRUD APIs for core entities (campaigns/streams/offers/landings/etc.) to manage configuration.

### Phase 04: Advanced Cloaking & Bot Detection
**Status**: ✅ Complete
**Objective**: Filter and cloaking mechanics (UA/IP/datacenter/rate limiting/safe page) integrated into the pipeline.

### Phase 04.9: Gap Closure — Uniqueness Hardening
**Status**: ✅ Complete
**Objective**: Uniqueness/session logic and missing parity items required for stable attribution.

### Phase 05: Conversion Tracking & Analytics
**Status**: ✅ Complete
**Objective**: Implement conversion ingestion (postback + pixel), attribution lookup/dedup, and reporting/query surfaces.

### Phase 06: Admin Dashboard
**Status**: ✅ Complete
**Objective**: Ship a usable web dashboard UI for managing entities and viewing reports.

### Phase 07: Production Hardening
**Status**: ⏳ Pending
**Objective**: Operational hardening (configs, rollouts, safety rails, observability) for real traffic.
