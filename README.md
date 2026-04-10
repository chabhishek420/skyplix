# SkyPlix TDS v1.0 — High-Performance Traffic Distribution System

SkyPlix TDS is a modern, Go-based replacement for the Keitaro TDS, engineered for ultra-low latency traffic routing and enterprise-grade analytics.

## Key Features

- **Ultra-Low Latency:** p99 < 5ms under load (50,000+ RPS).
- **Architectural Parity:** 23-stage Level 1 and 13-stage Level 2 processing pipelines matching Keitaro's behavior.
- **Advanced Cloaking:** Integrated bot detection (IP, UA, ASN, Proxy, CIDR) with behavioral scoring.
- **Enterprise Analytics:** ClickHouse-backed reporting with real-time materialized views.
- **Robust Attribution:** Secure S2S Postback and client-side Pixel tracking with HMAC-SHA256 validation.
- **Modern Admin UI:** Feature-rich React 19 SPA for managing campaigns, streams, and drilldown reports.
- **Production Ready:** Multi-stage hardened Docker builds, systemd units, and Prometheus metrics.

## Quick Start

### Prerequisites
- Docker & Docker Compose
- Go 1.25 (for local development)

### Deployment
1. Clone the repository.
2. Initialize the environment:
   ```bash
   docker-compose up -d
   ```
3. Run ClickHouse migrations:
   ```bash
   go run cmd/migrate-ch/main.go
   ```
4. Access the Admin UI at `http://localhost:8080/admin`.

## Documentation
- [Architecture](.gsd/ARCHITECTURE.md)
- [Operations & Tuning](OPERATIONS.md)
- [Feature Map](FEATURE-MAP.md)

## Development Workflow
SkyPlix follows the **GSD (Get Shit Done)** workflow. All planning artifacts and journals are located in the `.gsd/` directory.

---
**Version:** 1.0.0
**License:** MIT
