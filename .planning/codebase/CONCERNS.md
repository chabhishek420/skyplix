# Codebase Concerns

**Analysis Date:** 2026-04-03

## Tech Debt

**Startup error handling is partially suppressed:**
- Issue: `internal/server/server.go` ignores errors for GeoIP and ClickHouse writer init (`geo.New(...)` and `queue.NewWriter(...)` use `_` for error).
- Why: likely to keep the server running in dev even without optional deps.
- Impact: production misconfig can silently disable GeoIP resolution and/or analytics ingestion.
- Fix approach: decide whether these deps are required; if optional, log warnings and expose health status; if required, fail fast.

**Remote proxy cache is unbounded:**
- Issue: `internal/action/proxy.go` uses `sync.Map` TTL cache but does not evict expired entries proactively.
- Impact: memory growth over time under high URL diversity.
- Fix approach: add bounded cache (LRU) and/or periodic cleanup.

## Known Bugs

**Potentially misleading health semantics for dependencies:**
- Symptoms: `/api/v1/health` always returns `{"status":"ok"}` without checking Postgres/Valkey/ClickHouse.
- Trigger: downstream services are down but process is still up.
- Workaround: check logs and/or add richer health endpoint.
- Root cause: handler in `internal/server/routes.go` is static.

## Security Considerations

**Admin API key lookup is plaintext:**
- Risk: `users.api_key` appears to be stored/compared directly (`internal/admin/middleware.go`).
- Current mitigation: none visible in code.
- Recommendations: store hashed API keys (e.g., SHA-256) and compare hashes; add audit logging and per-key rate limiting.

**Cloaking proxy can fetch arbitrary URLs:**
- Risk: `RemoteProxyAction` (`internal/action/proxy.go`) fetches `RedirectURL` which may enable SSRF if `RedirectURL` is attacker-controlled.
- Current mitigation: none obvious.
- Recommendations: allowlist domains, restrict IP ranges (block link-local/private), add URL validation and timeouts (timeout exists).

## Performance Bottlenecks

**Click pipeline stage count and DB/cache hits:**
- Problem: Level 1 pipeline wires many stages (`internal/server/server.go`), likely involving cache/DB reads.
- Measurement: benchmark exists but requires seeded data (`test/benchmark/latency_test.go`).
- Improvement path: profile hot stages, reduce DB round trips, prefetch cached entities, add metrics per stage.

**ClickHouse batching error handling:**
- Problem: `internal/queue/writer.go` flush paths should be monitored for errors/backpressure under load.
- Improvement path: instrument flush errors, add retry/backoff, and expose queue depth metrics.

## Fragile Areas

**Pipeline wiring in `internal/server/server.go`:**
- Why fragile: long ordered list of stage instances; small ordering changes can alter behavior.
- Common failures: regressions in redirect selection/uniqueness/bot checks.
- Safe modification: add tests/benchmarks; change one stage at a time; keep stages pure when possible.
- Test coverage: partial; integration tests exist but not obviously exhaustive for stage ordering.

## Scaling Limits

**In-memory caches / per-process state:**
- Current capacity: depends on instance memory and request rate.
- Limit: unbounded caches (proxy), large channels (`internal/queue/writer.go`) can consume memory under sustained backlog.
- Scaling path: bound caches, add backpressure, run multiple stateless replicas with shared storage.

## Dependencies at Risk

**Go version directive:**
- Risk: `go.mod` specifies `go 1.25.6` which may not match commonly available Go versions.
- Impact: build tooling/CI may fail if it expects released versions only.
- Migration plan: align directive with supported Go release used by deployment.

## Missing Critical Features

**Observability/metrics:**
- Problem: limited visibility into per-stage latency, DB/Valkey/ClickHouse health.
- Current workaround: logs only.
- Blocks: diagnosing performance regressions and operational incidents.
- Implementation complexity: medium (add stage timing + metrics export).

## Test Coverage Gaps

**Pipeline behavior parity tests:**
- What's not tested: end-to-end click outcomes across many combinations (filters, rotator weights, bindings, actions).
- Risk: behavioral regressions when modifying stages.
- Priority: High.
- Difficulty to test: medium-high (needs fixtures + deterministic randomness + seeded DB state).

---

*Concerns audit: 2026-04-03*
*Update as issues are fixed or new ones discovered*
