# External Integrations

**Analysis Date:** 2026-04-06

## Databases and State Stores

### PostgreSQL
- Driver and pool: `github.com/jackc/pgx/v5/pgxpool` (`internal/server/server.go`).
- Used for admin entities and config repositories (`internal/admin/repository/*.go`).
- Health/readiness includes ping checks (`internal/server/routes.go`).

### Valkey (Redis protocol)
- Client: `github.com/redis/go-redis/v9` (`internal/server/server.go`).
- Used by cache/session/hit-limit/binding/lp-token/attribution/bot-db services:
  - `internal/cache/cache.go`
  - `internal/session/session.go`
  - `internal/hitlimit/hitlimit.go`
  - `internal/binding/binding.go`
  - `internal/lptoken/lptoken.go`
  - `internal/attribution/service.go`
  - `internal/botdb/valkey.go`, `internal/botdb/uastore.go`

### ClickHouse
- Async writer for clicks/conversions: `internal/queue/writer.go`.
- Optional reader connection for attribution fallback and reports (`internal/server/server.go`, `internal/analytics/service.go`).
- Integration tests directly validate ClickHouse behavior (`test/integration/click_test.go`, `test/integration/routing_test.go`).

## Observability

### Prometheus Metrics
- Metrics are registered in `internal/metrics/metrics.go`.
- Exported at `GET /metrics` via `promhttp.Handler()` (`internal/server/routes.go`).

### Health and Readiness
- Health endpoint: `GET /api/v1/health`.
- Readiness endpoint: `GET /api/v1/ready` checks Postgres, Valkey, and ClickHouse status (`internal/server/routes.go`).

## Authentication and Security Interfaces

- Login endpoint: `POST /api/v1/auth/login` returns JWT (`internal/server/routes.go`, `internal/server/server.go`).
- JWT signing/validation in `internal/auth/service.go`.
- Protected admin routes mounted under `/api/v1` with auth middleware (`internal/server/routes.go`).

## External File/Data Integrations

- GeoIP databases from local files:
  - `data/geoip/GeoLite2-Country.mmdb`
  - `data/geoip/GeoLite2-City.mmdb`
  - `data/geoip/GeoLite2-ASN.mmdb`
  (configured in `config.yaml`, loaded by `internal/geo/geo.go`).
- CIDR bot list loaded from `reference/YellowCloaker/bases/bots.txt` (`internal/server/server.go`).

## Migration and Legacy Input Integration

- Keitaro migration script reads MySQL and writes PostgreSQL (`scripts/migrate_keitaro.go`).
- CLI migration command currently delegates to script and is not fully wrapped (`cmd/zai-tds/main.go`).

## HTTP Integration Surfaces

- Traffic endpoints: `/{alias}`, `/`, and `/lp/{token}/click` (`internal/server/routes.go`).
- Postback endpoints: `GET|POST /postback/{key}` and tracking pixel `GET /pixel.gif` (`internal/server/routes.go`).
- Admin API endpoints for campaigns, streams, offers, landings, domains, users, bots, settings, reports (`internal/server/routes.go`).
