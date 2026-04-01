<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# macros

## Purpose
Macro-expansion subsystem for URLs and response content. It parses `{macro}` / `$macro` tokens, resolves them from registry implementations or request params, and applies encoding rules.

## Key Files
| File | Description |
| --- | --- |
| `processor.ts` | Core parser and replacement engine for macro expansion. |
| `registry.ts` | Macro registry that maps names to implementations. |
| `types.ts` | Macro context and parser item types. |
| `index.ts` | Exports for macro helpers. |

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `predefined/` | Concrete macro implementations grouped by subject area. (see `predefined/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Keep macro naming and raw/encoded behavior backward-compatible because URLs and templates depend on it.
- If you add a macro, wire it into the registry and document the expected context keys.

### Testing Requirements
- Run the exact action or URL-building path that emits the macro output.
- Verify both encoded and raw modes when the macro supports them.

### Common Patterns
- Registry-driven macro implementations with a central parser.

## Dependencies

### Internal
- `src/lib/tds/contexts/`
- `src/lib/tds/actions/`
- `src/lib/tds/pipeline/`

### External
- TypeScript only

<!-- MANUAL: Add directory-specific notes below this line. -->
