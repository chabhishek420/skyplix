# External Integrations

**Analysis Date:** 2026-04-03

## APIs & External Services

**HTTP Fetch (Cloaking / Proxying):**
- Arbitrary remote web servers — fetched server-side as part of cloaking/safe-page behavior
  - Implementation: `internal/action/proxy.go` (`RemoteProxyAction`)
  - Method: `GET` to `ActionContext.RedirectURL`
  - Notes: Copies some browser headers; TTL caches responses in-memory

## Data Storage

**PostgreSQL:**
- Purpose: admin entities and configuration (campaigns/streams/offers/landings/users/etc.)
  - Connection: `postgres.dsn` in `config.yaml` / `DATABASE_URL` env var
  - Client: `github.com/jackc/pgx/v5/pgxpool` (constructed in `internal/server/server.go`)
  - Schema/migrations: `db/`
  - Admin auth query: `internal/admin/middleware.go`

**ClickHouse:**
- Purpose: high-volume analytics/event ingestion for clicks/conversions
  - Connection: `clickhouse.addr` + `clickhouse.database` in `config.yaml` / `CLICKHOUSE_URL` env var
  - Client: `github.com/ClickHouse/clickhouse-go/v2` (`internal/queue/writer.go`)
  - Write path: pipeline stage `internal/pipeline/stage/StoreRawClicksStage` → channel from `internal/queue/writer.go`

**Valkey / Redis:**
- Purpose: cache, sessions, bot IP/UA lists, hit limiting, misc runtime state
  - Connection: `valkey.addr` in `config.yaml` / `VALKEY_URL` env var
  - Client: `github.com/redis/go-redis/v9` (constructed in `internal/server/server.go`)
  - Modules using Valkey: `internal/cache/`, `internal/session/`, `internal/botdb/`, `internal/hitlimit/`, `internal/ratelimit/`, `internal/lptoken/`

**GeoIP Databases (local files):**
- Purpose: geolocation and ASN lookups
  - Inputs: MaxMind `.mmdb` files in `data/geoip/` configured via `geoip.*` in `config.yaml`
  - Implementation: `internal/geo/*`

## Authentication & Identity

**Admin API Key:**
- Mechanism: `X-Api-Key` HTTP header
  - Middleware: `internal/admin/middleware.go` (`APIKeyAuth`)
  - Validation: DB lookup in Postgres (`users` table)

## Monitoring & Observability

**Logging:**
- `go.uber.org/zap` (development vs production logger selected in `cmd/zai-tds/main.go`)

## CI/CD & Deployment

**Local dev services:**
- `docker-compose.yml` provides `postgres`, `valkey`, `clickhouse`

**Deployment:**
- Not defined in-repo (no `.github/workflows/` found). Likely deploys as a Go binary/container.

## Environment Configuration

**Config sources:**
- YAML: `config.yaml` (overridable via `CONFIG_PATH`)
- Env vars: see `internal/config/config.go` override list

## Webhooks & Callbacks

- None observed in the Go server code (no inbound webhook endpoints beyond admin + click paths in `internal/server/routes.go`).

---

*Integrations analysis: 2026-04-03*
*Update as new external dependencies are added*
