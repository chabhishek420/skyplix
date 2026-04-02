<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# clickhouse

## Purpose
ClickHouse schema layer for analytics events and aggregate-friendly storage used by the tracking pipeline.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `migrations/` | SQL schema migrations for ClickHouse tables (see `migrations/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Keep analytics schema changes additive and versioned through migrations.
- Validate type compatibility with queue writer payloads before shipping schema edits.
- Preserve engine and sort-key decisions unless profiling confirms improvement.

### Testing Requirements
- Apply migration set to a clean ClickHouse instance.
- Run integration tests touching click/conversion ingestion paths.

### Common Patterns
- `MergeTree` tables with explicit `ORDER BY`.
- UTC timestamps and fixed-width fields for high-volume query efficiency.

## Dependencies

### Internal
- `db/clickhouse/migrations/`
- `internal/queue/`

### External
- `github.com/ClickHouse/clickhouse-go/v2`

<!-- MANUAL: -->
