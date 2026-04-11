# Technology Stack

**Analysis Date:** 2026-04-08

## Languages

**Primary:**
- Go 1.25.6 - Backend runtime, HTTP server, click pipeline, admin API, data integrations in `cmd/zai-tds/main.go`, `internal/server/server.go`, and `internal/**`.
- TypeScript (React SPA) - Admin UI in `admin-ui/src/**/*.tsx` with build config in `admin-ui/vite.config.ts`.

**Secondary:**
- SQL - PostgreSQL and ClickHouse schema/migration files in `db/postgres/**` and `db/clickhouse/**`; executed by `cmd/migrate-ch/main.go`.
- YAML - Runtime/deployment configuration in `config.yaml`, `docker-compose.yml`, and `deploy/prometheus.yml`.
- JavaScript - Tooling configs in `admin-ui/eslint.config.js`.

## Runtime

**Environment:**
- Go runtime 1.25.x (`go 1.25.6` in `go.mod`).
- Node.js 22 for frontend build stage (`FROM node:22-alpine` in `Dockerfile`).
- Alpine Linux container runtime (`FROM alpine:3.20` in `Dockerfile`).

**Package Manager:**
- Go modules (`go.mod`, `go.sum`).
- npm (`package-lock.json`, `admin-ui/package-lock.json`).
- Lockfile: present.

## Frameworks

**Core:**
- Chi v5 (`github.com/go-chi/chi/v5`) - HTTP router/middleware in `internal/server/routes.go`.
- pgx v5 (`github.com/jackc/pgx/v5`) - PostgreSQL access in `internal/server/server.go` and `internal/admin/repository/*.go`.
- go-redis/v9 (`github.com/redis/go-redis/v9`) - Valkey client in `internal/server/server.go`, `internal/cache/cache.go`, `internal/attribution/service.go`.
- ClickHouse Go v2 (`github.com/ClickHouse/clickhouse-go/v2`) - Analytics writer/reader in `internal/queue/writer.go` and `internal/server/server.go`.

**Testing:**
- Testify v1.11.1 (`github.com/stretchr/testify`) for Go tests (declared in `go.mod`, used across `test/**` and `internal/**/*_test.go`).

**Build/Dev:**
- Cobra v1.10.2 (`github.com/spf13/cobra`) - CLI command wiring in `cmd/zai-tds/main.go`.
- Vite v8 + React plugin - Admin UI build/dev server in `admin-ui/package.json` and `admin-ui/vite.config.ts`.
- TypeScript 5.9 + ESLint 9 - Frontend typing/linting in `admin-ui/package.json` and `admin-ui/eslint.config.js`.
- Docker / Docker Compose - Local/prod packaging and infra orchestration in `Dockerfile` and `docker-compose.yml`.

## Key Dependencies

**Critical:**
- `github.com/jackc/pgx/v5` - Primary metadata store access (campaigns, streams, settings, users) via repository layer in `internal/admin/repository/*.go`.
- `github.com/redis/go-redis/v9` - Hot-path cache/session/attribution uniqueness storage in `internal/cache/cache.go`, `internal/session/session.go`, `internal/attribution/service.go`.
- `github.com/ClickHouse/clickhouse-go/v2` - High-volume click/conversion analytics ingest and reporting in `internal/queue/writer.go` and `internal/analytics/service.go`.
- `go.uber.org/zap` - Structured logging in startup/server/services (`cmd/zai-tds/main.go`, `internal/server/server.go`).

**Infrastructure:**
- `github.com/prometheus/client_golang` - Metrics emission and `/metrics` exposure in `internal/metrics/metrics.go` and `internal/server/routes.go`.
- `github.com/oschwald/geoip2-golang` - GeoIP database lookups in `internal/geo/geo.go`.
- `github.com/golang-jwt/jwt/v5` - JWT auth token generation/validation in `internal/auth/service.go`.
- `github.com/go-sql-driver/mysql` - Legacy Keitaro migration source connectivity in `scripts/migrate_keitaro.go`.

## Configuration

**Environment:**
- Primary config source: YAML file loaded by `internal/config/config.go` (default `config.yaml` via `cmd/zai-tds/main.go`).
- Environment variable overrides supported in `internal/config/config.go`: `SERVER_HOST`, `SERVER_PORT`, `DATABASE_URL`, `VALKEY_URL`, `CLICKHOUSE_URL`, `SYSTEM_SALT`, `ALLOW_CHANGE_REFERRER`, `DEBUG`, `LOG_LEVEL`.
- Additional startup/env controls in `cmd/zai-tds/main.go`: `CONFIG_PATH`.
- Runtime settings-backed secrets/keys are read from PostgreSQL `settings` table in `internal/admin/repository/settings.go` (for example `tracker.postback_key`, `tracker.postback_salt` consumed by `internal/admin/handler/postback.go`).

**Build:**
- Backend build and static UI embedding in `Dockerfile`.
- Local infra/service wiring in `docker-compose.yml`.
- Prometheus scrape config in `deploy/prometheus.yml`.
- Grafana datasource provisioning in `deploy/grafana/provisioning/datasources/prometheus.yml`.
- Frontend build/lint config in `admin-ui/vite.config.ts`, `admin-ui/tsconfig.json`, `admin-ui/eslint.config.js`.

## Platform Requirements

**Development:**
- Go 1.25+ (`go.mod`).
- Node.js/npm for `admin-ui` build (`admin-ui/package.json`).
- Docker + Docker Compose for local dependencies (`docker-compose.yml`).
- Local services required: PostgreSQL 16, Valkey 8, ClickHouse 24, Prometheus, Grafana (defined in `docker-compose.yml`).

**Production:**
- Linux container deployment target with bundled Go binary (`Dockerfile`).
- Network access from app container to PostgreSQL, Valkey, ClickHouse; optional outbound HTTP for VPN checks and remote offer fetches in `internal/cloak/detector.go` and `internal/action/proxy.go`.

---

*Stack analysis: 2026-04-08*
