<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# admin

## Purpose
Admin-specific component set: layout scaffolding, navigation structures, dashboard widgets, and shared empty/section states for feature pages.

## Key Files
None currently documented.

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `dashboard/` | Dashboard-specific overview widgets. |
| `layout/` | Admin shell, header, sidebar, and content wrappers. |
| `nav/` | Navigation configuration and nav-rendering helpers. |
| `shared/` | Reusable admin page elements and placeholder content. |

## For AI Agents

### Working In This Directory
- Centralize admin chrome changes here rather than duplicating shell markup in individual pages.
- If a file is currently empty, establish the simplest reusable abstraction you can rather than over-building.

### Testing Requirements
- Verify the target `/admin/*` routes still render correctly across desktop/mobile breakpoints.

### Common Patterns
- Feature pages are expected to compose these shared layout pieces rather than owning navigation directly.
- Several modules are scaffold placeholders awaiting fuller implementation.

## Dependencies

### Internal
- `src/components/ui/`
- `src/lib/admin/`
- `src/types/admin/`

### External
- React
- Lucide React

<!-- MANUAL: Add directory-specific notes below this line. -->
