<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# .planning

## Purpose
Project planning workspace for architecture analysis and roadmap artifacts used by GSD-style workflows.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `codebase/` | Deep codebase analysis outputs (see `codebase/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Treat these files as planning/reference artifacts, not runtime source code.
- Update analysis docs when major architecture or stack decisions change.
- Keep roadmap and concern docs consistent with current implementation status.

### Testing Requirements
- No direct test execution is required for pure planning-document changes.
- If planning docs drive implementation, validate resulting code changes via `go test ./...`.

### Common Patterns
- Markdown-first docs with phase tracking and architecture snapshots.
- Codebase map files (`STACK`, `ARCHITECTURE`, `CONVENTIONS`) as context inputs for future tasks.

## Dependencies

### Internal
- Root `AGENTS.md` and `.gsd/` workflow artifacts reference this directory.

### External
- None (documentation-only directory)

<!-- MANUAL: -->
