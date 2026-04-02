<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# zai-tds

## Purpose
A high-performance Go-based tracking/analytics system (TDS - Tracking Delivery System). Handles click tracking, redirect management, action processing, and real-time analytics with PostgreSQL, ClickHouse, and Redis/Valkey.

## Key Files
| File | Description |
|------|-------------|
| `go.mod` | Go module definition with dependencies |
| `go.sum` | Go dependency checksums |
| `config.yaml` | Application configuration |
| `config.json` | Additional configuration |
| `docker-compose.yml` | Docker orchestration for local dev |
| `cmd/zai-tds/main.go` | Application entry point |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `cmd/` | Application entry points and CLI commands (see `cmd/AGENTS.md`) |
| `internal/` | Core application business logic (see `internal/AGENTS.md`) |
| `db/` | Database schemas, migrations, and queries (see `db/AGENTS.md`) |
| `test/` | Test suites (unit and integration) (see `test/AGENTS.md`) |
| `admin-ui/` | Frontend admin interface (see `admin-ui/AGENTS.md`) |
| `.planning/` | Planning and architecture analysis artifacts (see `.planning/AGENTS.md`) |
| `reference/` | Reference implementations and legacy code (see `reference/AGENTS.md`) |

## For AI Agents

### Working In This Project
- This is a Go 1.25.6 project using Chi router
- Primary databases: PostgreSQL (primary data), ClickHouse (analytics), Valkey/Redis (caching)
- Use Go idioms: interfaces, dependency injection, context propagation
- Run with: `go run cmd/zai-tds/main.go`
- Build with: `go build -o zai-tds cmd/zai-tds/main.go`

### Testing Requirements
- Unit tests in `test/unit/`
- Integration tests in `test/integration/`
- Run: `go test ./...`

### Common Patterns
- Configuration via YAML files
- Structured logging with zap
- Database transactions for consistency
- Async processing via workers/queues

## Dependencies

### External
- `github.com/go-chi/chi/v5` - HTTP router
- `github.com/jackc/pgx/v5` - PostgreSQL driver
- `github.com/ClickHouse/clickhouse-go/v2` - ClickHouse driver
- `github.com/redis/go-redis/v9` - Redis/Valkey client
- `go.uber.org/zap` - Structured logging

<!-- MANUAL: -->
