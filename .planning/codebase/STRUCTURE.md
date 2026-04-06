# Codebase Structure

**Analysis Date:** 2026-04-06

## Top-Level Layout

- `cmd/` - executable entrypoints (`cmd/zai-tds/main.go`, `cmd/migrate-ch/main.go`).
- `internal/` - backend business logic and HTTP handlers by domain.
- `db/` - PostgreSQL and ClickHouse migration SQL.
- `test/` - unit, integration, benchmark, and load tests.
- `admin-ui/` - Vite + React admin frontend.
- `deploy/` - deployment and observability provisioning (Grafana, Prometheus config).
- `scripts/` - utility scripts including Keitaro migration.
- `reference/` - legacy/reference data and source snapshots used by some support flows.

## Backend Package Map (`internal/`)

- `internal/server` - server wiring, route registration, click handlers, SPA mounting.
- `internal/pipeline` and `internal/pipeline/stage` - pipeline core + stage implementations.
- `internal/admin/handler` - HTTP handlers for admin APIs.
- `internal/admin/repository` - data access layer for admin entities.
- `internal/queue` - ClickHouse async writer and record shaping.
- `internal/analytics` - analytics query service for reports.
- `internal/model` - core structs and shared domain models.
- Support services: `cache`, `session`, `ratelimit`, `hitlimit`, `binding`, `lptoken`, `attribution`, `filter`, `rotator`, `auth`, `geo`, `device`, `botdb`, `worker`.

## Entry Points

- Main binary: `cmd/zai-tds/main.go`.
- HTTP wiring: `internal/server/server.go` and `internal/server/routes.go`.
- Click flows: `internal/server/routes.go` handlers `handleClick` and `handleClickL2`.
- Admin UI mount: `internal/server/routes.go` mounts SPA at `/admin`.

## Test Layout

- Unit tests: `test/unit/**` and some package-local tests in `internal/**`.
- Integration tests (build tag): `test/integration/**` with external service dependencies.
- Benchmarks: `test/bench`, `test/benchmark`.
- Load scripts: `test/load/click_pipeline.js`.

## Database Artifacts

- Postgres migrations: `db/postgres/migrations/*.up.sql` and `*.down.sql`.
- ClickHouse migrations: `db/clickhouse/migrations/*.sql`.
- Additional optimization script: `db/clickhouse/002_optimize_indexes.sql`.

## Frontend Structure (`admin-ui/src`)

- `pages/` - route-level pages (campaigns, offers, domains, logs, dashboard).
- `components/` - layout/auth/ui components.
- `lib/api.ts` - API client glue.
- `data/mockData.ts` - local mock data source for UI scaffolding.

## Naming and Layout Conventions Observed

- Go package names are lowercase single-word domains (`internal/hitlimit`, `internal/lptoken`).
- API routes are grouped by resource under `/api/v1/*` (`internal/server/routes.go`).
- Stage files are numbered by execution order (`internal/pipeline/stage/1_domain_redirect.go`, etc.).
- SQL migrations use numeric prefixes (`001_...`, `002_...`).
