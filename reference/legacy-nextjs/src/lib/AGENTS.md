<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# lib

## Purpose
Shared runtime library code for Prisma access, admin metadata, authentication helpers, and the core TDS engine.

## Key Files
| File | Description |
| --- | --- |
| `db.ts` | Singleton Prisma Client wiring for the Next.js runtime. |
| `utils.ts` | Small shared utility helpers consumed by UI code. |

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `admin/` | Admin-side registries and metadata helpers. (see `admin/AGENTS.md`) |
| `auth/` | Authentication/session helpers for admin APIs. (see `auth/AGENTS.md`) |
| `tds/` | Traffic distribution system engine and support modules. (see `tds/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Keep this directory framework-light where possible so route handlers and UI can share logic cleanly.
- Database/model changes here should stay aligned with `prisma/schema.prisma`.

### Testing Requirements
- Re-hit any route handler that consumes the modified helper.
- Run lint after signature or import-path changes.

### Common Patterns
- Thin wrappers for external systems, with domain-heavy logic concentrated under `tds/`.

## Dependencies

### Internal
- `prisma/`
- `src/types/`
- `src/app/api/`

### External
- Prisma Client
- Next.js server runtime

<!-- MANUAL: Add directory-specific notes below this line. -->
