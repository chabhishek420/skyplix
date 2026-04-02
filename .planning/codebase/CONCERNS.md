## Current Position
- **Phase**: 3 (Admin API)
- **Task**: 3.4 — Traffic Sources, Domains, Users, Settings CRUD
- **Status**: Paused at 2026-04-02 21:08

## Last Session Summary
Focused on implementing the Admin API CRUD surface. Successfully added migration `005` (Stream Limits & API Keys) and scaffolded handlers/repositories for Traffic Sources and Domains. Identified a drift where current implementation lacks specific "AUDIT FIX" requirements from `4-PLAN.md` (cloning, soft-deletion, restoration).

## In-Progress Work
- Migration `005_add_stream_limits_and_api_keys.up.sql` is applied.
- `internal/admin/handler/sources.go` — 5/6 endpoints implemented (missing `/clone`).
- `internal/admin/repository/domains.go` — Basic CRUD done, missing soft-delete/state-filtering logic.
- Files modified: `internal/admin/handler/sources.go`, `internal/admin/handler/streams.go`, `internal/admin/repository/domains.go`, `db/postgres/migrations/005_...`.
- Tests status: Not yet run for new CRUD surface.

## Blockers
- **Plan Drift**: The implementation has slightly diverted from the "AUDIT FIX #4" requirements in `4-PLAN.md`. Need to align on whether to implement the full 15 additional endpoints now.

## Context Dump
### Decisions Made
- **Migration 005**: Added `stream_limit` to `campaigns` and `api_key` to `users` to support upcoming admin features.
- **ScheduleWarmup**: All mutations correctly trigger cache warmup via `h.cache.ScheduleWarmup()`.

### Approaches Tried
- **Basic CRUD**: Followed standard repository/handler pattern; now needs extension for domain state management.

### Current Hypothesis
The core CRUD is functional, but scaling it to match the Keitaro reference (cloning, archivals) is the primary remaining gap for this task.

### Files of Interest
- `4-PLAN.md`: The "Source of Truth" for missing endpoints.
- `internal/admin/handler/sources.go`: Needs `/clone` endpoint.
- `internal/admin/repository/domains.go`: Needs `state` field support in SQL queries.

## Next Steps
1. **Align with Plan**: Implement missing endpoints (`/clone` for sources; `/deleted`, `/restore`, `/clone`, `/check` for domains).
2. **Soft-Delete Logic**: Update `DomainRepository` to set `state = 'archived'` instead of hard DELETE.
3. **Migration 006**: Create settings table and implement bulk-upsert handler.
4. **Users CRUD**: Complete secure password handling and access-data updates.
