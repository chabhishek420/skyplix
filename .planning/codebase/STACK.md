# Technology Stack

**Analysis Date:** 2026-04-02

## Languages

**Primary:**
- **Go 1.25.6** - Main application backend, all server-side logic
- **TypeScript** - GSD SDK tooling (`.gsd-source/sdk/`)
- **YAML** - Configuration files (`config.yaml`)
- **JavaScript** - GSD CLI tooling (`bin/install.js`)

## Runtime

**Environment:**
- Go 1.25.6 (module-aware)

**Package Manager:**
- Go modules (go.mod/go.sum)
- npm for TypeScript/Node tooling

## Frameworks

**Core:**
- **chi/v5** v5.2.5 - HTTP router for the TDS server (`github.com/go-chi/chi/v5`)
- **pgx/v5** v5.9.1 - PostgreSQL driver with connection pooling (`github.com/jackc/pgx/v5`)

**Data Storage:**
- Native database drivers (no ORM)
- **clickhouse-go/v2** v2.44.0 - ClickHouse analytics database
- **go-redis/v9** v9.18.0 - Valkey (Redis fork) client

**IP Intelligence:**
- **geoip2-golang** v1.13.0 - MaxMind GeoIP2 reader (`github.com/oschwald/geoip2-golang`)
- **useragent** v1.3.5 - User-Agent parsing (`github.com/mileusna/useragent`)

**Utilities:**
- **zap** v1.27.1 - Structured logging (`go.uber.org/zap`)
- **uuid** v1.6.0 - UUID generation (`github.com/google/uuid`)
- **yaml.v3** v3.0.1 - YAML config parsing (`gopkg.in/yaml.v3`)

## Configuration

**Primary Config:** `config.yaml`
- Server (host/port)
- PostgreSQL (DSN)
- Valkey (addr/password/db)
- ClickHouse (addr/database/username/password)
- GeoIP (country_db/city_db/asn_db paths)
- System (salt/debug/log_level)

**Environment Variable Overrides:**
| Variable | Config Field |
|----------|-------------|
| `SERVER_HOST` | server.host |
| `SERVER_PORT` | server.port |
| `DATABASE_URL` | postgres.dsn |
| `VALKEY_URL` | valkey.addr |
| `CLICKHOUSE_URL` | clickhouse.addr |
| `SYSTEM_SALT` | system.salt |
| `DEBUG` | system.debug |
| `LOG_LEVEL` | system.log_level |

**Alternative Config Path:**
- `CONFIG_PATH` env var to specify custom config file location

## Deployment

**Container Platform:**
- Docker Compose (see `docker-compose.yml`)
- Services: PostgreSQL 16-alpine, Valkey 8-alpine, ClickHouse 24-alpine

**Development Setup:**
- All services on localhost with health checks
- Bound to 127.0.0.1 (not 0.0.0.0) for security

## Architecture

**Pattern:** Pipeline-based request processing (23 stages)

**Key Files:**
- `cmd/zai-tds/main.go` - Entry point
- `internal/server/server.go` - Server initialization, DB connections
- `internal/server/routes.go` - HTTP routes and click handler
- `internal/pipeline/pipeline.go` - Stage execution framework
- `internal/pipeline/stage/` - Individual pipeline stages

---

*Stack analysis: 2026-04-02*
