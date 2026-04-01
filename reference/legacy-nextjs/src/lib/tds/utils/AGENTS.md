<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# utils

## Purpose
Small TDS-specific utility helpers that do not fit cleanly into actions, services, or pipeline stages.

## Key Files
| File | Description |
| --- | --- |
| `raw-click-serializer.ts` | Helpers for serializing stored raw click payloads. |
| `index.ts` | Utility exports. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Keep this directory for lightweight helpers, not new domain subsystems.

### Testing Requirements
- Exercise the consuming storage/reporting path after utility changes.

### Common Patterns
- Small focused helpers and barrel exports.

## Dependencies

### Internal
- `src/lib/tds/pipeline/`
- `src/lib/tds/services/`

### External
- TypeScript only

<!-- MANUAL: Add directory-specific notes below this line. -->
