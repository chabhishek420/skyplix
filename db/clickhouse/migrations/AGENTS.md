<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# migrations

## Purpose
Versioned ClickHouse schema migrations for analytics storage, including click logs and conversion attribution tables.

## Key Files
| File | Description |
|------|-------------|
| `001_create_clicks.sql` | Creates the primary `clicks` MergeTree table with campaign and device dimensions. |
| `002_create_conversions.sql` | Creates the `conversions` MergeTree table linked by `click_token`. |

## For AI Agents

### Working In This Directory
- Keep migration numbers monotonic (`NNN_*.sql`) and append new files instead of editing older migrations.
- Prefer ClickHouse-native types (`DateTime64`, `FixedString`, `Decimal`, `UUID`) to avoid cast-heavy queries.
- Preserve ordering/partition choices unless there is a measured analytics regression to fix.

### Testing Requirements
- Apply migrations in a fresh ClickHouse instance and verify both tables are created.
- Run integration tests that write click/conversion records after schema changes.

### Common Patterns
- `MergeTree` engine with explicit `ORDER BY` for common query paths.
- UTC timestamps (`DateTime64(3, 'UTC')`) for deterministic aggregation windows.

## Dependencies

### Internal
- `internal/queue/` writes click/conversion payloads that must match this schema.
- `test/integration/` validates ingestion and query behavior against these tables.

### External
- ClickHouse server 24.x+
- `github.com/ClickHouse/clickhouse-go/v2`

<!-- MANUAL: -->
