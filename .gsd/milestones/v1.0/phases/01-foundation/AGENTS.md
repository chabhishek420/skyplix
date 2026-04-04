<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-03 -->

# .gsd/phases/1

## Phase 1: Foundation — Go Project + Core Pipeline + Background Workers
**Status**: ✅ VERIFIED PASS (2026-04-02)

## Key Deliverables
| # | Deliverable |
|---|-------------|
| 1 | Go project structure (`cmd/`, `internal/`, `db/`) |
| 2 | Docker Compose: PostgreSQL 16, Valkey 8, ClickHouse 24 |
| 3 | HTTP server (Chi v5) with click/admin route split |
| 4 | RawClick model (~60 fields) |
| 5 | Pipeline framework (stage slice, Payload struct, abort) |
| 6 | GeoIP integration (MaxMind mmdb) |
| 7 | Device detection (mileusna/useragent) |
| 8 | Basic bot detection (43 UA patterns, 5 CIDR, empty UA) |
| 9 | PostgreSQL schema + migrations (4 tables) |
| 10 | Stream↔Landing and Stream↔Offer association tables |
| 11 | ClickHouse click schema + async batch writer |
| 12 | Background worker goroutines (cache warmup, hitlimit reset, session janitor) |
| 13 | Campaign type field (POSITION vs WEIGHT) |

## Files
- `1-PLAN.md`, `2-PLAN.md`, `3-PLAN.md` — Execution plans
- `VERIFICATION.md` — 13/13 must-haves verified

## For AI Agents
This phase is the foundation. All subsequent phases build on the pipeline framework and database schemas established here.

<!-- MANUAL: -->
