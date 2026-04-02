---
phase: 3
level: 2
researched_at: 2026-04-02
---

# Phase 3 Research — Admin API

## Questions Investigated

1. What is the exact Keitaro Admin API surface for P0/P1 entities?
2. How does the cache warmup trigger work on entity save?
3. What auth model does Keitaro use and how do we port it?
4. What Go patterns should we use for handler/repository layering?

## Findings

### 1. Keitaro Admin API Surface (from Initializer.php files)

**Campaigns** (11 routes):
- `GET /campaigns` — list all
- `GET /campaigns/:id` — show
- `POST /campaigns` — create
- `PUT /campaigns/:id` — update
- `DELETE /campaigns/:id` — archive
- `GET /campaigns/deleted` — list archived
- `POST /campaigns/:id/restore` — restore from archive
- `POST /campaigns/:id/disable` — disable
- `POST /campaigns/:id/enable` — enable
- `POST /campaigns/:id/clone` — clone
- `POST /campaigns/:id/update_costs` — update costs

**Streams** (14 routes):
- `GET /campaigns/:campaign_id/streams` — list by campaign
- `GET /streams/:id` — show
- `POST /streams` — create
- `PUT /streams/:id` — update
- `DELETE /streams/:id` — archive
- `GET /streams/deleted` — list archived
- `POST /streams/:id/restore` — restore
- `POST /streams/:id/enable` — enable
- `POST /streams/:id/disable` — disable
- `GET /streams/search` — search
- `GET /stream_types` — list types
- `GET /stream_actions` — list actions
- `GET /stream_schemas` — list schemas
- `GET /stream/:id/events` — stream events (P2, skip)

**Offers** (6 core routes, skip editor routes for v1):
- `GET /offers` — list all
- `GET /offers/:id` — show
- `POST /offers` — create
- `PUT /offers/:id` — update
- `DELETE /offers/:id/archive` — archive
- `POST /offers/:id/clone` — clone

**Landings** (6 core routes, skip editor routes for v1):
- `GET /landing_pages` — list all
- `GET /landing_pages/:id` — show
- `POST /landing_pages` — create
- `PUT /landing_pages/:id` — update
- `DELETE /landing_pages/:id` — archive
- `PUT /landing_pages/:id/clone` — clone

**Affiliate Networks** (6 routes):
- `GET /affiliate_networks` — list all
- `GET /affiliate_networks/:id` — show
- `POST /affiliate_networks` — create
- `PUT /affiliate_networks/:id` — update
- `DELETE /affiliate_networks/:id` — archive
- `POST /affiliate_networks/:id/clone` — clone

**Traffic Sources** (similar CRUD pattern): 5 routes
**Domains** (similar CRUD pattern): 5 routes
**Users**: 5 routes (list, show, create, update, delete)
**Settings**: GET + PUT (single-row settings)
**Stream Filters**: GET /stream_filters (list available types)

### 2. Cache Warmup on Entity Save

Keitaro pattern (from `Core/EntityEventManager/EventHandler/`):
- On every campaign/stream/offer/landing save/update/delete, an event handler
  calls `WarmupScheduler::schedule()`.
- The warmup scheduler sets a flag in Redis (`warmup:scheduled = 1`).
- A background cron task checks this flag every 5 seconds.
- If set, it re-runs the full warmup (same as boot warmup).

**Go implementation strategy:**
- Add a `ScheduleWarmup()` method on the Cache service.
- Each admin mutation handler calls `cache.ScheduleWarmup()` after DB write.
- Background worker checks for the flag via a ticker (every 5s).
- Simple, robust, matches Keitaro exactly.

### 3. Auth Model

Keitaro uses PHP sessions + API key header.
Our plan (ADR-005):
- Login endpoint → session token in Valkey (32-byte hex, 24h TTL).
- HTTP-only Secure cookie for browser.
- `X-Api-Key` header for programmatic access (API keys stored in DB).
- Middleware validates session on every admin request.

**Phase 3 auth scope:**
- API key auth only (simplest, unblocks all CRUD testing).
- Session-based auth deferred to Phase 6 (when admin UI needs cookies).
- Default API key seeded during migration (like Keitaro's default admin user).

### 4. Go Handler Architecture Patterns

**Repository pattern (not raw queries in handlers):**
```
Handler → validates request → calls Repository → returns JSON
Repository → executes SQL query → returns model
```

**Why not sqlc yet:** We have a small number of straightforward queries.
Hand-written pgx queries in the repository layer are simpler to start with.
sqlc can be introduced when query count exceeds ~30 or when we need
compile-time verification at scale.

**Handler pattern:**
```go
func (h *Handler) handleCreateCampaign(w http.ResponseWriter, r *http.Request) {
    var req CreateCampaignRequest
    if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
        respondError(w, http.StatusBadRequest, "invalid json")
        return
    }
    if err := req.Validate(); err != nil {
        respondError(w, http.StatusUnprocessableEntity, err.Error())
        return
    }
    campaign, err := h.repo.CreateCampaign(r.Context(), req)
    if err != nil {
        respondError(w, http.StatusInternalServerError, "db error")
        return
    }
    h.cache.ScheduleWarmup()
    respondJSON(w, http.StatusCreated, campaign)
}
```

## Decisions Made

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Auth for Phase 3 | API key only | Unblocks all testing, session auth in Phase 6 |
| Query layer | pgx direct in repo | Simple for <30 queries, sqlc later |
| Cache warmup | Flag-based scheduler | Matches Keitaro pattern exactly |
| Entity scope | 8 entities | campaigns, streams, offers, landings, networks, sources, domains, users |

## Patterns to Follow
- Repository pattern: `internal/admin/repository/` contains all DB access
- Handler pattern: `internal/admin/handler/` contains all HTTP logic
- JSON helpers: `respondJSON()` and `respondError()` utilities
- Middleware: API key validation on all `/api/v1/*` routes (except health)

## Anti-Patterns to Avoid
- Raw SQL in handlers: always go through repository
- Direct cache writes in handlers: always use ScheduleWarmup()
- Monolithic handler files: one file per entity (campaigns.go, offers.go, etc.)

## Risks
- Schema drift: models.go struct tags must match DB columns exactly
- Cache invalidation: must not forget ScheduleWarmup() on any mutation

## Ready for Planning
- [x] Questions answered
- [x] Approach selected
- [x] Dependencies identified (no new deps needed — pgx + chi already in go.mod)
