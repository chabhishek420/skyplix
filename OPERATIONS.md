# SkyPlix Operations & Tuning Guide

## Infrastructure Architecture
SkyPlix TDS is designed for horizontal scalability. All request-time logic is stateless, relying on the following external services:
- **PostgreSQL:** Configuration and metadata persistence.
- **Valkey (Redis):** Hot-path caching, uniqueness sessions, and attribution state.
- **ClickHouse:** Analytics storage and reporting.

## Performance Tuning
To achieve sub-5ms p99 latency at 50k+ RPS, the following optimizations are implemented:
- **sync.Pool:** Request and Payload objects are pooled to minimize GC pressure.
- **Zero-Allocation Parsing:** Query parameters are extracted via a manual scanner to avoid map allocations.
- **Async Writing:** Click and conversion data is buffered and flushed to ClickHouse in large batches.

## Monitoring
Metrics are exposed via the `/metrics` endpoint in Prometheus format. Key indicators:
- `skyplix_pipeline_duration_seconds`: E2E click processing time.
- `skyplix_clicks_total`: Total request volume.
- `skyplix_clickhouse_channel_depth`: Buffer health for analytics writes.

## Security
- **Admin API:** Secured via JWT or X-API-Key.
- **Postbacks:** HMAC-SHA256 signature validation is recommended for production.
- **Cloaking:** Multiple layers of bot detection are enabled by default.

## Maintenance
- **Backups:** Perform periodic snapshots of PostgreSQL and ClickHouse `data/` directories.
- **Updates:** Rebuild the Docker image and apply migrations using the `migrate-ch` utility.
