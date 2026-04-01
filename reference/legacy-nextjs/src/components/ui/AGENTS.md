<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# ui

## Purpose
shadcn/ui-style primitives and wrappers used across the app. This directory is the main design-system surface for cards, forms, overlays, navigation, tables, and feedback components.

## Key Files
| File | Description |
| --- | --- |
| `button.tsx` | Core button primitive used throughout the UI. |
| `card.tsx` | Standard card container matching the project dark theme. |
| `form.tsx` | Form integration helpers built around React Hook Form. |
| `sidebar.tsx` | Sidebar shell primitive used by admin navigation. |
| `table.tsx` | Table primitives for scrollable/sticky admin data tables. |
| `toast.tsx` | Toast markup and variants. |
| `toaster.tsx` | Global toast presenter. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Stay close to shadcn conventions so future upstream syncs stay easy.
- Prefer extending an existing primitive via variants/classes before adding a one-off component here.

### Testing Requirements
- Render the consuming page and verify keyboard/focus behavior for interactive primitives.
- Check dark-theme contrast because the project styling assumes slate/emerald surfaces.

### Common Patterns
- One component per file with Tailwind class composition.
- Wrappers generally mirror Radix primitives and expose project-specific styling defaults.

## Dependencies

### Internal
- `src/lib/utils.ts`
- `src/hooks/use-toast.ts`

### External
- Radix UI packages
- class-variance-authority
- tailwind-merge

<!-- MANUAL: Add directory-specific notes below this line. -->
