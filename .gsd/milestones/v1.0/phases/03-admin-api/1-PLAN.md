---
phase: 3
plan: 1
wave: 1
---

# Plan 3.1: Admin API Foundation — Router, Auth Middleware, JSON Helpers

## Objective
Build the admin API skeleton: route group under `/api/v1/`, API key auth middleware,
JSON request/response helpers, and the repository + handler package structure. This
unblocks all subsequent entity CRUD plans.

## Context
- .gsd/SPEC.md — Admin API requirements
- .gsd/ARCHITECTURE.md — Admin API section, Valkey session auth (ADR-005)
- .gsd/phases/3/RESEARCH.md — Auth decision (API key for Phase 3)
- internal/server/server.go — Existing server struct with DB, Valkey, Cache
- internal/server/routes.go — Existing route wiring
- internal/config/config.go — Config struct
- db/postgres/migrations/004_create_domains_users.up.sql — Existing users table

## Tasks

<task type="auto">
  <name>Create admin JSON helpers and middleware</name>
  <files>
    internal/admin/handler/handler.go (NEW)
    internal/admin/handler/helpers.go (NEW)
    internal/admin/middleware.go (NEW)
  </files>
  <action>
    1. Create `internal/admin/handler/handler.go`:
       - Define `Handler` struct holding `*pgxpool.Pool`, `*cache.Cache`, `*zap.Logger`
       - Constructor `NewHandler(db, cache, logger)`

    2. Create `internal/admin/handler/helpers.go`:
       - `respondJSON(w http.ResponseWriter, status int, data interface{})` — marshals JSON, sets Content-Type
       - `respondError(w http.ResponseWriter, status int, message string)` — JSON error envelope `{"error": "message"}`
       - `parseUUID(s string) (uuid.UUID, error)` — validates UUID path params
       - `parsePagination(r *http.Request) (limit, offset int)` — reads `?limit=N&offset=M`, defaults 25/0

    3. Create `internal/admin/middleware.go`:
       - `APIKeyAuth(db *pgxpool.Pool) func(http.Handler) http.Handler`
       - Reads `X-Api-Key` header
       - Validates against DB: `SELECT id, role FROM users WHERE api_key = $1 AND state = 'active'`
       - Sets user ID + role in request context via `context.WithValue`
       - Returns 401 JSON error if invalid

    4. Add `ScheduleWarmup()` method to `internal/cache/cache.go` (AUDIT FIX #3):
       ```go
       // ScheduleWarmup sets a flag in Valkey indicating warmup is needed.
       // Called by admin handlers after any entity mutation.
       func (c *Cache) ScheduleWarmup() {
           c.vk.Set(context.Background(), "warmup:scheduled", "1", 30*time.Second)
       }
       ```
       This stub must exist NOW so Plans 3.2–3.4 can compile.
       The background worker that consumes this flag is upgraded in Plan 3.5.

    IMPORTANT: Do NOT use pgx row-scanning without proper error handling.
    IMPORTANT: Context key type should be a custom unexported type, not string, to avoid collisions.
  </action>
  <verify>go build ./internal/admin/...</verify>
  <done>
    - handler.go compiles with Handler struct and constructor
    - helpers.go has 4 helper functions that compile
    - middleware.go validates API key against DB and injects user context
  </done>
</task>

<task type="auto">
  <name>Add migrations, wire admin routes, start worker manager</name>
  <files>
    db/postgres/migrations/005_add_stream_limits_and_api_keys.up.sql (NEW)
    db/postgres/migrations/005_add_stream_limits_and_api_keys.down.sql (NEW)
    internal/server/routes.go (MODIFY)
    internal/server/server.go (MODIFY)
    internal/cache/cache.go (MODIFY — add ScheduleWarmup)
  </files>
  <action>
    1. Create migration `005_add_stream_limits_and_api_keys.up.sql` (AUDIT FIX #1):
       ```sql
       -- Fix: daily_limit and total_limit are in Go model + cache.go scans but missing from schema
       ALTER TABLE streams ADD COLUMN IF NOT EXISTS daily_limit BIGINT NOT NULL DEFAULT 0;
       ALTER TABLE streams ADD COLUMN IF NOT EXISTS total_limit BIGINT NOT NULL DEFAULT 0;

       -- API key auth for admin endpoints
       ALTER TABLE users ADD COLUMN IF NOT EXISTS api_key VARCHAR(64) UNIQUE;
       UPDATE users SET api_key = encode(gen_random_bytes(32), 'hex') WHERE api_key IS NULL;
       ```

    2. Create migration `005_add_stream_limits_and_api_keys.down.sql`:
       ```sql
       ALTER TABLE users DROP COLUMN IF EXISTS api_key;
       ALTER TABLE streams DROP COLUMN IF EXISTS daily_limit;
       ALTER TABLE streams DROP COLUMN IF EXISTS total_limit;
       ```

    3. Modify `internal/server/routes.go`:
       - Import `internal/admin/handler` and `internal/admin`
       - Add a new Chi route group: `r.Route("/api/v1", func(r chi.Router) { ... })`
       - Apply `admin.APIKeyAuth(s.db)` middleware to the group
       - Keep existing `/api/v1/health` outside the auth group (public)
       - Register placeholder routes for campaigns: GET/POST `/campaigns`, GET/PUT/DELETE `/campaigns/{id}`
       - Handler instance created in server.go and passed through

    4. Modify `internal/server/server.go` (AUDIT FIX #5):
       - Add `adminHandler *handler.Handler` field to Server struct
       - Initialize in `New()`: `s.adminHandler = handler.NewHandler(s.db, s.cache, s.logger)`
       - Initialize worker manager:
         ```go
         s.workers = worker.NewManager(logger,
             worker.NewCacheWarmupWorker(s.valkey, logger),
             worker.NewSessionJanitorWorker(logger),
         )
         ```
       - Start workers in `Run()` BEFORE HTTP server:
         ```go
         s.workers.StartAll(ctx)
         ```
       - Replace `wg.Wait()` in shutdown with `s.workers.Wait()`

    5. Add `ScheduleWarmup()` method to `internal/cache/cache.go`

    IMPORTANT: The health endpoint MUST remain public (no auth).
    IMPORTANT: Route order matters in Chi — more specific routes before catch-all.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - Migration 005 adds daily_limit + total_limit to streams AND api_key to users
    - /api/v1/* routes are guarded by API key middleware
    - /api/v1/health remains public
    - Worker manager initialized and started (cache warmup + session janitor)
    - ScheduleWarmup() method exists on Cache for use by plans 3.2–3.4
    - Server compiles with admin handler wired in
  </done>
</task>

## Success Criteria
- [ ] `go build ./...` succeeds
- [ ] Admin route group exists at `/api/v1/` with API key auth
- [ ] JSON helpers (respondJSON, respondError) exist and are importable
- [ ] Migration 005 adds api_key to users AND daily_limit/total_limit to streams
- [ ] `cache.ScheduleWarmup()` method exists and compiles
- [ ] Worker manager is initialized and started in server.go
