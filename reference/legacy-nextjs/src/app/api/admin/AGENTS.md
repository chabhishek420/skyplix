<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# admin

## Purpose
Admin CRUD/reporting API namespace. Each child folder contains a `route.ts` module that handles one resource area or auth/session action.

## Key Files
None currently documented.

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `affiliate-networks/` | Affiliate network CRUD route. |
| `audit-logs/` | Audit log listing route. |
| `bot-rules/` | Bot-rule CRUD route. |
| `campaigns/` | Campaign CRUD route. |
| `clicks/` | Click-reporting route. |
| `conversions/` | Conversion-reporting route. |
| `domains/` | Domain CRUD route. |
| `landings/` | Landing CRUD route. |
| `login/` | Admin login/session bootstrap route. |
| `logout/` | Admin logout route. |
| `offers/` | Offer CRUD route. |
| `publishers/` | Publisher CRUD route. |
| `reports/` | Aggregate reporting route. |
| `settings/` | System settings read/write route. |
| `stats/` | Dashboard statistics route. |
| `streams/` | Stream CRUD route. |
| `traffic-sources/` | Traffic source CRUD route. |
| `users/` | Admin user CRUD route. |

## For AI Agents

### Working In This Directory
- Apply auth checks consistently. Existing routes typically gate requests with `checkAuth(request)` from `src/lib/auth`.
- Resource routes favor straightforward Prisma calls and return JSON payloads without an extra service layer.

### Testing Requirements
- Exercise the exact HTTP verb you changed with and without auth headers/cookies.
- If you alter payload shapes, confirm the corresponding admin page still parses the response.

### Common Patterns
- One resource per folder, one `route.ts` per resource.
- Current handlers are pragmatic CRUD endpoints, not a fully abstracted REST layer.

## Dependencies

### Internal
- `src/lib/auth/`
- `src/lib/db.ts`
- `src/types/admin/`

### External
- Prisma Client
- Next.js route handlers

<!-- MANUAL: Add directory-specific notes below this line. -->
