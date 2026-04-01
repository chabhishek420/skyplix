<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# (admin)

## Purpose
Admin-only route group that isolates the management dashboard from the public root surface while preserving the canonical `/admin/*` URLs.

## Key Files
None currently documented.

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `admin/` | Concrete admin dashboard routes and page shells. (see `admin/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Treat this group as administrative UI only; shared admin chrome belongs in `src/components/admin` and `src/lib/admin`.
- Expect many pages here to act as placeholders while backend functionality is still being filled in.

### Testing Requirements
- Open the affected `/admin/*` page in the dev server and verify the page shell renders.

### Common Patterns
- Route-group folder exists for organization, not URL naming.

## Dependencies

### Internal
- `src/components/admin/` for layout and navigation.
- `src/lib/auth/` for gatekeeping patterns.

### External
- Next.js route groups

<!-- MANUAL: Add directory-specific notes below this line. -->
