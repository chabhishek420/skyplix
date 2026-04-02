<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# admin-ui

## Purpose
Frontend admin interface workspace for the tracking system. This directory is currently scaffolded with structure in place but no implemented UI source files yet.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `src/` | Reserved location for admin UI source code (currently empty scaffolding). |

## For AI Agents

### Working In This Directory
- Treat this as a future React/Vite app area unless project direction changes.
- Add frontend manifests (`package.json`, Vite config) before introducing non-trivial UI code.
- Keep API contracts aligned with handlers under `internal/admin/`.

### Testing Requirements
- Once UI implementation starts, run frontend unit/build checks from this directory.
- For now, no runnable frontend tests exist here.

### Common Patterns
- Source files should live under `src/` and avoid coupling to backend internals.
- Prefer typed API clients generated from stable admin endpoints.

## Dependencies

### Internal
- `internal/admin/` backend endpoints consumed by the UI.

### External
- Not yet declared in this repository (frontend package manifest pending).

<!-- MANUAL: -->
