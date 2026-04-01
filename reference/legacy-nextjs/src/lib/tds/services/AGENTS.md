<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# services

## Purpose
Support services for the TDS runtime: GeoIP lookup, cookie handling, entity binding, proxy awareness, LP token creation, and related cross-cutting concerns.

## Key Files
| File | Description |
| --- | --- |
| `geo-db-service.ts` | GeoIP resolution service with MaxMind and development fallback logic. |
| `cookies-service.ts` | Cookie read/write helpers. |
| `entity-binding-service.ts` | Visitor/entity binding helpers. |
| `lp-token-service.ts` | Landing-page token helpers. |
| `proxy-service.ts` | Proxy-related request helpers. |
| `index.ts` | Service exports. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Be careful with optional infrastructure dependencies such as MaxMind databases; development fallbacks are intentional.
- Service changes often affect several stages at once, so verify all consumers.

### Testing Requirements
- Run the flow that consumes the service and verify both happy-path and fallback behavior where relevant.

### Common Patterns
- Function-based service modules with small shared state/caches when necessary.

## Dependencies

### Internal
- `src/lib/tds/pipeline/`
- `src/lib/tds/data/`
- `src/lib/db.ts`

### External
- optional `maxmind` runtime dependency
- Fetch API
- Node `fs`/`path` where needed

<!-- MANUAL: Add directory-specific notes below this line. -->
