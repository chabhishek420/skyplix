# STACK

## Runtime and Language
- Primary language: Go (`go 1.25.6`) in `go.mod`.
- Main executable entrypoint: `cmd/zai-tds/main.go`.
- Service boots from YAML config (`config.yaml`) plus env overrides in `internal/config/config.go`.

## Core Backend Libraries
- HTTP router/middleware: `github.com/go-chi/chi/v5` used in `internal/server/routes.go`.
- Logging: `go.uber.org/zap` used across server/services (`cmd/zai-tds/main.go`, `internal/server/server.go`).
- PostgreSQL driver/pool: `github.com/jackc/pgx/v5/pgxpool` used in `internal/server/server.go`.
- Valkey/Redis client: `github.com/redis/go-redis/v9` used in cache/session/rate-limit layers.
- ClickHouse client: `github.com/ClickHouse/clickhouse-go/v2` used in `internal/queue/writer.go`.
- UUID support: `github.com/google/uuid` used in models/repositories.
- YAML parsing: `gopkg.in/yaml.v3` used in `internal/config/config.go`.
- GeoIP parsing: `github.com/oschwald/geoip2-golang` used in `internal/geo/geo.go`.

## Internal Runtime Components
- HTTP server + dependency wiring: `internal/server/server.go`.
- Request pipelines: `internal/pipeline/pipeline.go` + `internal/pipeline/stage/*.go`.
- Async analytics writer: `internal/queue/writer.go`.
- Entity cache and DB fallback: `internal/cache/cache.go`.
- Bot detection storage: `internal/botdb/store.go`, `internal/botdb/valkey.go`.
- Session/uniqueness tracking: `internal/session/session.go`.
- Stream/action/filter engines: `internal/action/*.go`, `internal/filter/*.go`.
- Background workers: `internal/worker/*.go`.

## Data Storage Stack
- PostgreSQL schema migrations in `db/postgres/migrations/*.sql`.
- ClickHouse analytical tables in `db/clickhouse/migrations/*.sql`.
- Valkey key-value store for cache/session/rate-limit/botdb.
- GeoIP MMDB files configured under `data/geoip/*.mmdb`.

## Local Infrastructure / Dev Environment
- Local compose services in `docker-compose.yml`:
- PostgreSQL 16 (`postgres:16-alpine`) on `127.0.0.1:5432`.
- Valkey 8 (`valkey/valkey:8-alpine`) on `127.0.0.1:6379`.
- ClickHouse 24 (`clickhouse/clickhouse-server:24-alpine`) on `127.0.0.1:9000` and `8123`.

## Config Sources
- Static defaults and validation in `internal/config/config.go`.
- File config in `config.yaml`.
- Env overrides: `SERVER_HOST`, `SERVER_PORT`, `DATABASE_URL`, `VALKEY_URL`, `CLICKHOUSE_URL`, `SYSTEM_SALT`, `DEBUG`, `LOG_LEVEL`.

## Build and Test Tooling
- Build/run instructions documented in `AGENTS.md`.
- Unit and integration tests in `test/unit/*` and `test/integration/*`.
- Benchmark scaffold in `test/benchmark/latency_test.go`.

## Non-runtime Reference Assets
- `reference/` contains legacy/reference material (Next.js and Keitaro source snapshots).
- `admin-ui/` currently contains guidance files only (`admin-ui/AGENTS.md`, `admin-ui/CLAUDE.md`), no active frontend app files detected.
