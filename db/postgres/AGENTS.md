<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# postgres

## Purpose
PostgreSQL schema and query layer for transactional entities such as campaigns, streams, users, domains, and settings.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `migrations/` | Versioned PostgreSQL DDL migrations (see `migrations/AGENTS.md`) |
| `queries/` | Reserved area for SQL/query definitions and generated query artifacts (currently empty). |

## For AI Agents

### Working In This Directory
- Drive schema evolution through numbered migrations only.
- Keep query definitions in sync with effective schema after latest migrations.
- Favor backward-compatible changes for running environments.

### Testing Requirements
- Validate migrations on a clean DB and check rollback path for new entries.
- Run `go test ./...` after schema/query contract changes.

### Common Patterns
- Pair `.up.sql` and `.down.sql` files by migration number.
- UUID keys and timestamp fields for core entities.

## Dependencies

### Internal
- `db/postgres/migrations/`
- `internal/admin/repository/` and `internal/model/`

### External
- `github.com/jackc/pgx/v5`

<!-- MANUAL: -->
