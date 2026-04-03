# Testing Patterns

**Analysis Date:** 2026-04-03

## Test Framework

**Runner:**
- Standard `go test` runner.
- Build tags used to isolate integration tests (e.g., `//go:build integration`).

**Assertion Library:**
- Standard Go `testing` package (e.g., `t.Errorf`, `t.Fatalf`).
- No external assertion libraries like `testify` are present.

**Run Commands:**
```bash
go test ./test/unit/...              # Run all unit tests
go test -v -tags integration ./test/integration/... -timeout 30s # Run integration tests
go test -bench . ./test/benchmark/    # Run performance benchmarks
```

## Test File Organization

**Location:**
- Centralized `test/` directory at the project root for multi-component tests.
- Package-level tests co-located with implementation (e.g., `internal/botdb/store_test.go`).

**Naming:**
- Files: `*_test.go`.
- Functions: `Test[Name]` (e.g., `TestContains_SingleIP`, `TestEndToEndClick`).

**Structure:**
```
/Users/roshansharma/Desktop/zai-yt-keitaro/test/
├── unit/           # Isolated package tests
├── integration/    # E2E and multi-component tests
└── benchmark/      # Performance tests
```

## Test Structure

**Suite Organization:**
```go
func TestEndToEndClick(t *testing.T) {
    // 1. Setup (Connections, Environment)
    // 2. Pre-test state capture
    // 3. Execution (HTTP request)
    // 4. Assertions (Status code, database records)
    // 5. Teardown (Deferred Close)
}
```

**Patterns:**
- Setup: In-memory or real database connections depending on the test type.
- Teardown: `defer` for closing connections and cleaning up.
- Assertion: Simple `if got != want` style using standard Go tools.

## Mocking

**Framework:**
- Manual implementation of interfaces for mocking (no mock generators like `mockery`).
- Dependency injection via interfaces (e.g., `DB` interface in `internal/admin/repository/db.go`).

**Patterns:**
- No complex mocking observed. Integration tests prefer using real database instances.

**What to Mock:**
- External services (GeoIP lookup, ClickHouse writer, Database).

**What NOT to Mock:**
- Core business logic (Pipeline stages, Filters, Rotators).

## Fixtures and Factories

**Test Data:**
```go
input := "1.1.1.1\n2.2.2.0/24\n3.3.3.1-3.3.3.10"
// Or SQL seeds in testdata/
```

**Location:**
- SQL fixture and seed artifacts in `/Users/roshansharma/Desktop/zai-yt-keitaro/test/integration/testdata/`.

## Coverage

**Requirements:**
- None explicitly enforced, but coverage is noted in CLAUDE.md files.

**View Coverage:**
```bash
go test -cover ./...
```

## Test Types

**Unit Tests:**
- Fast, isolated tests for package logic (e.g., `internal/botdb/store_test.go`).
- No external dependencies used.

**Integration Tests:**
- End-to-end tests requiring real infrastructure (PostgreSQL, ClickHouse, Valkey).
- Located in `/Users/roshansharma/Desktop/zai-yt-keitaro/test/integration/`.

**E2E Tests:**
- Same as integration tests, exercising the full click path and admin API.

## Common Patterns

**Async Testing:**
- Use of `time.Sleep` to wait for background operations (e.g., ClickHouse batch flushes).

**Error Testing:**
- Asserting `err == nil` or specific error values for expected failures.

---

*Testing analysis: 2026-04-03*
