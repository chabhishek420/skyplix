<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# contexts

## Purpose
Shared execution contexts used while evaluating landing and gateway flows inside the TDS engine.

## Key Files
| File | Description |
| --- | --- |
| `gateway-context.ts` | Gateway/request context model. |
| `landing-context.ts` | Landing-click context model. |
| `index.ts` | Context exports. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Keep context objects lean and serializable enough for debugging/logging.

### Testing Requirements
- Exercise the consumer path that constructs the modified context.

### Common Patterns
- Context data classes/interfaces consumed by pipeline and macros layers.

## Dependencies

### Internal
- `src/lib/tds/pipeline/`
- `src/lib/tds/macros/`

### External
- TypeScript only

<!-- MANUAL: Add directory-specific notes below this line. -->
