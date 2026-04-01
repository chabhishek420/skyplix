<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# admin

## Purpose
Admin metadata layer. This is the intended home for module registry, navigation definition, JS configuration helpers, and other admin-only shared configuration.

## Key Files
| File | Description |
| --- | --- |
| `auth.ts` | Admin-facing auth utilities used by client/admin flows. |
| `js-config.ts` | Client-consumable admin configuration helper. |
| `module-registry.ts` | Reserved module registry scaffold; currently empty. |
| `navigation.ts` | Reserved navigation helper scaffold; currently empty. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Prefer storing admin metadata and registries here rather than embedding them directly in page files.
- Empty files here are scaffolds; fill them deliberately instead of assuming they are dead code.

### Testing Requirements
- Verify the admin shell or page consuming the config still renders.

### Common Patterns
- Configuration-oriented modules rather than domain services.

## Dependencies

### Internal
- `src/components/admin/`
- `src/types/admin/`
- `src/lib/auth/`

### External
- TypeScript only; no special runtime dependency beyond Next.js where imported

<!-- MANUAL: Add directory-specific notes below this line. -->
