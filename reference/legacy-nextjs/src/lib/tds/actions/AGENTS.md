<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# actions

## Purpose
Action abstraction layer for the TDS engine. Action classes describe how a selected stream/offer responds: redirects, content bodies, frames, local files, remote fetches, and other Keitaro-style behaviors.

## Key Files
| File | Description |
| --- | --- |
| `base.ts` | Abstract action contract shared by every action class. |
| `repository.ts` | Singleton registry that maps action keys to classes and metadata. |
| `types.ts` | Shared action result/type definitions. |
| `index.ts` | Action exports. |

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `predefined/` | Concrete built-in action implementations. (see `predefined/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Register new actions in `repository.ts` or they will never be discoverable at runtime.
- Keep action classes focused on response generation; selection logic belongs in pipeline stages.

### Testing Requirements
- Exercise a route that chooses the modified action type and inspect the resulting redirect/body/headers.

### Common Patterns
- Registry plus concrete class implementations.

## Dependencies

### Internal
- `src/lib/tds/pipeline/`
- `src/lib/tds/macros/`

### External
- Next.js response semantics when actions are materialized by the runner

<!-- MANUAL: Add directory-specific notes below this line. -->
