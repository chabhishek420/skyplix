<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# worker

## Purpose
Background workers for batch processing, cache warmup, and scheduled tasks.

## Key Files
| File | Description |
|------|-------------|
| `worker.go` | Worker initialization and runner |
| `cache_warmup.go` | Cache warming on startup |
| `hitlimit_reset.go` | Periodic hit limit reset |

## For AI Agents

### Working In This Directory
- Runs background tasks (not request-handling)
- Cache warmup on application start
- Periodic maintenance tasks

<!-- MANUAL: -->