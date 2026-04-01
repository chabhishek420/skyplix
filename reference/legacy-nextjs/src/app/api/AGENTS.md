<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# api

## Purpose
HTTP route-handler surface for traffic ingestion and admin APIs. Most handlers are intentionally thin wrappers over `src/lib/tds`, Prisma, or auth helpers.

## Key Files
| File | Description |
| --- | --- |
| `route.ts` | Base API route placeholder/entrypoint. |

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `admin/` | Authenticated CRUD and reporting endpoints for the admin UI. (see `admin/AGENTS.md`) |
| `click/` | Primary traffic entrypoint and JSON click helper. |
| `lp/` | Landing-page follow-up routing endpoints. |
| `postback/` | Conversion postback processing endpoint. |
| `safe/` | Safe-page delivery endpoint for cloaked/bot traffic. |

## For AI Agents

### Working In This Directory
- Keep route handlers small and delegate parsing, validation, and business logic into `src/lib/`.
- Preserve compatibility with Keitaro-style parameter names such as `campaign_id`, `pub_id`, and `sub1-sub15`.

### Testing Requirements
- Manually hit the modified endpoint with representative query/body data.
- Check `dev.log` after route edits because runtime errors surface there quickly in this environment.

### Common Patterns
- Next.js `route.ts` modules export HTTP verb functions directly.
- Traffic endpoints call the pipeline runner rather than duplicating click logic.

## Dependencies

### Internal
- `src/lib/auth/`
- `src/lib/db.ts`
- `src/lib/tds/`

### External
- NextRequest / NextResponse

<!-- MANUAL: Add directory-specific notes below this line. -->
