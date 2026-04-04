---
phase: 3
plan: 5
wave: 3
---

# Plan 3.5: Cache Warmup Scheduler + Integration Testing

## Objective
Implement the flag-based cache warmup scheduler (matching Keitaro's
`WarmupScheduler` pattern) and write integration tests that prove the
entire admin API works end-to-end: create entities via API, verify they
appear in Valkey cache, and confirm the click pipeline can route traffic
using admin-created campaigns.

## Context
- .gsd/phases/3/4-PLAN.md — All entity CRUD (must be complete)
- internal/cache/cache.go — Existing Warmup() method
- internal/worker/manager.go — Background worker manager (if exists)
- internal/server/server.go — Worker lifecycle
- reference/Keitaro_source_php — WarmupScheduler pattern

## Tasks

<task type="auto">
  <name>Upgrade existing CacheWarmupWorker to call real Warmup()</name>
  <files>
    internal/worker/cache_warmup.go (MODIFY — AUDIT FIX #2: upgrade, NOT create new scheduler)
  </files>
  <action>
    AUDIT FIX #2: A `CacheWarmupWorker` already exists at `internal/worker/cache_warmup.go`.
    It already polls `warmup:scheduled` every 30 seconds and deletes the flag.
    It is a Phase 1 stub — it logs but does NOT call `cache.Warmup()`.

    DO NOT create `internal/cache/scheduler.go` — that would create two competing
    goroutines polling the same Valkey key, causing flag starvation.

    Instead, upgrade the existing worker:

    1. Modify `internal/worker/cache_warmup.go`:
       - Add `cache *cache.Cache` field to `CacheWarmupWorker` struct
       - Update constructor: `NewCacheWarmupWorker(valkey, cache, logger)`
       - Change ticker interval from `30 * time.Second` to `5 * time.Second`
         (matches Keitaro's ~5s warmup debounce)
       - Replace the stub log line with actual warmup call:
         ```go
         if err := w.cache.Warmup(ctx); err != nil {
             w.logger.Error("cache warmup failed", zap.Error(err))
         } else {
             w.logger.Info("scheduled cache warmup complete")
         }
         ```

    2. Update `internal/server/server.go` worker initialization
       (already updated in Plan 3.1) to pass `s.cache` to the constructor:
       ```go
       worker.NewCacheWarmupWorker(s.valkey, s.cache, logger)
       ```

    IMPORTANT: The ScheduleWarmup() method on Cache (created in Plan 3.1)
    sets the same `warmup:scheduled` key that this worker polls. No new
    goroutines or schedulers needed — the existing worker pattern is correct.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - Existing CacheWarmupWorker upgraded from stub to real warmup
    - 5-second polling interval (was 30s) with natural debounce
    - No duplicate scheduler — single goroutine polls single flag
    - Graceful shutdown via context cancellation (already implemented)
  </done>
</task>

<task type="auto">
  <name>Admin API integration test</name>
  <files>
    test/integration/admin_test.go (NEW)
  </files>
  <action>
    1. Create `test/integration/admin_test.go` with build tag `//go:build integration`:
       - Start the full server (reuse existing Docker Compose setup)
       - Test flow:

       ```
       a. GET /api/v1/health → 200 (no auth required)
       b. GET /api/v1/campaigns → 401 (no API key)
       c. Look up admin user's API key from DB
       d. Create affiliate network: POST /affiliate_networks → 201
       e. Create offer: POST /offers → 201 (with network ID)
       f. Create landing: POST /landing_pages → 201
       g. Create campaign: POST /campaigns → 201
       h. Create stream: POST /streams → 201 (with campaign_id, action_type=HttpRedirect)
       i. Link offer to stream: POST /streams/{id}/offers → 200
       j. Link landing to stream: POST /streams/{id}/landings → 200
       k. Wait 6 seconds for warmup scheduler
       l. Send click to /{campaign_alias} → 302 (verify pipeline uses admin-created data)
       m. Verify redirect URL matches the offer URL
       ```

       This proves the full admin→cache→pipeline integration works.

    2. Helper functions:
       - `apiRequest(method, path, body, apiKey) (*http.Response, error)`
       - `mustJSON(v interface{}) io.Reader`

    IMPORTANT: The test must wait for the warmup scheduler (6s minimum) before
    sending click traffic, otherwise the cache won't have the new entities.
  </action>
  <verify>echo "Integration test file created — run with: go test -tags integration ./test/integration/ -run TestAdminAPI -v"</verify>
  <done>
    - Integration test proves full admin→warmup→pipeline flow
    - API key auth tested (401 without, 200 with)
    - Entity CRUD verified end-to-end
    - Click pipeline routes traffic using admin-created campaign
  </done>
</task>

## Success Criteria
- [ ] `go build ./...` succeeds
- [ ] Cache warmup scheduler runs as background goroutine
- [ ] Admin-created entities appear in Valkey cache within 5 seconds
- [ ] Integration test passes: create campaign via API → send click → get 302 to offer URL
- [ ] Total Phase 3 API surface: ~60+ endpoints across 8 entities + associations + settings
