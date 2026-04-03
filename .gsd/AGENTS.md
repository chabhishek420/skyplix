<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-03 -->

# .gsd — Goal-State-Driven Planning System

## Purpose
AI agent planning artifacts. Contains phases, milestones, decisions, research, architecture docs, and workflow templates for the SkyPlix TDS project.

## Key Files
| File | Purpose |
|------|---------|
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
| `phases/` | Phase plans, summaries, verification docs (see `phases/AGENTS.md`) |
| `milestones/` | Milestone audit documents (see `milestones/AGENTS.md`) |
| `examples/` | Workflow examples and quick reference |
| `templates/` | 24 document templates |

## Phase Status
| Phase | Status | Focus |
|-------|--------|-------|
| 1 | ✅ Complete | Foundation — Go project, core pipeline, workers |
| 1.5 | ✅ Complete | Maintenance — graceful shutdown, UUID validation |
| 2 | ✅ Complete | Campaign Engine — streams, filters, rotators, binding |
| 3 | ✅ Complete | Admin API — CRUD for all P0/P1 entities |
| 4 | ✅ Complete | Advanced Cloaking — bot detection, safe pages, rate limiting |
| 4.9 | ⬜ Next | Gap Closure — Global Uniqueness, p99 benchmarks |
| 5 | ⬜ Pending | Conversion Tracking & Analytics |
| 6 | ⬜ Pending | Admin Dashboard UI |
| 7 | ⬜ Pending | Production Hardening |

## For AI Agents

### Working In This Project
- Use `.gsd/phases/` for phase-specific context
- `STATE.md` shows current position and next steps
- `ROADMAP.md` shows all phases and requirements
- Follow templates in `templates/` when creating new docs

### Commands
```bash
# Sync planning to opencode
bash .agent/scripts/sync-planning.sh

# Run integration tests
go test -v -tags integration ./test/integration/... -timeout 30s
```

<!-- MANUAL: -->
