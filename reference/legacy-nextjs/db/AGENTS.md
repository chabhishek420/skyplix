<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# db

## Purpose
Database runtime directory. The current repo stores the SQLite database file here.

## Key Files
| File | Description |
| --- | --- |
| `custom.db` | SQLite database used by local development/testing. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Treat checked-in database files as environment artifacts unless the task explicitly calls for updating them.

### Testing Requirements
- If the DB file changes intentionally, re-run the affected flows against the local app.

### Common Patterns
- Runtime data rather than source code.

## Dependencies

### Internal
- `prisma/`
- `src/lib/db.ts`

### External
- SQLite

<!-- MANUAL: Add directory-specific notes below this line. -->
