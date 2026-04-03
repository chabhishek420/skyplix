# CONVENTIONS

## Source of Truth for Conventions
- Root guidance in `AGENTS.md` defines style, naming, logging, and testing expectations.
- Package-level docs (`internal/AGENTS.md`, `test/AGENTS.md`, etc.) complement local norms.

## Language and Naming
- Go naming follows exported PascalCase and unexported camelCase.
- Package names are short lowercase words (`internal/queue`, `internal/filter`, `internal/geo`).
- Sentinel error naming pattern exists (example `ErrRedispatch` in `internal/action/action.go`).

## Error Handling Patterns
- Common pattern: immediate `if err != nil` returns with context wrapping (`fmt.Errorf("...: %w", err)`).
- SQL and cache paths usually propagate errors up to handlers/stages.
- Some non-critical async paths intentionally fail open (e.g., attribution cache in `internal/pipeline/stage/23_store_raw_clicks.go`).

## Context Usage
- Services and repository methods typically take `context.Context` first (examples in `internal/session/session.go`, `internal/admin/repository/*.go`).
- Worker and server lifecycle is context-driven (`internal/worker/worker.go`, `internal/server/server.go`).

## Logging Conventions
- Structured zap logging is standard (`zap.String`, `zap.Duration`, `zap.Error`).
- `cmd/zai-tds/main.go` initializes development vs production logger based on debug mode.

## Architectural Coding Patterns
- Repository pattern for admin SQL access (`internal/admin/repository/*.go`).
- Interface-based abstractions for pluggable behavior:
- `pipeline.Stage` in `internal/pipeline/pipeline.go`.
- `worker.Worker` in `internal/worker/worker.go`.
- Action/filter registries for extensibility in `internal/action/action.go` and `internal/filter/filter.go`.

## HTTP/API Patterns
- JSON responses centralized via helper methods (`internal/admin/handler/helpers.go`).
- Route grouping and middleware layering via chi router (`internal/server/routes.go`).
- Admin APIs rely on `X-Api-Key` auth middleware (`internal/admin/middleware.go`).

## Data/State Patterns
- Read-through caching from Valkey with Postgres fallback (`internal/cache/cache.go`).
- Time-bounded key semantics for session/rate-limit/hit-limit (`internal/session/session.go`, `internal/ratelimit/ratelimit.go`, `internal/hitlimit/hitlimit.go`).
- Async event persistence to ClickHouse through buffered channels (`internal/queue/writer.go`).

## Notable Inconsistencies to Watch
- Several files include maintenance headers (`/* MODIFIED: ... */`) while others do not, so header style is not uniformly enforced.
- A few calls ignore returned errors (examples in `internal/server/server.go` and some `cache` set operations), which differs from strict guidance in `AGENTS.md`.
