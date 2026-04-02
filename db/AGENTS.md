<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# db

## Purpose
Database schemas, migrations, and query definitions for PostgreSQL and ClickHouse.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `postgres/` | PostgreSQL migrations and queries (see `postgres/AGENTS.md`) |
| `clickhouse/` | ClickHouse migrations (see `clickhouse/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- PostgreSQL for primary data (clicks, actions, campaigns)
- ClickHouse for analytics/aggregations
- Migrations are numbered and applied sequentially
- Queries are stored as separate Go files

### Migration Pattern
- SQL migration files in `migrations/`
- Migration files numbered (001_, 002_, etc.)
- Run migrations on database startup

## Dependencies
- `github.com/jackc/pgx/v5` - PostgreSQL driver
- `github.com/ClickHouse/clickhouse-go/v2` - ClickHouse driver

<!-- MANUAL: -->