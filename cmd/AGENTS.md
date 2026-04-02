<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# cmd

## Purpose
Application entry points and CLI command implementations.

## Key Files
| File | Description |
|------|-------------|
| `zai-tds/main.go` | Application entry point, initializes server and starts HTTP listener |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `zai-tds/` | Main application entry (see `zai-tds/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Entry point for the application
- Initialize dependencies before modifying main.go
- Run with: `go run cmd/zai-tds/main.go`

### Dependencies
- `internal/config` - Configuration loading
- `internal/server` - HTTP server setup
- `internal/valkey` - Redis/Valkey client
- `internal/queue` - Queue writer initialization
- `internal/worker` - Background worker startup

<!-- MANUAL: -->