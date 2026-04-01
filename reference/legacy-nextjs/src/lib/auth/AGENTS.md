<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# auth

## Purpose
Authentication and session helpers for admin routes. The current implementation centers on API-key validation plus cookie-based convenience for browser access.

## Key Files
| File | Description |
| --- | --- |
| `admin-auth.ts` | Primary admin auth implementation and middleware helpers. |
| `index.ts` | Re-export surface for auth helpers. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Preserve the supported auth vectors unless you update every admin consumer: Bearer, `X-API-Key`, query fallback, and cookie session.
- Be careful with development-only shortcuts such as localhost auth bypasses.

### Testing Requirements
- Exercise login, logout, and at least one protected route after auth changes.
- Validate both authenticated and unauthenticated responses.

### Common Patterns
- Pure helper functions wrapping Next.js request/response types.
- Route handlers call `checkAuth` or higher-order wrappers rather than duplicating validation.

## Dependencies

### Internal
- `src/app/api/admin/login/`
- `src/app/api/admin/logout/`

### External
- NextRequest / NextResponse

<!-- MANUAL: Add directory-specific notes below this line. -->
