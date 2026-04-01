<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# tds

## Purpose
Core traffic distribution system implementation. This subtree contains click processing, bot detection, macro expansion, action execution, routing filters, and the staged pipeline that mirrors Keitaro concepts.

## Key Files
| File | Description |
| --- | --- |
| `click-processor.ts` | Standalone click-processing engine with campaign/publisher lookup and cloaking decisions. |
| `bot-detection.ts` | Bot detection heuristics and cloaking helpers. |
| `click-id.ts` | Click ID generation and validation helpers. |
| `rotator.ts` | Stream/offer/landing rotation support. |
| `index.ts` | Top-level re-export surface for TDS modules. |
| `macros.ts` | Compatibility entrypoint for macro-related helpers. |

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `actions/` | Action types and registries that turn pipeline choices into responses. (see `actions/AGENTS.md`) |
| `contexts/` | Context objects shared during TDS execution. (see `contexts/AGENTS.md`) |
| `data/` | Static lookup data such as countries, browsers, operators, and bot signatures. (see `data/AGENTS.md`) |
| `filters/` | Stream filter implementations for targeting and uniqueness. (see `filters/AGENTS.md`) |
| `macros/` | Macro registry, processor, and predefined macro handlers. (see `macros/AGENTS.md`) |
| `pipeline/` | Staged click-processing pipeline and stage runner. (see `pipeline/AGENTS.md`) |
| `services/` | Support services such as GeoIP, cookies, proxy, and entity binding. (see `services/AGENTS.md`) |
| `utils/` | Small TDS-specific helper utilities. (see `utils/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Preserve Keitaro terminology and flow ordering when changing pipeline behavior.
- Avoid duplicating logic between the legacy `click-processor.ts` path and the newer staged pipeline without a deliberate migration plan.

### Testing Requirements
- Hit `/api/click`, `/api/click/json`, `/api/postback`, or `/api/lp/offer` with representative traffic parameters.
- Check `dev.log` for stage failures because the pipeline logs heavily at runtime.

### Common Patterns
- Keitaro-inspired domain model and vocabulary.
- Thin route handlers delegate to a staged pipeline or focused helpers inside this subtree.

## Dependencies

### Internal
- `src/lib/db.ts`
- `prisma/schema.prisma`
- `reference/` for parity research

### External
- Prisma Client
- Next.js server runtime
- optional MaxMind runtime module

<!-- MANUAL: Add directory-specific notes below this line. -->
