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
  <name>Implement cache warmup scheduler</name>
  <files>
    internal/cache/scheduler.go (NEW)
    internal/server/server.go (MODIFY — start scheduler goroutine)
  </files>
  <action>
    1. Create `internal/cache/scheduler.go`:
       ```go
       // ScheduleWarmup sets a flag in Valkey indicating warmup is needed.
       // Called by admin handlers after any entity mutation.
       func (c *Cache) ScheduleWarmup() {
           c.vk.Set(context.Background(), "warmup:scheduled", "1", 30*time.Second)
       }

       // RunWarmupScheduler checks the warmup flag every 5 seconds.
       // When set, runs full warmup and clears the flag.
       // Debounces rapid mutations (multiple saves within 5s = one warmup).
       func (c *Cache) RunWarmupScheduler(ctx context.Context) {
           ticker := time.NewTicker(5 * time.Second)
           defer ticker.Stop()
           for {
               select {
               case <-ctx.Done():
                   return
               case <-ticker.C:
                   val, err := c.vk.Get(ctx, "warmup:scheduled").Result()
                   if err != nil || val != "1" {
                       continue
                   }
                   c.vk.Del(ctx, "warmup:scheduled")
                   if err := c.Warmup(ctx); err != nil {
                       c.logger.Error("scheduled warmup failed", zap.Error(err))
                   } else {
                       c.logger.Info("scheduled cache warmup complete")
                   }
               }
           }
       }
       ```

    2. Start scheduler in `server.go` Run():
       - Add goroutine: `go s.cache.RunWarmupScheduler(ctx)`
       - Must be started BEFORE HTTP server begins accepting requests
       - Must respect context cancellation for graceful shutdown

    IMPORTANT: The scheduler debounces naturally — multiple ScheduleWarmup() calls
    within 5s result in a single actual warmup. This is intentional and matches Keitaro.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - ScheduleWarmup() method exists on Cache
    - RunWarmupScheduler goroutine runs in background
    - 5-second polling interval with natural debounce
    - Graceful shutdown via context cancellation
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
