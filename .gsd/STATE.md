# GSD State

## Current Position
- **Phase**: 4 — Advanced Cloaking & Bot Detection
- **Task**: Plan 4.5 Task 2 (Integration Tests) — partially written, not yet runnable
- **Status**: Paused at 2026-04-03T01:50:00+05:30

## Last Session Summary
Executed Phase 4 Plans 4.2 through 4.5 (Task 1). All code compiles cleanly (`go build ./...` and `go vet ./...` both pass). Manual curl verification confirmed cloaking works (human→302 redirect, bot→200 safe page).

## Completed Plans
| Plan | Name | Commit | Status |
|------|------|--------|--------|
| 4.1 | BotDB Engine (store.go) | Previous session | ✅ |
| 4.2 | Valkey Persistence + Pipeline + Admin API | `20970716` | ✅ |
| 4.3 | Safe Page TTL Cache + Custom UA Store | `ab3a32fb` | ✅ |
| 4.4 | Datacenter/ASN Detection + P1 Filters | `eb2d5e55` | ✅ |
| 4.5 | Rate Limiting + Integration Tests | N/A | ⏳ Task 1 done, Task 2 partial |

## In-Progress Work
- **Rate Limiter** (`internal/ratelimit/ratelimit.go`): Fully implemented, compiles, wired into server and pipeline
- **Integration Test** (`test/integration/cloaking_test.go`): Written but NOT yet run successfully
- **Seed SQL** (`test/integration/testdata/seed_phase4.sql`): Fixed (password_hash column), seeded into DB

### Files Modified (uncommitted)
- `internal/ratelimit/ratelimit.go` — NEW: Valkey-based IP rate limiting
- `internal/config/config.go` — Added `RateLimitPerIP`, `RateLimitWindow` fields + `time` import
- `internal/pipeline/stage/3_build_raw_click.go` — Added `context`/`time` imports, `RateLimiter` field, check #7
- `internal/server/server.go` — Added `ratelimit` import, wired `ratelimiter` into Server + pipelines
- `test/integration/cloaking_test.go` — NEW: 6 cloaking test cases + ClickHouse verification
- `test/integration/testdata/seed_phase4.sql` — NEW: Cloaking campaign seed with test user
- `.gsd/phases/4/4-SUMMARY.md` — Plan 4.4 completion summary

### Tests Status
- `go build ./...` ✅ CLEAN
- `go vet ./...` ✅ CLEAN
- `go vet -tags integration ./test/integration/` ✅ CLEAN
- Integration tests: NOT RUN (terminal processes frozen)

## Blockers
**Terminal process freezing** — Commands hang indefinitely in the terminal. Root cause: backgrounding `go run` with `&` in a persistent terminal caused cascading hangs. New commands sent to the same terminal queue behind stuck processes.

**Fix for next session:**
1. Kill ALL zombie processes first: `pkill -9 -f "zai-tds"; pkill -9 -f "go test"`
2. Start server in a DEDICATED terminal (not backgrounded with `&`)
3. Run curl and test commands in a SEPARATE terminal

## Context Dump

### Decisions Made
- **Rate limit defaults**: 60 req/min per IP, 1-minute window (matches Keitaro behavior)
- **Rate limit fail-open**: If Valkey is unreachable, allow traffic (don't block)
- **Datacenter detection heuristic**: 18 keywords (aws, hosting, datacenter, etc.) substring-matched against ASN org name
- **Custom UA patterns**: Stored in Valkey as JSON array (`botdb:ua_patterns` key), loaded on startup
- **RemoteProxyAction cache**: In-memory `sync.Map` with 60s TTL, 10MB body limit, stale-on-error

### Manual Verification Results (from server logs)
- Human UA (`Chrome/120`) from `8.8.8.8` → `302 Found`, `Location: https://real-offer.com` ✅
- Googlebot UA from `66.249.66.1` → `200 OK`, `ShowHtml` action, `is_bot: true` ✅
- Empty UA from `2.2.2.2` → `200 OK`, `ShowHtml` action, `is_bot: true` ✅
- SputnikBot (expanded pattern) → `is_bot: true` ✅

### Files of Interest
- `internal/pipeline/stage/3_build_raw_click.go` — The 7-layer bot detection pipeline (UA, IP prefix, BotDB, CustomUA, Datacenter, Rate Limit)
- `internal/action/proxy.go` — RemoteProxyAction with TTL cache for safe page serving
- `internal/botdb/uastore.go` — Custom UA signature management with Valkey persistence
- `internal/geo/geo.go` — Enhanced with ASN database + `IsDatacenter()` heuristic
- `internal/filter/traffic.go` — New `ReferrerStopwordFilter` and `UrlTokenFilter`
- `internal/filter/network.go` — New `IspBlacklistFilter`

## Next Steps
1. **Kill all zombie processes** — Start clean
2. **Commit Plan 4.5 Task 1** — Rate limiting code is done, just needs git commit
3. **Run integration tests** — Start server in dedicated terminal, run tests in separate one
4. **Complete Plan 4.5 verification** — Write 5-SUMMARY.md
5. **Phase 4 verification** — Run full `go build/vet/test` and create VERIFICATION.md
6. **Update ROADMAP.md** — Mark Phase 4 complete
