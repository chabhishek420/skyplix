<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# app

## Purpose
Top-level App Router directory. It hosts the global layout and styles, the public entry page, the admin route group, the auth route group, and the API surface.

## Key Files
| File | Description |
| --- | --- |
| `layout.tsx` | Root layout wrapper for the entire application. |
| `globals.css` | Global Tailwind/theme styling shared by every route. |
| `page.tsx` | Primary public page entry; currently an empty placeholder file. |

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `(admin)/` | Admin route group for the in-app management UI. (see `(admin)/AGENTS.md`) |
| `(auth)/` | Authentication route group for login flows. (see `(auth)/AGENTS.md`) |
| `api/` | Next.js route handlers for click tracking and admin CRUD. (see `api/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Keep user-facing UI on the visible `/` route unless the work specifically targets the hidden admin routes.
- Do not move backend logic into page files; route handlers should delegate to `src/lib/`.

### Testing Requirements
- Smoke-test route rendering in `bun run dev` for layout or CSS changes.
- Call the affected `app/api` endpoint after any route-handler edit.

### Common Patterns
- Route groups are used to separate admin/auth concerns without changing URLs.
- Layouts and pages stay thin; shared behavior should move into `src/components` or `src/lib`.

## Dependencies

### Internal
- `src/components/` for page composition.
- `src/lib/` for route behavior and shared utilities.

### External
- Next.js App Router
- Tailwind CSS 4

<!-- MANUAL: Add directory-specific notes below this line. -->
