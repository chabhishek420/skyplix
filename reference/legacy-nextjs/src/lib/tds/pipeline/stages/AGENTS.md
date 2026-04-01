<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# stages

## Purpose
Concrete ordered pipeline stages covering request normalization, campaign lookup, targeting, selection, token/cookie handling, action execution, and click persistence.

## Key Files
| File | Description |
| --- | --- |
| `build-raw-click.ts` | Initial raw click construction stage. |
| `check-bot.ts` | Bot detection/cloaking stage. |
| `find-campaign.ts` | Campaign lookup stage. |
| `choose-stream.ts` | Stream selection stage. |
| `choose-offer.ts` | Offer selection stage. |
| `execute-action.ts` | Final action execution stage. |
| `store-raw-clicks.ts` | Persistence stage for raw click storage. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Each stage should own one coherent mutation/decision step and communicate through the shared payload.
- Prefer adding logging at the payload/stage boundary when diagnosing flow issues.

### Testing Requirements
- Exercise a traffic request that reaches the modified stage.
- If the stage mutates payload fields used later, verify downstream stages still behave correctly.

### Common Patterns
- One stage per file implementing a shared interface.

## Dependencies

### Internal
- `src/lib/tds/pipeline/payload.ts`
- `src/lib/tds/services/`
- `src/lib/tds/actions/`

### External
- TypeScript only

<!-- MANUAL: Add directory-specific notes below this line. -->
