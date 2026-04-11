# External Integrations

**Analysis Date:** 2026-04-08

## APIs & External Services

**Traffic/Offer Fetching:**
- Arbitrary offer/landing URLs (configured per stream/action) - server-side remote fetch and proxy behavior for cloaking/relay.
  - SDK/Client: Go `net/http` (`http.Get`, `http.Client`, `http.NewRequestWithContext`) in `internal/action/content.go` and `internal/action/proxy.go`.
  - Auth: Not applicable by default (passes browser-like headers such as User-Agent/Referer).

**Bot/VPN Intelligence:**
- Configurable VPN/proxy lookup endpoint (`system.vpn_api`) - IP quality checks in cloaking flow.
  - SDK/Client: Go `net/http` in `internal/cloak/detector.go`.
  - Auth: Provider-specific; base URL configured through `vpn_api` in `config.yaml` / `SYSTEM_*` config path via `internal/config/config.go`.

**Observability Services:**
- Prometheus - metrics scraping from `/metrics`.
  - SDK/Client: `github.com/prometheus/client_golang` in `internal/metrics/metrics.go`.
  - Auth: Not detected in current local config (`deploy/prometheus.yml`).
- Grafana - dashboards via provisioned Prometheus datasource.
  - SDK/Client: Grafana datasource provisioning in `deploy/grafana/provisioning/datasources/prometheus.yml`.
  - Auth: Not configured in repository files.

## Data Storage

**Databases:**
- PostgreSQL
  - Connection: `postgres.dsn` in `config.yaml` or `DATABASE_URL` env var (resolved in `internal/config/config.go`).
  - Client: `pgxpool` (`github.com/jackc/pgx/v5/pgxpool`) in `internal/server/server.go`.
- ClickHouse
  - Connection: `clickhouse.addr` in `config.yaml` or `CLICKHOUSE_URL` env var.
  - Client: `clickhouse-go/v2` writer/reader in `internal/queue/writer.go`, `internal/server/server.go`, and migrations in `cmd/migrate-ch/main.go`.

**File Storage:**
- Local filesystem only.
  - GeoIP MMDB files loaded from `data/geoip/*.mmdb` paths configured in `config.yaml` and read in `internal/geo/geo.go`.
  - Static/landing files served from local `data/landers/**` in `internal/action/content.go`.

**Caching:**
- Valkey (Redis-compatible)
  - Connection: `valkey.addr` in `config.yaml` or `VALKEY_URL` env var.
  - Client: `github.com/redis/go-redis/v9` in `internal/server/server.go` and services (`internal/cache/cache.go`, `internal/session/session.go`, `internal/attribution/service.go`).

## Authentication & Identity

**Auth Provider:**
- Custom auth (internal database-backed).
  - Implementation: API key login (`POST /api/v1/auth/login`) against `users.api_key` plus JWT issuance/validation in `internal/auth/service.go`; middleware accepts `Authorization: Bearer` and fallback `X-Api-Key`.

## Monitoring & Observability

**Error Tracking:**
- None detected (no Sentry/Bugsnag/Rollbar client imports in `internal/**`).

**Logs:**
- Structured application logging with Zap in `cmd/zai-tds/main.go` and `internal/server/server.go`.
- Metrics-based observability through Prometheus counters/histograms in `internal/metrics/metrics.go` and endpoint in `internal/server/routes.go`.

## CI/CD & Deployment

**Hosting:**
- Containerized deployment target (multi-stage Docker build) in `Dockerfile`.
- Local orchestration with Docker Compose in `docker-compose.yml`.

**CI Pipeline:**
- Not detected (`.github/` workflow directory is not present in this working tree).

## Environment Configuration

**Required env vars:**
- Core runtime/overrides consumed by `internal/config/config.go`: `DATABASE_URL`, `VALKEY_URL`, `CLICKHOUSE_URL`, `SERVER_HOST`, `SERVER_PORT`, `SYSTEM_SALT`, `ALLOW_CHANGE_REFERRER`, `DEBUG`, `LOG_LEVEL`.
- Startup path override consumed by `cmd/zai-tds/main.go`: `CONFIG_PATH`.

**Secrets location:**
- Config file values in `config.yaml` (development defaults present).
- Environment variables (runtime override path in `internal/config/config.go`).
- DB-backed settings keys in PostgreSQL `settings` table for postback key/salt (`internal/admin/repository/settings.go`, `internal/admin/handler/postback.go`).
- `.env` files are present under `reference/**` and should be treated as environment configuration artifacts; contents were not read.

## Webhooks & Callbacks

**Incoming:**
- Postback callbacks (GET/POST): `/postback/{key}` in `internal/server/routes.go`, handled by `internal/admin/handler/postback.go`.
- Pixel callback: `/pixel.gif` in `internal/server/routes.go`, handled by `internal/admin/handler/postback.go`.
- Cloak JS challenge callback: `/js/challenge` in `internal/server/routes.go`, handled by `internal/cloak/handler.go`.

**Outgoing:**
- Outbound HTTP to configured VPN intelligence endpoint: `d.vpnAPI + ip` in `internal/cloak/detector.go`.
- Outbound HTTP fetches to stream redirect/offer URLs in `internal/action/content.go` and `internal/action/proxy.go`.
- Generated outbound affiliate postback templates use internal callback endpoint pattern from `internal/macro/postback.go` and are exposed by `internal/admin/handler/networks.go`.

---

*Integration audit: 2026-04-08*
