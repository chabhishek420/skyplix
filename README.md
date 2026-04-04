# SkyPlix TDS

SkyPlix is a high-performance, open-source replacement for the Keitaro Traffic Distribution System (TDS). Built in Go, it is designed to process millions of clicks with sub-5ms latency while providing advanced cloaking, bot detection, and conversion tracking.

## Core Features

- **High-Performance Pipeline**: L1 (Campaign) and L2 (Landing) processing pipelines designed for speed.
- **Advanced Cloaking & Bot Detection**: Multi-layer detection using User-Agent signatures, IP/CIDR blocklists, ASN/datacenter checks, and behavior analysis.
- **Dynamic Campaign Routing**: 3-tier stream selection (FORCED → REGULAR → DEFAULT) with advanced filtering and rotation.
- **Conversion Tracking**: S2S postback support with robust attribution caching (Valkey) and persistent storage (ClickHouse).
- **Real-time Analytics**: Aggregated stats (hourly/daily) via ClickHouse SummingMergeTree materialized views.
- **Admin Dashboard**: Modern React-based UI for campaign management and reporting.
- **Prometheus Metrics**: Built-in observability with 12+ metric families for monitoring throughput, latency, and health.

## Tech Stack

- **Language**: Go 1.25+
- **Database**: PostgreSQL 16 (Configuration), ClickHouse 24 (Analytics)
- **Cache**: Valkey 8 (Hot-path data & Attribution)
- **Frontend**: React 19, Vite, shadcn/ui

## Production Quick Start

### 1. Prerequisites
- Docker and Docker Compose
- MaxMind GeoIP2 Databases (`GeoLite2-Country.mmdb`, `GeoLite2-City.mmdb`, `GeoLite2-ASN.mmdb`)

### 2. Configuration
Copy the example configuration and update it with your production settings:
```bash
cp config.yaml.example config.yaml
# Edit config.yaml with your secrets and database credentials
```

### 3. Deploy
The included `docker-compose.yml` is production-ready and handles database initialization, ClickHouse migrations, and the TDS application.

```bash
docker compose up -d
```

### 4. Verification
The TDS will be available at `http://localhost:8080`.
- **Admin UI**: `http://localhost:8080/admin`
- **Health Check**: `http://localhost:8080/api/v1/health`
- **Metrics**: `http://localhost:8080/metrics`

## Documentation

- [Architecture](./docs/ARCHITECTURE.md)
- [GSD Workflow](./.gsd/PROJECT.md)
- [API Specification](./docs/API.md)

## License
Open-source under the MIT License.
