# Testing Patterns

**Analysis Date:** 2026-04-03

## Test Framework

**Runner:**
- Go `testing` package (stdlib)

**Assertion Library:**
- Built-in `testing.T`/`testing.B` with manual assertions (`t.Errorf`, `t.Fatal`, etc.)

**Run Commands:**
```bash
# Unit tests
go test ./test/unit/...

# Single file
go test -v ./test/unit/queue/writer_test.go

# Single test function (example)
go test -v -run TestParseUUIDVal_ValidUUID ./test/unit/queue/...

# Integration tests (require services)
go test -v -tags integration ./test/integration/... -timeout 30s

# Benchmarks (tagged as integration)
go test -v -tags integration -bench=BenchmarkClickLatency -benchmem ./test/benchmark/
```

## Test File Organization

**Location:**
- Tests are organized under a dedicated `test/` tree rather than colocated with source.

**Naming:**
- Standard Go `*_test.go` naming (examples: `test/unit/queue/writer_test.go`, `test/integration/click_test.go`).

**Structure:**
```
test/
  unit/
    queue/
      writer_test.go
    worker/
      worker_test.go
  integration/
    suite_test.go
    click_test.go
    routing_test.go
  benchmark/
    latency_test.go
```

## Test Structure

**Patterns observed:**
- Direct unit tests for pure functions and small helpers (example: `test/unit/queue/writer_test.go`).
- Integration tests use the `integration` build tag and typically require Postgres/Valkey/ClickHouse available (see `docker-compose.yml`).

## Mocking

- Minimal/none; tests mostly use real objects with `zap.NewNop()` logger (example: `test/unit/queue/writer_test.go`).
- HTTP-level integration/bench tests use `httptest.NewServer` (example: `test/benchmark/latency_test.go`).

## Fixtures and Factories

- Tests commonly build structs inline (example: `model.RawClick` in `test/unit/queue/writer_test.go`).

## Integration Test Setup

- See `test/integration/suite_test.go` for suite-wide setup/teardown.
- Local services are expected from `docker-compose.yml` unless otherwise configured via env vars.

---

*Testing analysis: 2026-04-03*
*Update as test strategy changes*
