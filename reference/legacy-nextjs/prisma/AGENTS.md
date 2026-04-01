<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# prisma

## Purpose
Prisma schema and seed assets for the SQLite-backed TDS data model.

## Key Files
| File | Description |
| --- | --- |
| `schema.prisma` | Canonical data model for campaigns, streams, clicks, conversions, auth, and settings. |
| `seed.ts` | Database seeding entrypoint. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Schema changes must stay aligned with code in `src/lib/db.ts` and any Prisma queries in API routes.
- Prefer additive migrations/changes over breaking renames unless you are updating all consumers.

### Testing Requirements
- Run `bun run db:generate` and `bun run db:push` when schema changes are intentional.
- Smoke-test affected API routes after changing model fields or relations.

### Common Patterns
- Single Prisma schema for a local SQLite database.

## Dependencies

### Internal
- `db/`
- `src/lib/db.ts`
- `src/app/api/`

### External
- Prisma ORM
- SQLite

<!-- MANUAL: Add directory-specific notes below this line. -->
