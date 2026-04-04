# SkyPlix Deployment Guide

SkyPlix TDS uses a single-binary architecture with bundled React frontend, making it simple to deploy using Docker or natively on Linux.

## Prerequisites
- Linux Server (Ubuntu 22.04+ recommended)
- Docker & Docker Compose (v2)
- At least 2 CPU, 4GB RAM 
- Domains pointing to your server's IP

## Quick Start (Docker Compose)

The easiest way to run the full production stack:
```bash
# 1. Clone the repository
git clone https://github.com/chabhishek420/skyplix.git
cd skyplix

# 2. Modify config.yaml (set random secure salt!)
cp config.yaml.example config.yaml 
nano config.yaml

# 3. Start the stack
docker compose up -d

# 4. View logs
docker compose logs -f zai-tds
```
This automatically scales PostgreSQL, Valkey, ClickHouse, Prometheus, Grafana, and the SkyPlix engine.

## Production Checklist

Before sending live traffic, ensure:
1. **Salt Generation**: Ensure `system.salt` is randomly generated and at least 32 characters.
2. **PostgreSQL Tuning**: Run `pgtune` based on server RAM.
3. **SSL / TLS**: Put Nginx or Caddy in front of SkyPlix port 8080 to handle HTTPS.
4. **ClickHouse Migrations**: Execute the schema and index optimizations within `/db/clickhouse/`.
5. **GeoIP Bases**: Ensure `GeoLite2-Country.mmdb`, `GeoLite2-City.mmdb`, and `GeoLite2-ASN.mmdb` are stored in the GeoIP config path.

## Health Checks
Kubernetes/Docker liveness probes should point towards `/api/v1/health` (HTTP 200). 
Readiness probes (checking dependent infrastructure) should point towards `/api/v1/ready`.

## Metrics
Metrics are emitted automatically at `/metrics` based on Prometheus standards. See Grafana dashboards within `/deploy/grafana/` for visualization.
