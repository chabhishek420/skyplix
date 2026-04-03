<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# internal

## Purpose
Core application business logic organized by domain. Contains HTTP handlers, models, middleware, and processing pipelines.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `server/` | HTTP server setup, routing, middleware (see `server/AGENTS.md`) |
| `model/` | Data models and structures (see `model/AGENTS.md`) |
| `action/` | Action processing handlers (see `action/AGENTS.md`) |
| `filter/` | Traffic filtering and detection (see `filter/AGENTS.md`) |
| `queue/` | Queue writer for async processing (see `queue/AGENTS.md`) |
| `worker/` | Background workers for batch processing (see `worker/AGENTS.md`) |
| `admin/` | Admin UI handlers and repositories (see `admin/AGENTS.md`) |
| `auth/` | Authentication middleware |
| `binding/` | Request binding and validation |
| `botdb/` | Bot detection IP/UA store (see `botdb/AGENTS.md`) |
| `cache/` | Caching layer |
| `config/` | Configuration management |
| `cookie/` | Cookie handling |
| `device/` | Device detection and parsing |
| `geo/` | Geolocation/IP lookup |
| `hitlimit/` | Hit limiting logic |
| `lptoken/` | Landing page token handling |
| `macro/` | Macro substitution |
| `metrics/` | Prometheus metrics |
| `pipeline/` | Processing pipeline stages |
| `ratelimit/` | Redis-based rate limiting (see `ratelimit/AGENTS.md`) |
| `rotator/` | URL rotation |
| `session/` | Session management |
| `valkey/` | Redis/Valkey client |

## For AI Agents

### Working In This Directory
- Primary business logic lives here
- Follow Go idioms: interfaces, dependency injection
- Use context.Context for request-scoped values

### Common Patterns
- Repository pattern for data access
- Middleware chaining for cross-cutting concerns
- Structured logging with zap

## Dependencies
- Go standard library
- Chi router for HTTP
- pgx for PostgreSQL
- ClickHouse client
- Redis client

<!-- MANUAL: -->