<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-03 -->

# .gsd/phases/5

## Phase 5: Conversion Tracking & Analytics
**Status**: ⬜ Pending

## Planned Deliverables
| # | Deliverable |
|---|-------------|
| 1 | Attribution Service (Valkey + ClickHouse) |
| 2 | Postback (S2S) listener endpoint |
| 3 | ClickHouse Materialized Views for real-time stats |
| 4 | Reporting Query Builder (Campaign, Geo, Device drilldowns) |
| 5 | Postback URL template macros |
| 6 | Global uniqueness tracking (Valkey) |

## Files
- `1-PLAN.md`, `2-PLAN.md`, `3-PLAN.md` — Execution plans (planned)

## For AI Agents
Phase 5 is next after Phase 4.9 gap closure. Key prerequisite: implement `IsUnique` logic (Valkey set/get with TTL) before conversion tracking, as attribution depends on reliable click data.

## Important
Per v1.0-AUDIT.md: current pipeline returns `IsUnique = true` for ALL clicks. This skews conversion rates until uniqueness keys are implemented.

<!-- MANUAL: -->
