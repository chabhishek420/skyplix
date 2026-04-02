<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# migrations

## Purpose
Sequential PostgreSQL migrations that define and evolve transactional schema for campaigns, routing entities, users, and settings.

## Key Files
| File | Description |
|------|-------------|
| `001_create_campaigns.up.sql` | Bootstraps extensions, campaign enum, and base `campaigns` table with indexes. |
| `002_create_streams.up.sql` | Adds stream and routing structures used by campaign selection logic. |
| `005_add_stream_limits_and_api_keys.up.sql` | Adds stream limit columns and API key support for admin auth flows. |
| `006_create_settings.up.sql` | Introduces key/value settings table and default system/tracker values. |

## For AI Agents

### Working In This Directory
- Add new migrations only; do not rewrite historical migrations that may already be applied.
- Keep paired `.up.sql` and `.down.sql` files aligned by migration number and intent.
- Use reversible DDL in `down` migrations whenever safe and practical.

### Testing Requirements
- Run migrations on a clean PostgreSQL instance and verify successful up/down for new entries.
- Execute `go test ./...` after schema changes to catch model/query mismatches.

### Common Patterns
- Numeric prefix ordering (`001_`, `002_`, ...).
- UUID-based primary keys and timestamp audit columns.
- Schema fixes are captured as additive migrations (no destructive history rewrites).

## Dependencies

### Internal
- `internal/admin/repository/` and related model packages depend on table/column stability.
- `db/postgres/queries/` should reflect effective schema after migrations.

### External
- PostgreSQL 16+
- `github.com/jackc/pgx/v5`

<!-- MANUAL: -->
