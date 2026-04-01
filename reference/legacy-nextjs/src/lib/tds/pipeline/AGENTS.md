<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# pipeline

## Purpose
Staged click-processing engine that mirrors Keitaro-style first-level and second-level flow execution. It owns payload state, runner adapters, and the ordered stage lists.

## Key Files
| File | Description |
| --- | --- |
| `pipeline.ts` | Main staged pipeline orchestration with first-level and second-level stage order. |
| `runner.ts` | Adapter between Next.js route handlers and the pipeline engine. |
| `payload.ts` | Mutable pipeline payload/state carrier. |
| `types.ts` | Stage contracts and result types. |

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `stages/` | Concrete pipeline stage implementations. (see `stages/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Stage order matters. Reordering stages can change click semantics, persistence, and recursion behavior.
- When introducing a new stage, decide whether it belongs in first-level flow, second-level flow, or both.

### Testing Requirements
- Exercise the relevant route (`/api/click`, `/api/click/json`, `/api/lp/offer`) and inspect the final response.
- Review logs for abort conditions, recursion handling, and payload mutations.

### Common Patterns
- Pipeline + payload + stage classes.
- Runner translates Next.js requests into payload objects and payloads back into `NextResponse`s.

## Dependencies

### Internal
- `src/lib/tds/actions/`
- `src/lib/tds/filters/`
- `src/lib/tds/services/`

### External
- Next.js server runtime

<!-- MANUAL: Add directory-specific notes below this line. -->
