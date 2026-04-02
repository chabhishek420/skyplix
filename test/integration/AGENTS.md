<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# integration

## Purpose
End-to-end and multi-component tests that exercise real service integration paths across PostgreSQL, ClickHouse, and Valkey.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `testdata/` | SQL fixtures and integration seed assets (see `testdata/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Keep test scenarios close to real traffic/routing behavior.
- Ensure fixture assumptions match latest schema and cache behavior.
- Avoid coupling tests to unstable implementation details.

### Testing Requirements
- Run `go test ./test/integration/...` with required services running.
- Use `docker compose up` dependencies before running full integration suites.

### Common Patterns
- Fixture-backed setup + endpoint/flow assertions.
- Deterministic IDs/aliases in seed data to reduce flaky comparisons.

## Dependencies

### Internal
- `test/integration/testdata/`
- `db/postgres/migrations/` and `db/clickhouse/migrations/`

### External
- PostgreSQL, ClickHouse, Valkey/Redis runtime services

<!-- MANUAL: -->
