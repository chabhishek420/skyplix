# ARCH-DECISIONS.md

This document records the core architectural decisions for SkyPlix TDS, including justifications for deviations from the Keitaro PHP reference.

## 1. Click Hot Path: Pipeline Architecture
**Decision**: Use a linear pipeline of `Stage` implementations instead of a nested class hierarchy.
**Justification**:
- **Performance**: Minimizes allocation on the hot path (p99 < 5ms).
- **Maintainability**: New filters, actions, or bot detection logic can be added as discrete stages without modifying core routing logic.
- **Observability**: Each stage can be individually timed and monitored via Prometheus.
- **Keitaro Deviation**: Keitaro uses a traditional MVC/Dispatcher pattern. SkyPlix adopts a stream-processing model for better performance and Go idiomaticity.

## 2. Analytics: ClickHouse-First Storage
**Decision**: Use ClickHouse for all click and conversion event storage, with asynchronous batch writing.
**Justification**:
- **Scale**: Handles 100M+ events per day with efficient columnar storage.
- **Real-time**: Materialized views (`stats_hourly`, `stats_daily`) provide sub-second query performance for the Admin UI.
- **Durability**: Using a buffered channel + batch writer ensures that ClickHouse ingestion does not block the click hot path.
- **Keitaro Deviation**: Keitaro uses MySQL/MariaDB for both config and analytics, which often becomes a bottleneck under high traffic.

## 3. Configuration State: PostgreSQL + Valkey Cache
**Decision**: PostgreSQL 16 for source-of-truth configuration, with Valkey 7 for high-performance L1 caching.
**Justification**:
- **Consistency**: Relational integrity for campaigns, streams, and offers.
- **Performance**: Campaigns and streams are cached in Valkey. Routing decisions are made without hitting PostgreSQL.
- **Invalidation**: Administrative mutations trigger Valkey cache invalidation or warm-up.

## 4. Click ID Format (Yljary Scale)
**Decision**: `[8 hex timestamp][16 hex random]` (24 characters).
**Justification**:
- **Transparency**: Encodes the creation time for easy debugging without DB lookups.
- **Uniqueness**: 16 hex random bytes ensure virtually zero collisions at 50k RPS.
- **Attribution**: The token is used as the primary key for S2S postback matching.

## 5. Security: Secure Postback Handling
**Decision**: `/postback/{key}` where `key` is a system-wide or per-workspace secret stored in settings.
**Justification**:
- **Simplicity**: Easy integration with affiliate networks.
- **Security**: Prevents unauthorized conversion injection by requiring a secret key.
- **Robustness**: Supports HMAC-SHA256 validation if required in Phase 5 (Roadmap).

## 6. Multi-Armed Bandit (MAB) Implementation
**Decision**: Background worker-based weight recalculation (Roadmap Phase 8).
**Justification**:
- **Hot Path Safety**: Avoids expensive calculations during the click request.
- **Consistency**: Uses ClickHouse metrics (CR/ROI) to adjust stream and offer weights periodically (e.g., every 5 minutes).

## 7. Next-Gen Bot Detection: JA3/JA4 TLS Fingerprinting
**Decision**: Capture TLS ClientHello fingerprints at the router/middleware level (Roadmap Phase 12).
**Justification**:
- **Evasion Resistance**: Scrapers and bots often use libraries (like Python `requests` or `curl`) with distinct TLS signatures that are hard to spoof compared to User-Agents.
- **Integration**: Results are stored in ClickHouse and used as a filtering dimension in the `BotDetection` stage.
