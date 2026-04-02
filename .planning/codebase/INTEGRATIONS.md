# External Integrations

**Analysis Date:** 2026-04-02

## Data Storage

**Primary Database (PostgreSQL):**
- Driver: `jackc/pgx/v5` (`github.com/jackc/pgx/v5/pgxpool`)
- Connection: DSN via `DATABASE_URL` env var or `postgres.dsn` in config.yaml
- Purpose: Campaigns, streams, offers, landings (entity storage)
- Schema: Direct SQL queries in pipeline stages

**Analytics Database (ClickHouse):**
- Driver: `ClickHouse/clickhouse-go/v2` v2.44.0
- Connection: `CLICKHOUSE_URL` env var or `clickhouse.addr` in config.yaml
- Purpose: Click event storage for analytics/reporting
- Write Pattern: Async batch writer with 500ms/5000 record flush
- Table: `clicks` with ~32 columns (click metadata, geo, device, costs)

**Cache/Session Store (Valkey):**
- Driver: `redis/go-redis/v9` v9.18.0
- Connection: `VALKEY_URL` env var or `valkey.addr` in config.yaml
- Purpose: Hit limit counters, uniqueness sessions, cache warmup flags
- Key Patterns:
  - `hitlimit:*` - Daily click caps (deleted at midnight UTC)
  - `warmup:scheduled` - Cache warmup trigger flag
  - Session keys with TTL (Phase 2)

## IP Intelligence

**GeoIP (MaxMind GeoIP2):**
- Library: `oschwald/geoip2-golang` v1.13.0
- Databases: Country (.mmdb), City (.mmdb), ASN (.mmdb)
- Config paths: `geoip.country_db`, `geoip.city_db`, `geoip.asn_db` in config.yaml
- Data Returned: Country code, city name, ISP
- Graceful: Empty paths log warnings, do not fail startup

**User-Agent Parsing:**
- Library: `mileusna/useragent` v1.3.5
- Data Returned: Device type (desktop/mobile/tablet/bot), browser, browser version, OS, OS version
- Bot Detection: `Bot` flag from useragent library

## Authentication

**None detected** - This is a traffic distribution system (TDS), not a user-facing application with auth.

## Logging & Observability

**Logging Framework:**
- Library: `go.uber.org/zap` v1.27.1
- Levels: Debug (development), Production (JSON structured)
- Request logging: Chi middleware with method, path, status, bytes, IP

**Health Check Endpoint:**
- `GET /api/v1/health` - Returns `{"status": "ok", "version": "X.X.X"}`

## Background Workers

**Worker Manager:** Runs multiple goroutines managed by `worker.Manager`

| Worker | Purpose | Schedule |
|--------|---------|----------|
| `hitlimit-reset` | Reset daily click caps | Midnight UTC daily |
| `cache-warmup` | Detect cache warmup requests | Every 30 seconds |
| `session-janitor` | Expire old sessions | Every 1 hour |
| `click-writer` | Flush clicks to ClickHouse | Every 500ms or 5000 records |

## HTTP Server

**Framework:** Chi v5 (`github.com/go-chi/chi/v5`)

**Endpoints:**
| Route | Handler | Purpose |
|-------|---------|---------|
| `GET /api/v1/health` | `handleHealth` | Health check |
| `GET /{alias}` | `handleClick` | Click traffic processing |
| `GET /` | `handleClick` | Bare domain (gateway context) |

**Timeouts:**
- Read: 10s, Write: 15s, Idle: 60s

## Environment Configuration

**Required env vars (production):**
- `DATABASE_URL` - PostgreSQL connection string
- `VALKEY_URL` - Valkey connection address
- `CLICKHOUSE_URL` - ClickHouse connection address
- `SYSTEM_SALT` - Cryptographic salt (min 32 chars in production)

**Optional env vars:**
- `CONFIG_PATH` - Custom config.yaml location
- `DEBUG` - Enable debug mode ("true" or "1")
- `LOG_LEVEL` - Override log level

---

*Integration audit: 2026-04-02*
