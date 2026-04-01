<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# scripts

## Purpose
Repository maintenance and validation scripts. Most current scripts support cross-platform search and validation workflows; this directory now also owns the deep-init generator.

## Key Files
| File | Description |
| --- | --- |
| `deepinit-generate-agents.mjs` | Generates and refreshes the AGENTS.md hierarchy for the live project surface. |
| `search_repo.sh` | Unix search helper. |
| `validate-all.sh` | Unix validation umbrella script. |
| `validate-skills.sh` | Skill validation helper. |
| `validate-workflows.sh` | Workflow validation helper. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Keep scripts portable where practical; this directory intentionally includes paired `.sh` and `.ps1` variants.
- If you add a new repo-maintenance script, document it here and in the root navigation if it becomes important.

### Testing Requirements
- Run the script you changed directly instead of assuming it remains executable.

### Common Patterns
- Cross-platform maintenance scripts and one-off tooling.

## Dependencies

### Internal
- `docs/` and `planning/` for maintenance context.

### External
- Shell / PowerShell / Node depending on script

<!-- MANUAL: Add directory-specific notes below this line. -->
