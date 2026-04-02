<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# server

## Purpose
HTTP server setup, routing configuration, and middleware.

## Key Files
| File | Description |
|------|-------------|
| `server.go` | Server initialization and configuration |
| `routes.go` | Route definitions and handler registration |

## For AI Agents

### Working In This Directory
- Uses Chi router for HTTP routing
- Middleware includes auth, logging, CORS
- Register handlers in routes.go

### Dependencies
- `github.com/go-chi/chi/v5`

<!-- MANUAL: -->