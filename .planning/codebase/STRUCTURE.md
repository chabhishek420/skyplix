# STRUCTURE

## Repository Layout
- `cmd/zai-tds/main.go`: process entrypoint.
- `internal/`: core application packages (server, pipeline, cache, filters, admin, queue, workers).
- `db/postgres/migrations/`: operational schema migrations.
- `db/clickhouse/migrations/`: analytics schema migrations.
- `test/unit/`: unit tests.
- `test/integration/`: integration tests (`//go:build integration`).
- `test/benchmark/`: benchmark scenarios (`//go:build integration`).
- `data/geoip/`: GeoIP database assets.
- `reference/`: legacy/reference implementations and external datasets (not main runtime).

## Internal Package Map (Key Modules)
- `internal/server/`
- `server.go`: dependency graph + pipeline assembly + lifecycle.
- `routes.go`: API and click route registration.
- `internal/pipeline/`
- `pipeline.go`: pipeline engine + payload.
- `stage/*.go`: numbered click processing stages.
- `internal/admin/`
- `middleware.go`: API key auth.
- `handler/*.go`: endpoint handlers.
- `repository/*.go`: SQL persistence adapters.
- `internal/cache/cache.go`: Valkey preload/read-through layer.
- `internal/queue/writer.go`: ClickHouse batch writer.
- `internal/worker/*.go`: background worker manager and jobs.
- `internal/model/models.go`: shared domain model structures.

## Endpoint-Oriented Structure
- Health endpoint: `internal/server/routes.go` (`/api/v1/health`).
- Admin endpoints grouped under `/api/v1/*` and backed by `internal/admin/handler/*`.
- Click routes in same router with hot-path handlers (`handleClick`, `handleClickL2`).

## Naming and File Organization Patterns
- Package folders are lowercase (`cache`, `ratelimit`, `lptoken`, `botdb`).
- Stages are mostly numeric-prefixed files (`1_domain_redirect.go`, `23_store_raw_clicks.go`).
- Handlers/repositories are entity-oriented (`campaigns.go`, `streams.go`, `users.go`).

## Planning and Agent Metadata Files
- Multiple directories include local guidance files (`AGENTS.md`, `CLAUDE.md`).
- Root-level `AGENTS.md` contains project commands and coding conventions.
- `.planning/` currently stores generated mapping docs in `.planning/codebase/`.

## Test File Organization
- Unit tests colocated by domain in subfolders:
- `test/unit/queue/writer_test.go`
- `test/unit/worker/worker_test.go`
- Integration tests grouped by behavior:
- `test/integration/click_test.go`
- `test/integration/admin_test.go`
- `test/integration/routing_test.go`
- `test/integration/cloaking_test.go`

## Current Structural Gaps
- `admin-ui/` contains only guidance docs; no active frontend source tree found.
- No dedicated `pkg/` public library layer; architecture is service-first monolith under `internal/`.
