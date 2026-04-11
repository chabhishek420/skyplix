# Project: zai-tds (High-Performance TDS)

## Purpose
A high-performance Go-based replacement for **Keitaro TDS**, optimized for high-volume operations (10k+ RPS) and advanced cloaking/bot detection.

## What This Is
A mission-critical traffic distribution system that routes incoming web traffic through configurable campaigns, streams, landers, and offers with advanced bot detection and real-time analytics.

## Core Value
Provides ultra-low latency click routing (p99 < 5ms target) with ClickHouse-backed analytics, replacing PHP-based Keitaro with a modern Go implementation.

## Mission
To provide a mission-critical traffic distribution and analytics system that outperforms PHP-based alternatives while maintaining feature compatibility with Keitaro's campaign/stream/lander/offer architecture.

## Primary Targets
- **Performance:** ⚠️ **UNVERIFIED** - 10k+ requests per second (RPS) on a single instance (goal, not yet benchmarked).
- **Latency:** ⚠️ **UNVERIFIED** - Sub-10ms average processing time (goal, not yet benchmarked).
- **Security:** Advanced bot detection (JA3/TLS fingerprinting - planned, behavioral analysis - partial), and hardened click tracking (CSPRNG IDs - ✅ implemented).
- **Scale:** Real-time analytics with ClickHouse, handling billions of rows.

## Requirements
See [REQUIREMENTS.md](./REQUIREMENTS.md) for complete requirement list:
- **FEAT**: Campaign/stream architecture, filtering, rotation, macros
- **PERF**: Throughput, latency, resource efficiency, HA
- **SEC**: CSPRNG IDs, JA3 fingerprinting, cloaking, behavioral detection
- **DATA**: Analytics, postback, attribution, uniqueness tracking
- **MGMT**: Admin API, metadata storage, dashboard UI

## References
- **Keitaro PHP Source (`reference/Keitaro_source_php`):** Feature parity and logic reference.
- **yljary Investigation (`reference/yljary-investigation`):** Scale, security requirements, and fraud prevention patterns.

## Tech Stack
- **Runtime:** Go 1.25+
- **Database (OLTP):** PostgreSQL (Admin config, metadata)
- **Database (OLAP):** ClickHouse (Analytics, clicks, conversions)
- **Caching/State:** Valkey/Redis (Hot-path resolution, hit limits, uniqueness)
- **Messaging:** Internal Go channels + Valkey (Async processing)
- **Logging:** Uber-zap (Structured, high-performance)

## Project Structure
```
zai-tds/
├── cmd/zai-tds/          # Entry point
├── internal/              # Business logic
│   ├── action/           # Action handlers (redirect, proxy, content)
│   ├── admin/            # Admin API handlers
│   ├── analytics/        # ClickHouse analytics
│   ├── attribution/      # Conversion attribution
│   ├── botdb/            # Bot detection database
│   ├── filter/           # Traffic filters (geo, device, network)
│   ├── macro/            # Macro expansion (40+ macros)
│   ├── metrics/          # Prometheus metrics
│   ├── model/            # Data models
│   ├── pipeline/         # Click processing pipeline
│   ├── queue/            # Async click queue
│   ├── worker/           # Background workers
│   └── ...
├── admin-ui/             # React dashboard
├── db/                   # Database schemas
└── deploy/               # Production deployment
```

## Verified Metrics
| Metric | Target | Status |
|--------|--------|--------|
| Pipeline Stages (L1) | 28 | ✅ Verified |
| Pipeline Stages (L2) | 14 | ✅ Verified |
| Filters | 11+ | ✅ Verified |
| Action Types | 4 | ✅ Verified |
| Macro Count | 40+ | ✅ Verified |
| Admin API Endpoints | 15+ | ✅ Verified |
| UI Pages | 12+ | ✅ Verified |

## CI/CD Status
- **GitHub Actions:** ❌ NOT IMPLEMENTED
- **Docker Compose:** ✅ Available
- **Systemd Service:** ✅ Available
- **Prometheus Metrics:** ✅ Available
