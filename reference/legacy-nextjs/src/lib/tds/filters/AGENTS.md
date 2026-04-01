<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# filters

## Purpose
Targeting and gating filters for stream selection. This includes geo, device, browser, OS, connection, limits, and uniqueness checks.

## Key Files
| File | Description |
| --- | --- |
| `types.ts` | Common filter contracts. |
| `index.ts` | Filter exports. |
| `country.ts` | Country-based targeting filter. |
| `device-type.ts` | Device-type targeting filter. |
| `uniqueness.ts` | Uniqueness-related filter logic. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Filters should remain deterministic and easy to combine during stream evaluation.
- Coordinate any schema/payload changes with the stored filter payload format.

### Testing Requirements
- Run a click flow that exercises the filter you changed and verify stream choice/output.

### Common Patterns
- Filter-per-file with shared type contracts.

## Dependencies

### Internal
- `src/lib/tds/data/`
- `src/lib/tds/pipeline/stages/choose-stream.ts`

### External
- TypeScript only

<!-- MANUAL: Add directory-specific notes below this line. -->
