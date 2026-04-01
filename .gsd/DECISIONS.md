# DECISIONS.md — Architecture Decision Records

## ADR-001: Go over TypeScript/Next.js for TDS Core
**Date**: 2026-04-01
**Status**: Accepted
**Context**: Existing implementation uses Next.js + Prisma + SQLite. Analysis revealed fundamental performance limitations: SQLite single-writer bottleneck, Prisma ORM overhead on hot path, Node.js single-threaded event loop blocking during CPU-bound bot detection, Next.js framework overhead adding 10-50ms per request.
**Decision**: Rewrite TDS core in Go. Keep Next.js reference code for admin UI patterns.
**Consequences**: Need Go expertise on team. Lose TypeScript type-safety ecosystem. Gain 10-100x performance on click processing. Single binary deployment simplifies ops.

## ADR-002: PostgreSQL + Redis + ClickHouse over SQLite
**Date**: 2026-04-01
**Status**: Accepted
**Context**: SQLite cannot handle concurrent writes from a high-throughput click server. A TDS needs three data access patterns: hot config reads (ms), relational state management, and analytical aggregation over billions of rows.
**Decision**: PostgreSQL for state, Redis for cache, ClickHouse for analytics.
**Consequences**: More complex deployment. Docker Compose for local dev. Worth it for correct data architecture.

## ADR-003: Single Binary Architecture
**Date**: 2026-04-01
**Status**: Accepted
**Context**: Keitaro deploys as PHP + Apache/Nginx + MariaDB + Redis. Deployment is complex. For an open-source tool, simplified deployment drives adoption.
**Decision**: Go binary embeds the React admin SPA via `//go:embed`. Binary serves all HTTP routes (click, API, admin UI).
**Consequences**: Go binary size ~30-50MB (includes embedded UI assets). Simpler deployment. Admin UI must be built before embedding.
