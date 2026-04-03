<!-- Parent: ../CLAUDE.md -->
<!-- Generated: 2026-04-03 10:25 | Updated: 2026-04-03 10:25 -->
# .gsd

## Purpose
AI agent planning artifacts. Contains phases, milestones, decisions, research, architecture docs, and workflow templates for the SkyPlix TDS project.

## Key Files
| File | Purpose / Responsibility |
|------|--------------------------|
| `ROADMAP.md` | Phase breakdown with status (current: Phase 4.9.4) |
| `STATE.md` | Current position, next steps, blockers |
| `ARCHITECTURE.md` | System architecture, pipeline stages, components |
| `SPEC.md` | Project spec, goals, Keitaro parity map |
| `STACK.md` | Technology stack, dependencies, DB schemas |
| `DECISIONS.md` | 11 Architecture Decision Records |
| `RESEARCH.md` | Deep technology research, reference analysis |
| `JOURNAL.md` | Session-by-session history |
| `TODO.md` | Pending items by phase |
| `DEBUG.md` | Debug session notes |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `examples/` | Workflow examples, quick reference guides, and cross-platform usage patterns for the GSD system. (see `examples/CLAUDE.md`). |
| `milestones/` | Milestone audit documents — formal review of phase completion against original intent. (see `milestones/CLAUDE.md`). |
| `phases/` | Phase-by-phase execution artifacts: plans, summaries, verification reports, and research. (see `phases/CLAUDE.md`). |
| `templates/` | 24 document templates used by the GSD workflow system. (see `templates/CLAUDE.md`). |

## Claude-specific Guidance
### When Editing Files Here
- Keep roadmap/state docs synchronized with actual implementation progress.
- Capture major decisions in durable docs instead of only in chat/session context.
- Avoid rewriting historical records; append clarifications with timestamps when needed.

### Testing Expectations
- When plans change, verify generated tasks still align with current repo state.
- Cross-check phase status against completed code/tests before marking done.
- Run command examples periodically to ensure docs remain executable.

### Common Patterns & Conventions
- Use concise, outcome-oriented task descriptions tied to verifiable success criteria.
- Prefer parent-first hierarchical docs so agents can resolve context quickly.
- Record blockers and assumptions explicitly to reduce session-to-session drift.

### Dependencies - Internal
- Depends on project root `AGENTS.md` and `.gsd/*` planning artifacts.
- Phase docs in `.gsd/phases/` should map directly to execution work in code directories.

### Dependencies - External
- GSD workflow conventions from upstream `.gsd-source` should be treated as reference contracts.

<!-- MANUAL SECTION -->
<!-- Add project-specific notes, warnings, future plans, etc. below -->
