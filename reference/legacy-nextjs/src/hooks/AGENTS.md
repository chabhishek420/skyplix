<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# hooks

## Purpose
Shared React hooks used by the UI layer. Current hooks handle responsive checks and toast state management.

## Key Files
| File | Description |
| --- | --- |
| `use-mobile.ts` | Responsive/mobile detection helper. |
| `use-toast.ts` | Toast state hook used by the notification UI. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Keep hooks framework-focused and avoid leaking route-specific business logic into this directory.

### Testing Requirements
- Exercise the UI that consumes the hook to verify client-only assumptions remain valid.

### Common Patterns
- Hooks are small, focused helpers rather than a large state-management layer.

## Dependencies

### Internal
- `src/components/ui/`

### External
- React hooks

<!-- MANUAL: Add directory-specific notes below this line. -->
