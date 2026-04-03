# SkyPlix TDS (zai-yt-keitaro)

## What This Is

SkyPlix TDS is a high-performance Go traffic distribution and analytics platform inspired by Keitaro, designed to route affiliate traffic, apply cloaking/bot filtering, and record click/conversion analytics in real time. It serves two primary user groups: media buyers/affiliate operators who manage campaigns and streams, and internal ops/admin users who manage entities and monitor performance.

The system runs as a single backend service with PostgreSQL for config entities, Valkey for hot-path state/caching, and ClickHouse for analytics ingestion.

## Core Value

Accurately route every incoming click to the right destination under strict low-latency constraints while preserving reliable attribution and analytics.

## Requirements

### Validated

- ✓ End-to-end click pipeline with staged execution and action handling — existing (Phase 1/2)
- ✓ Advanced cloaking and bot detection baseline (UA/IP/datacenter/rate-limit/safe-page flows) — existing (Phase 4)
- ✓ Admin API CRUD foundation for core entities — existing (Phase 3)
- ✓ Async ClickHouse click ingestion with background workers and operational stability baseline — existing (Phase 1 + 4.9.4)

### Active

- [ ] Implement full conversion attribution and postback ingestion (Phase 5)
- [ ] Deliver reporting/query layer and real-time stats surfaces (Phase 5)
- [ ] Build production admin dashboard UI consuming admin/reporting APIs (Phase 6)
- [ ] Complete production hardening, benchmark proofs, and deployment readiness (Phase 7)

### Out of Scope

- Consumer-facing product surfaces beyond TDS/admin operations — not part of this system’s purpose
- Major architecture rewrite away from current Go monolith + pipeline model — would derail milestone completion

## Context

- Brownfield continuation project with substantial implementation already completed through cloaking and routing milestones.
- Existing planning knowledge and execution history is tracked in `.gsd/` (ROADMAP, STATE, TODO, JOURNAL, milestones/phases artifacts).
- Codebase map is already available in `.planning/codebase/` and confirms the architecture/data-path used by the current implementation.
- Current execution focus is transitioning into conversion tracking and analytics completion.

## Constraints

- **Performance**: Click-path latency target remains strict (<5ms p99 target) — core differentiator
- **Architecture**: Keep deterministic pipeline stage behavior and low-allocation hot path — aligns with current code and AGENTS guidance
- **Infrastructure**: Must operate with PostgreSQL + Valkey + ClickHouse as foundational stores
- **Compatibility**: Continue Keitaro-inspired behavior where already implemented/validated to avoid routing regressions

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Continue from existing brownfield `.gsd` state instead of restarting scope | The project already has validated phases and implementation momentum | ✓ Good |
| Keep Go monolith + stage pipeline as the primary architecture | Matches current codebase and performance goals; avoids destabilizing rewrites | ✓ Good |
| Prioritize conversion attribution/reporting before UI polish | Highest remaining value is closing tracking loop and analytics correctness | — Pending |

---
*Last updated: 2026-04-03 after initialization*
