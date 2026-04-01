<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# reference

## Purpose
Archived upstream/reference implementations used for parity research and reverse engineering. This directory intentionally contains foreign codebases and vendored trees that should not receive full deep-init expansion by default.

## Key Files
None currently documented.

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `Keitaro_source_php/` | Large PHP Keitaro reference codebase. |
| `KeitaroCustomScripts/` | Custom Keitaro scripts reference repo. |
| `YellowCloaker/` | Archived cloaker codebase reference. |
| `akm-traffic-tracker/` | Traffic tracker reference project. |
| `pp_adsensor/` | Adsensor reference project. |

## For AI Agents

### Working In This Directory
- Treat everything here as read-mostly source material unless a task explicitly asks you to modify a reference project.
- Do not mirror the entire nested vendor structure with AGENTS files unless the user requests deep documentation for a specific reference repo.

### Testing Requirements
- No project-wide testing is expected for reference material changes unless working inside one reference repo on purpose.

### Common Patterns
- Mixed third-party/project archives, often with their own VCS metadata and vendor trees.

## Dependencies

### Internal
- `src/lib/tds/` and `docs/` may cite these projects for parity research.

### External
- Varies by reference project

<!-- MANUAL: Add directory-specific notes below this line. -->
