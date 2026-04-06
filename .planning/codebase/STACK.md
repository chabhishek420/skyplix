# Technology Stack

**Analysis Date:** 2026-04-06

## Languages

- Go 1.25.6 is the primary backend language (`go.mod`, `cmd/zai-tds/main.go`, `internal/**`).
- TypeScript 5 is used for the admin UI (`admin-ui/package.json`, `admin-ui/src/**`).
- SQL migrations are maintained for PostgreSQL and ClickHouse (`db/postgres/migrations/*.sql`, `db/clickhouse/migrations/*.sql`).
- YAML is used for runtime and infra configuration (`config.yaml`, `docker-compose.yml`, `deploy/**`).

## Runtime and Tooling

- Backend runtime: Go toolchain (`go build`, `go run`, `go test` in project docs).
- CLI framework: Cobra (`cmd/zai-tds/main.go`).
- HTTP router: Chi v5 (`internal/server/routes.go`).
- Structured logging: zap (`cmd/zai-tds/main.go`, `internal/**`).
- Frontend build/dev: Vite + TypeScript (`admin-ui/package.json`, `admin-ui/src/main.tsx`).

## Core Backend Dependencies

| Dependency | Version | Purpose |
|---|---|---|
| `github.com/go-chi/chi/v5` | `v5.2.5` | HTTP routing and sub-routers (`internal/server/routes.go`) |
| `github.com/jackc/pgx/v5` | `v5.9.1` | PostgreSQL driver and pooling (`internal/server/server.go`) |
| `github.com/redis/go-redis/v9` | `v9.18.0` | Valkey/Redis cache and state (`internal/cache`, `internal/session`) |
| `github.com/ClickHouse/clickhouse-go/v2` | `v2.44.0` | Analytics write/read access (`internal/queue/writer.go`, `internal/analytics/service.go`) |
| `github.com/oschwald/geoip2-golang` | `v1.13.0` | GeoIP enrichment (`internal/geo/geo.go`) |
| `github.com/golang-jwt/jwt/v5` | `v5.3.1` | Auth token issuance/verification (`internal/auth/service.go`) |
| `github.com/prometheus/client_golang` | `v1.23.2` | Metrics and `/metrics` export (`internal/metrics/metrics.go`, `internal/server/routes.go`) |

## Frontend Stack (`admin-ui`)

- React 19, React Router 7, TanStack Query and Table (`admin-ui/package.json`).
- Tailwind CSS v4, CVA, and utility helpers (`admin-ui/package.json`, `admin-ui/src/index.css`).
- Forms and validation: React Hook Form + Zod (`admin-ui/package.json`).
- Charting: Recharts (`admin-ui/package.json`, `admin-ui/src/pages/dashboard.tsx`).

## Infrastructure and Services

- PostgreSQL 16 (`docker-compose.yml` service `postgres`).
- Valkey 8 (`docker-compose.yml` service `valkey`).
- ClickHouse 24 (`docker-compose.yml` service `clickhouse`).
- Prometheus and Grafana for observability (`docker-compose.yml`, `deploy/grafana/provisioning/**`).
- Embedded admin UI served by backend under `/admin` (`internal/server/routes.go`, `internal/server/spa.go`).

## Configuration Surfaces

- Main runtime config: `config.yaml`.
- Config loader and env override logic: `internal/config/config.go`.
- DB schemas/migrations: `db/postgres/migrations/*.sql`, `db/clickhouse/migrations/*.sql`.
- Container orchestration: `docker-compose.yml`.
