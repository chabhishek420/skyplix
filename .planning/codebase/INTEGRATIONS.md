# INTEGRATIONS

## PostgreSQL (Operational Data)
- Connection created in `internal/server/server.go` via `pgxpool.New(ctx, cfg.Postgres.DSN)`.
- DSN configured through `config.yaml` (`postgres.dsn`) or `DATABASE_URL` env override in `internal/config/config.go`.
- Schema managed by SQL migrations under `db/postgres/migrations/*.sql`.
- Admin CRUD repositories hit Postgres directly (example: `internal/admin/repository/campaigns.go`).

## ClickHouse (Analytics Sink)
- Click writer initialized in `internal/server/server.go` using `queue.NewWriter(...)`.
- Batch writes implemented in `internal/queue/writer.go` (channels + periodic flush).
- Clicks table definition in `db/clickhouse/migrations/001_create_clicks.sql`.
- Conversions table definition in `db/clickhouse/migrations/002_create_conversions.sql`.
- Integration tests assert persisted click rows (`test/integration/click_test.go`).

## Valkey / Redis (Hot Path State)
- Client created in `internal/server/server.go` with `redis.NewClient`.
- Used by cache layer: `internal/cache/cache.go`.
- Used by session and uniqueness keys: `internal/session/session.go`.
- Used by rate limiting counters: `internal/ratelimit/ratelimit.go`.
- Used by hit-limit counters: `internal/hitlimit/hitlimit.go`.
- Used by bot IP and UA storage: `internal/botdb/valkey.go`, `internal/botdb/uastore.go`.
- Used by attribution token cache: `internal/attribution/service.go`.

## GeoIP Databases
- Resolver loads MaxMind DB files in `internal/geo/geo.go`.
- Configured paths from `config.yaml`:
- `data/geoip/GeoLite2-Country.mmdb`
- `data/geoip/GeoLite2-City.mmdb`
- `data/geoip/GeoLite2-ASN.mmdb`
- Output consumed in click build/update stages (e.g., `internal/pipeline/stage/3_build_raw_click.go`, `internal/pipeline/stage/6_update_raw_click.go`).

## HTTP API Integration Surface
- Public health endpoint: `/api/v1/health` in `internal/server/routes.go`.
- Authenticated admin API under `/api/v1/*` with `X-Api-Key` middleware (`internal/admin/middleware.go`).
- Click tracking endpoints:
- `GET /{alias}` (L1 pipeline)
- `GET /lp/{token}/click` (L2 pipeline)
- `GET /` (domain gateway path)

## Background Integration Points
- Cache warmup worker checks Valkey key `warmup:scheduled` and reloads from Postgres (`internal/worker/cache_warmup.go`).
- Hit-limit reset worker scans/deletes `hitlimit:*` keys (`internal/worker/hitlimit_reset.go`).

## Test/Tooling Integration Requirements
- Integration tests require running Postgres + Valkey + ClickHouse (`test/integration/*.go`).
- Benchmark test also depends on live infra and seeded campaign alias (`test/benchmark/latency_test.go`).

## Security/Auth Integration Notes
- API key auth validates directly against `users.api_key` in Postgres (`internal/admin/middleware.go`).
- Default admin seed user with known password appears in migration `db/postgres/migrations/004_create_domains_users.up.sql` (explicitly marked to change in production).
