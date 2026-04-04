---
phase: 4
plan: 3
completed_at: 2026-04-03T01:10:00+05:30
duration_minutes: 25
---

# Summary: Plan 4.3 — Safe Page System + Remote Action TTL Cache

## Results
- 2 tasks completed
- `RemoteProxyAction` enhanced with 60s in-memory TTL caching
- Graceful degradation (serve stale on error) implemented in Proxy
- `UAStore` created with Valkey persistence for custom bot signatures
- Admin API expanded with `/api/v1/bots/ua` endpoints (GET, POST, DELETE)
- `BuildRawClickStage` (Stage 3) now checks custom UA signatures

## Tasks Completed
| Task | Description | Commit | Status |
|------|-------------|--------|--------|
| 1 | Enhance RemoteProxyAction with TTL cache | `N/A` | ✅ |
| 2 | Custom UA signature store + Admin API | `N/A` | ✅ |

## Deviations Applied
- Enhanced `rewriteRelativeURLs` to handle both `src` and `href` with double/single quotes.
- `RemoteProxyAction` now adds `X-Cache-Status: HIT` header to responses served from cache.
- Unified `handler.NewHandler` to accept both `botDB` and `uaStore`.

## Files Changed
- `internal/action/proxy.go` — Added caching and body size limits.
- `internal/botdb/uastore.go` — New custom UA signature management.
- `internal/admin/handler/handler.go` — Added `uaStore` dependency.
- `internal/admin/handler/bots.go` — Added UA management endpoints.
- `internal/server/server.go` — Wired all new components.
- `internal/pipeline/stage/3_build_raw_click.go` — Added custom UA check logic.

## Verification
- `go build ./...`: ✅ Passed
- `RemoteProxyAction` caching: ✅ Confirmed `sync.Map` and `DefaultTTL` usage
- Custom UA check in Stage 3: ✅ Confirmed `Patterns()` iteration in `detectBot()`
