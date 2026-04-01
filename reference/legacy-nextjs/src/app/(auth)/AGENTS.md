<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# (auth)

## Purpose
Authentication route group for admin login flows and any future auth-only screens that should not inherit the admin dashboard shell.

## Key Files
None currently documented.

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `login/` | Admin login page route. |

## For AI Agents

### Working In This Directory
- Keep auth UX separate from admin shell concerns.
- Any credential/session changes must stay aligned with `src/lib/auth` helpers and the login/logout API routes.

### Testing Requirements
- Exercise the login page and corresponding API round-trip when auth behavior changes.

### Common Patterns
- Route group used to isolate auth presentation from admin content.

## Dependencies

### Internal
- `src/lib/auth/`
- `src/app/api/admin/login`
- `src/app/api/admin/logout`

### External
- Next.js route groups

<!-- MANUAL: Add directory-specific notes below this line. -->
