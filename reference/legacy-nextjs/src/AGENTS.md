<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# src

## Purpose
Application source tree for the Next.js App Router app: pages, route handlers, reusable UI primitives, domain libraries, hooks, and shared TypeScript definitions.

## Key Files
None currently documented.

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `app/` | App Router pages, layouts, and HTTP route handlers. (see `app/AGENTS.md`) |
| `components/` | Reusable UI and admin-facing React components. (see `components/AGENTS.md`) |
| `hooks/` | Client-side React hooks shared by the UI. (see `hooks/AGENTS.md`) |
| `lib/` | Server and shared utilities, auth helpers, Prisma access, and TDS logic. (see `lib/AGENTS.md`) |
| `types/` | TypeScript shapes for admin config and navigation models. (see `types/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Respect the App Router split: UI lives under `app/` and backend logic stays in API routes or shared `lib/` modules.
- Use the established path aliases (`@/lib`, `@/components`) instead of deep relative imports.
- When a change crosses routing, UI, and data logic, update the relevant child AGENTS files as the entry point shifts.

### Testing Requirements
- Run `bun run lint` after multi-file changes in `src/`.
- For traffic-handling changes, exercise the impacted `/api/*` route manually or through existing verification notes.

### Common Patterns
- App Router file-system routing with route groups such as `(admin)` and `(auth)`.
- Shared domain code is concentrated in `src/lib/tds` and consumed by thin route handlers.

## Dependencies

### Internal
- `prisma/schema.prisma` for persisted shape changes.
- `docs/` and `planning/` for architectural context.

### External
- Next.js App Router
- React 19
- TypeScript strict mode

<!-- MANUAL: Add directory-specific notes below this line. -->
