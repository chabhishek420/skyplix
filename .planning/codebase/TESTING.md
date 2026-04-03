# TESTING

## Test Types Present
- Unit tests in `test/unit/`.
- Integration tests in `test/integration/` guarded by `//go:build integration`.
- Benchmark tests in `test/benchmark/` also behind integration build tags.

## Test Commands (Documented)
- Unit suite: `go test ./test/unit/...`.
- Full suite: `go test ./...` (requires external services for integration).
- Integration: `go test -v -tags integration ./test/integration/... -timeout 30s`.
- Benchmark: `go test -v -tags integration -bench=BenchmarkClickLatency -benchmem ./test/benchmark/`.
- Coverage: `go test -cover ./...`.

## Current Unit Coverage Focus
- Queue writer helpers and data conversion:
- `test/unit/queue/writer_test.go`.
- Worker manager lifecycle behavior:
- `test/unit/worker/worker_test.go`.

## Current Integration Coverage Focus
- End-to-end click flow and ClickHouse persistence:
- `test/integration/click_test.go`.
- Admin API and routing behavior:
- `test/integration/admin_test.go`, `test/integration/routing_test.go`.
- Cloaking/bot behavior:
- `test/integration/cloaking_test.go`.

## Test Infrastructure Dependencies
- Integration tests depend on running Postgres, Valkey, and ClickHouse.
- Docker compose stack in `docker-compose.yml` provides local dependencies.
- Seed data fixtures under `test/integration/testdata/seed_phase4.sql`.

## Observed Test Patterns
- Predominantly table/assert-style tests with direct `t.Errorf` checks.
- Worker tests use mock worker structs and context cancellation orchestration.
- Integration click tests use real HTTP calls and direct ClickHouse queries for verification.

## Gaps / Risk Areas in Current Coverage
- No dedicated unit tests found for most pipeline stages in `internal/pipeline/stage/*.go`.
- Limited direct tests for admin repository SQL edge cases and transaction failures.
- Few tests around failure modes (degraded startup, partial dependency outages, queue saturation).
- Benchmark exists but no enforced latency threshold assertions in CI-visible rules from repository files.

## Suggested Testing Priorities (for future updates)
- Add stage-level unit tests for decision-heavy pipeline stages.
- Add failover/error-path integration tests (Valkey unavailable, ClickHouse unavailable).
- Add security-focused tests around admin auth and API-key handling edge cases.
