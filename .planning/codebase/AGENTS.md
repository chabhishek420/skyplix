<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# codebase

## Purpose
Structured analysis snapshots describing system architecture, stack decisions, conventions, risks, integrations, and milestone roadmap status.

## Key Files
| File | Description |
|------|-------------|
| `ARCHITECTURE.md` | System architecture and module interaction narrative. |
| `STACK.md` | Technology stack decisions and dependency rationale. |
| `STRUCTURE.md` | Directory/module layout and ownership mapping. |
| `CONVENTIONS.md` | Coding and implementation conventions observed in the codebase. |
| `TESTING.md` | Test strategy, current coverage, and validation guidance. |
| `INTEGRATIONS.md` | External services and integration touchpoints. |
| `CONCERNS.md` | Known risks, debt, and caution areas. |
| `ROADMAP.md` | Phase-by-phase implementation roadmap and status. |

## For AI Agents

### Working In This Directory
- Keep analysis factual and synchronized with actual repository state.
- Update only the sections impacted by concrete codebase changes.
- Use this directory as context input before planning large refactors or new phases.

### Testing Requirements
- No runtime tests required for documentation-only edits.
- Verify file references and stated commands remain valid.

### Common Patterns
- One concern area per document (`STACK`, `TESTING`, etc.) for quick retrieval.
- Explicit status markers for phases and deliverables.

## Dependencies

### Internal
- `.planning/` container docs and GSD workflow state.
- Main source directories (`cmd/`, `internal/`, `db/`, `test/`) described by these files.

### External
- None directly; this directory documents dependencies used elsewhere.

<!-- MANUAL: -->
