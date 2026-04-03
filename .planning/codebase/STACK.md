# Technology Stack

**Analysis Date:** 2026-04-03

## Languages

**Primary:**
- Go (module `go.mod`, directive `go 1.25.6`) — all backend application code (`cmd/`, `internal/`, `test/`)

**Secondary:**
- YAML — configuration (`config.yaml`)
- SQL — schemas/migrations (`db/`)
- Shell/Docker — local service orchestration (`docker-compose.yml`)

## Runtime

**Environment:**
- Go toolchain — builds a single HTTP server binary (`cmd/zai-tds/main.go`)

**Package Manager:**
- Go modules — `go.mod`, `go.sum`

## Frameworks

**Core (HTTP):**
- `github.com/go-chi/chi/v5` — router + middleware (`internal/server/routes.go`)
- `go.uber.org/zap` — structured logging (`cmd/zai-tds/main.go`, `internal/server/server.go`)

**Persistence/Storage Clients:**
- `github.com/jackc/pgx/v5/pgxpool` — PostgreSQL connection pool (`internal/server/server.go`)
- `github.com/ClickHouse/clickhouse-go/v2` — ClickHouse ingestion (`internal/queue/writer.go`)
- `github.com/redis/go-redis/v9` — Valkey/Redis client (`internal/server/server.go`)

**Testing:**
- Go `testing` stdlib — unit/integration tests (`test/unit/*`, `test/integration/*`)

## Key Dependencies

**Critical:**
- `github.com/go-chi/chi/v5` — HTTP routing for admin + click endpoints (`internal/server/routes.go`)
- `github.com/jackc/pgx/v5` — PostgreSQL data access (`internal/admin/handler/*`, `internal/server/server.go`)
- `github.com/redis/go-redis/v9` — caching/session/botdb storage (`internal/cache/*`, `internal/session/*`, `internal/botdb/*`)
- `github.com/ClickHouse/clickhouse-go/v2` — async analytics writes (`internal/queue/writer.go`)
- `github.com/oschwald/geoip2-golang` — GeoIP resolution (`internal/geo/*`)
- `github.com/mileusna/useragent` — UA parsing (`internal/device/*`)

## Configuration

**Runtime config:**
- Primary config file: `config.yaml` (path override via `CONFIG_PATH` in `cmd/zai-tds/main.go`)
- Env overrides (examples in `internal/config/config.go`): `DATABASE_URL`, `VALKEY_URL`, `CLICKHOUSE_URL`, `SERVER_HOST`, `SERVER_PORT`, `SYSTEM_SALT`, `DEBUG`, `LOG_LEVEL`

**Local dependencies:**
- `docker-compose.yml` provisions local `postgres`, `valkey`, and `clickhouse` services bound to `127.0.0.1`

## Platform Requirements

**Development:**
- Go installed
- Docker (recommended) for Postgres/Valkey/ClickHouse (`docker-compose.yml`)
- GeoIP DB files expected in `data/geoip/` (`config.yaml`)

**Production (inferred):**
- Provide Postgres + Valkey + ClickHouse endpoints via config/env
- Set `SYSTEM_SALT` to a secure value (validated in `internal/config/config.go`)

---

*Stack analysis: 2026-04-03*
*Update after major dependency changes*
