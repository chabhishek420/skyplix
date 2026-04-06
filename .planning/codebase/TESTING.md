# Testing

**Analysis Date:** 2026-04-06

## Test Framework and Execution

- Backend tests use Go's standard `testing` package.
- Commands documented for unit, integration, and coverage runs in project docs (`AGENTS.md` root instructions).
- Integration suite requires external services (Postgres, ClickHouse, Valkey), usually via `docker-compose.yml`.

## Test Structure

- Unit tests:
  - `test/unit/queue/writer_test.go`
  - `test/unit/worker/worker_test.go`
  - `test/unit/macro/postback_test.go`
- Integration tests (build tag `integration`):
  - `test/integration/click_test.go`
  - `test/integration/routing_test.go`
  - `test/integration/cloaking_test.go`
  - `test/integration/admin_test.go`
- Package-local unit tests also exist (for example `internal/auth/service_test.go`, `internal/attribution/service_test.go`).

## Patterns in Current Tests

- Table-driven style appears in several tests.
- Mock and fake strategy is mixed:
  - Redis fake via `github.com/alicebob/miniredis/v2`
  - Postgres fake via `github.com/pashagolub/pgxmock/v3`
  - Real integration dependencies in `test/integration/*`
- Functional verification is emphasized for data transformation and service behavior.

## What Is Covered Well

- Queue serialization/parsing helpers (`test/unit/queue/writer_test.go`).
- Worker/service behaviors with mocked dependencies (`test/unit/worker/*`, `internal/*_test.go`).
- End-to-end click and routing paths in integration tests.

## Testing Gaps and Risks

- No top-level CI workflow detected in repository root, so automated enforcement is unclear.
- Admin UI (`admin-ui/src/**`) has no visible automated test suite in this repo.
- Some critical runtime bootstrap and graceful shutdown paths are lightly covered by direct tests.
- Benchmark/load scripts exist but are separate from assertions-based tests (`test/load/click_pipeline.js`, `test/bench/*`).

## Recommended Near-Term Improvements

- Add deterministic tests for full L1 and L2 pipeline stage sequencing with controlled fixtures.
- Add API contract tests for high-value admin routes (auth, campaigns, settings, reports).
- Add frontend smoke tests for `admin-ui` route rendering and API client behavior.
- Add a single automated test gate command to run in CI (at minimum `go test ./test/unit/...`).
