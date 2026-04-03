# CONCERNS

## 1. Startup Error Suppression in Critical Dependencies
- In `internal/server/server.go`, `geo.New(...)` and `queue.NewWriter(...)` errors are ignored (`geoResolver, _ := ...`, `chWriter, _ := ...`).
- Impact: service can boot in degraded mode without clear hard failure, which can silently disable geo enrichment or analytics writes.
- Risk level: high for production observability/data completeness.

## 2. Best-Effort Async Paths Without Logging on Failure
- `internal/pipeline/stage/23_store_raw_clicks.go` launches attribution save in goroutine and suppresses errors.
- Impact: attribution data loss can occur without surfaced diagnostics.
- Risk level: medium-high for conversion attribution accuracy.

## 3. Potential Data Loss Under Queue Backpressure
- `StoreRawClicksStage` performs non-blocking send to click channel and drops writes when channel is full (`default:` case in `internal/pipeline/stage/23_store_raw_clicks.go`).
- Impact: dropped analytics events during burst traffic.
- Risk level: high for reporting fidelity during spikes.

## 4. Security Footgun in Seed Data
- Migration `db/postgres/migrations/004_create_domains_users.up.sql` seeds default admin credentials (`admin` / `admin123`) with note to change in production.
- Impact: dangerous default if migration used outside controlled dev bootstrapping.
- Risk level: high unless deployment process hardens user provisioning.

## 5. API Key Auth Simplicity and Operational Risk
- `internal/admin/middleware.go` checks plain API key from `users` table and returns generic unauthorized on mismatch.
- Impact: minimal defense-in-depth controls shown here (e.g., no explicit rate limiting for admin endpoints, no key rotation metadata in this layer).
- Risk level: medium (depends on upstream protections).

## 6. Convention Drift: Error Handling Policy vs Implementation
- Project guidance in `AGENTS.md` says never suppress errors, but several paths do (for example ignored return values in `internal/server/server.go` and warmup cache set calls in `internal/cache/cache.go`).
- Impact: harder debugging and inconsistent reliability behavior.
- Risk level: medium.

## 7. Test Coverage Concentration
- Unit tests are concentrated in queue/worker domains (`test/unit/queue/*`, `test/unit/worker/*`).
- Complex pipeline stage logic (`internal/pipeline/stage/*.go`) has relatively limited direct unit coverage based on present test tree.
- Impact: regressions in core click decisioning may escape early.
- Risk level: medium-high.

## 8. Partial/Stub Components
- `admin-ui/` currently has no implementation files (only `AGENTS.md` and `CLAUDE.md`).
- `SessionJanitorWorker` is intentionally no-op in `internal/worker/cache_warmup.go`.
- Impact: roadmap assumptions may exceed currently implemented operational tooling.
- Risk level: low-medium (depends on expected completeness).

## 9. Integration Environment Coupling
- Integration tests require external services and seeded data (`docker-compose.yml`, `test/integration/testdata/seed_phase4.sql`).
- Impact: slower feedback loop and higher CI/environment setup complexity.
- Risk level: medium for contributor velocity.

## 10. Root-Level Reference Data Volume
- Large `reference/` subtree includes legacy and external artifacts.
- Impact: repository navigation noise and potential accidental coupling to non-runtime assets.
- Risk level: low, but impacts onboarding ergonomics.
