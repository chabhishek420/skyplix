<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# components

## Purpose
Reusable React component library for both the admin interface and the shared shadcn/ui primitive layer.

## Key Files
None currently documented.

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `admin/` | Admin dashboard shells, navigation, and shared admin view building blocks. (see `admin/AGENTS.md`) |
| `ui/` | shadcn/ui-derived primitive wrappers and composite controls. (see `ui/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Prefer composing existing `ui/` primitives before introducing custom component patterns.
- Keep admin-specific presentation inside `admin/` so the shared primitives remain generic.

### Testing Requirements
- Open the affected page or story-like usage path in `bun run dev` and verify class names/rendering.

### Common Patterns
- shadcn-style component modules with colocated exports per primitive.

## Dependencies

### Internal
- `src/lib/utils.ts` for class merging.
- `src/hooks/` for responsive helpers/toasts.

### External
- Radix UI primitives
- Lucide React
- Tailwind CSS

<!-- MANUAL: Add directory-specific notes below this line. -->
