# Phase 3 Research: Admin API Implementation

## Objective
Determine the best patterns for RESTful CRUD, validation, and cache warmup in the SkyPlix TDS Go backend.

## Findings

### API Structure
- **Router**: Already using `go-chi/chi/v5`.
- **Middleware**: Need `Auth` (API Key) and `JSON` content-type enforcement.
- **Handlers**: Should be grouped by entity (e.g., `internal/server/admin_campaign.go`).

### CRUD Implementation
- **Repository Pattern**: Already somewhat present in `internal/cache`, but need direct DB access for Admin.
- **SQLC**: The `SPEC.md` mentions `sqlc` for near-native perf and zero reflection. I should check if `sqlc` is already configured.
- **Validation**: Use a simple validation library or manual checks. Given the "no magic" philosophy, manual or `go-playground/validator` are candidates.

### Cache Warmup
- When an entity is saved, we need to invalidate or update the Valkey cache.
- The `SPEC.md` mentions matching Keitaro's `WarmupScheduler` pattern.
- Implementation: An async trigger that re-reads the entity from PG and updates Valkey.

### Entity Relationships
- Campaigns have Streams.
- Streams have Filters, Offers, and Landings.
- Need to handle nested JSON or separate CRUD for associations.

## Recommendations
1. Use `sqlc` if possible, or `pgx` directly for complex queries.
2. Implement a generic `JSONResponse` helper.
3. Create a `WarmupService` that can be called from handlers.
