---
phase: 4
plan: 3
wave: 2
---

# Plan 4.3: Safe Page System + Remote Action TTL Cache

## Objective
Implement the production safe page delivery system. When a bot is detected, the stream's action serves a "safe page" instead of the real offer. Enhance the existing `RemoteProxyAction` with an in-memory TTL cache (60s, matching Keitaro's `Remote.php` pattern). Add custom UA signature storage in Valkey with admin API.

## Context
- `internal/action/proxy.go` — Existing RemoteProxyAction (59 lines, no cache)
- `internal/action/content.go` — LocalFile, ShowHtml, Status404, DoNothing already implemented
- `reference/Keitaro_source_php/application/Traffic/Actions/Predefined/Remote.php` — TTL cache pattern (60s file cache)
- `internal/pipeline/stage/3_build_raw_click.go` — CustomUA interface from Plan 4.2

## Tasks

<task type="auto">
  <name>Enhance RemoteProxyAction with in-memory TTL cache</name>
  <files>internal/action/proxy.go</files>
  <action>
    Rewrite `internal/action/proxy.go` to add TTL-based caching:

    1. **Add cache struct:**
       ```go
       type cacheEntry struct {
           body        []byte
           contentType string
           statusCode  int
           fetchedAt   time.Time
       }
       ```

    2. **Add cache to RemoteProxyAction:**
       - Change struct to include `cache sync.Map` and `ttl time.Duration` (default 60s)
       - `NewRemoteProxyAction(ttl time.Duration) *RemoteProxyAction` constructor

    3. **Modify Execute():**
       - Compute cache key: `sha256(redirectURL)` (match Keitaro's `md5($url)` pattern but use sha256)
       - Check cache: if entry exists and `time.Since(entry.fetchedAt) < ttl`, serve from cache
       - On cache miss: fetch remote, store response body + Content-Type + status in cache, then serve
       - On fetch error: if stale cache entry exists, serve stale (graceful degradation)
       - Limit response body to 10MB to prevent memory bombs

    4. **Keep existing header copying** (Content-Type, Cache-Control) but serve from cached bytes instead of streaming.

    5. **Add `rewriteRelativeURLs`** — Enhance the existing stub to also handle `href="/` patterns (not just `src="/`). Use simple string replacement (full HTML parser deferred).

    **Why in-memory not file-based:** Go process is long-lived (unlike PHP per-request). sync.Map with TTL is simpler and faster than filesystem I/O. Memory is bounded by 10MB * unique_URLs.
  </action>
  <verify>go build ./internal/action/...</verify>
  <done>RemoteProxyAction serves cached responses within 60s TTL, falls back to stale on error</done>
</task>

<task type="auto">
  <name>Custom UA signature store (Valkey-backed) + admin API</name>
  <files>internal/botdb/uastore.go, internal/admin/handler/bots.go, internal/server/routes.go</files>
  <action>
    1. Create `internal/botdb/uastore.go`:
       - `UAStore struct { mu sync.RWMutex; patterns []string; client *redis.Client }`
       - `NewUAStore(client *redis.Client) *UAStore` — Load from Valkey key `botdb:ua_patterns`
       - `Patterns() []string` — Return snapshot (satisfies the CustomUA interface from Plan 4.2)
       - `Add(patterns string) error` — Parse newline/comma-separated patterns, deduplicate, lowercase, save to Valkey
       - `Remove(pattern string) error` — Remove pattern, save to Valkey
       - `Replace(patterns string) error` — Clear and replace
       - `Clear() error` — Empty store
       - `List() []string` — Return all patterns

    2. Add admin endpoints to `internal/admin/handler/bots.go`:
       - `POST /api/v1/bots/ua` — Body: `{"patterns": "mybot\ncustomcrawler"}`. Calls `UAStore.Add()`.
       - `DELETE /api/v1/bots/ua` — Body: `{"pattern": "mybot"}`. Calls `UAStore.Remove()`.
       - `GET /api/v1/bots/ua` — Returns `{"patterns": [...], "count": N}`.

    3. Wire `UAStore` into `server.go`:
       - Create `s.uaStore = botdb.NewUAStore(vk)`
       - Pass to `BuildRawClickStage` as `CustomUA: s.uaStore`

    4. Wire routes: add UA endpoints under existing `/bots` route group.
  </action>
  <verify>go build ./...</verify>
  <done>Custom UA patterns persist in Valkey, admin can add/remove/list, pipeline checks them on every click</done>
</task>

## Success Criteria
- [ ] `RemoteProxyAction` caches remote responses for 60s (in-memory TTL)
- [ ] Stale cache served on fetch failure (graceful degradation)
- [ ] Response body capped at 10MB
- [ ] Custom UA patterns stored in Valkey, loaded on startup
- [ ] Admin API: 3 endpoints for UA pattern management
- [ ] `go build ./...` compiles cleanly
