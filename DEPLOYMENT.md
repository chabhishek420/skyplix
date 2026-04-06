# SkyPlix TDS — Deployment Guide

SkyPlix is a high-performance tracking system designed to run as a single Go binary. All state is maintained in external databases (PostgreSQL, Valkey, and ClickHouse).

## Prerequisites

- **PostgreSQL 16+**: For persistent campaign data.
- **Valkey 7.x+**: For hot-path caching and sessions.
- **ClickHouse 24.x+**: For click and conversion analytics.
- **MaxMind GeoIP DBs**: (Optional but recommended) `GeoLite2-Country.mmdb`, `GeoLite2-City.mmdb`, `GeoLite2-ASN.mmdb`.

## Configuration

Configuration is managed via `config.yaml` or environment variables (prefixed with `SKYPLIX_`).

```yaml
system:
  listen_addr: "0.0.0.0"
  port: 8080
  debug: false

postgres:
  dsn: "postgres://user:pass@localhost:5432/skyplix?sslmode=disable"

valkey:
  addr: "localhost:6379"

clickhouse:
  addr: "localhost:9000"
  database: "skyplix"
```

## Running with Docker

Use the provided `docker-compose.yml` for a production-ready stack.

```bash
docker-compose up -d
```

## Running on Bare Metal

1. Build the binary:
   ```bash
   go build -o skyplix cmd/zai-tds/main.go
   ```
2. Run migrations:
   ```bash
   ./skyplix migrate up
   ```
3. Start the server:
   ```bash
   ./skyplix serve
   ```

## Production Hardening

- **Horizontal Scaling**: SkyPlix is stateless. Run multiple instances behind a load balancer (Nginx/HAProxy/Envoy).
- **Monitoring**: Scrape `/metrics` with Prometheus. Monitor `skyplix_clicks_total` and `skyplix_pipeline_duration_seconds`.
- **Security**: Always run behind TLS. Ensure `X-API-Key` is rotated regularly.
