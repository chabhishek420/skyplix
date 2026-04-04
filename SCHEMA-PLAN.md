# SCHEMA-PLAN.md

This document outlines the required schema changes to achieve full Keitaro parity and support Yljary Scale features.

## 1. PostgreSQL (Configuration State)

### 1.1 Multi-Workspace Support
Currently, SkyPlix is single-tenant. To support teams and multi-user environments:
- **New Table**: `workspaces` (id, name, owner_id, state, created_at)
- **Modifications**: Add `workspace_id` (UUID) to:
    - `campaigns`
    - `offers`
    - `landings`
    - `domains`
    - `traffic_sources`
    - `affiliate_networks`
    - `users` (as many-to-many via `user_workspaces`)

### 1.2 Campaign Enhancements
- **Fields**:
    - `group_id`: Support for campaign grouping.
    - `cost_model`: `CPC`, `CPM`, `CPA`, `RevShare`.
    - `cost_value`: Default cost per click/event.
    - `notes`: Text field for operator metadata.
    - `tags`: JSONB array of strings.
- **New Table**: `campaign_groups` (id, name, workspace_id).

### 1.3 Offer & Landing Enhancements
- **Fields**:
    - `group_id`: Grouping support.
    - `notes`: Metadata.
    - `daily_cap`: Limit per offer.

### 1.4 RBAC Enforcement
- **Modifications**:
    - `users` table: Ensure `role` is one of `owner`, `admin`, `manager`, `viewer`.
    - Add `permissions` (JSONB) for granular overrides.

---

## 2. ClickHouse (Analytics & Events)

### 2.1 Click Events (`clicks`)
SkyPlix already has a robust `clicks` table. Enhancements:
- **Field**: `workspace_id` (UUID) — for partitioned analytics.
- **Field**: `tls_fingerprint` (String) — to store JA3/JA4 results.
- **Field**: `behavior_score` (UInt8) — calculated risk score.
- **Field**: `request_id` (String) — for tracing across systems.

### 2.2 Conversion Events (`conversions`)
- **Field**: `conversion_type` (LowCardinality(String)) — lead, sale, upsell, etc.
- **Field**: `click_id` (String) — Reference to the original click UUID/token.
- **Field**: `visitor_code` (String) — For cross-click attribution.

### 2.3 Materialized Views
- **Update**: `stats_hourly` and `stats_daily` to include `workspace_id`.
- **New View**: `stats_by_os_browser` — optimized for drilldowns without full table scans.
- **New View**: `stats_fraud_report` — aggregating `is_bot` and `bot_reason` over time.

---

## 3. Valkey (Hot-Path Caching)

### 3.1 Session Persistence
- **Prefix**: `sess:{click_token}`
- **TTL**: 24-48 hours.
- **Data**: Full `RawClick` snapshot for Level 2 (L2) clicks.

### 3.2 Uniqueness Hardening
- **Global Uniqueness**: `uniq:g:{ip}` (24h).
- **Campaign Uniqueness**: `uniq:c:{campaign_id}:{ip}` (24h).
- **Stream Uniqueness**: `uniq:s:{stream_id}:{ip}` (24h).

### 3.3 Rate Limiting
- **IP Velocity**: `rl:ip:{ip}:{window}`.
- **Campaign Velocity**: `rl:camp:{campaign_id}:{window}`.

---

## Implementation Roadmap

1. **Migration 007 (PG)**: Multi-workspace core (`workspaces`, `user_workspaces`).
2. **Migration 008 (PG)**: Workspace scoping for all major entities.
3. **Migration 009 (PG)**: Campaign metadata (groups, notes, cost models).
4. **Migration 009 (CH)**: Add `workspace_id` and `tls_fingerprint`.
5. **Migration 010 (CH)**: Update materialized views to support scoping.
