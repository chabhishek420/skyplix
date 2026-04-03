# Phase 5: Conversion Tracking & Analytics - Context

**Gathered:** 2026-04-03
**Status:** Ready for planning

<domain>
## Phase Boundary

Postback (S2S) conversion tracking, real-time stats aggregation (daily/hourly materialized views in ClickHouse), reporting API with drilldowns by campaign/geo/device/source/time. Full click→conversion attribution and live revenue dashboards.

Phase 5 closes the tracking loop: clicks are already recorded in ClickHouse — this phase links conversions back to clicks and makes the data queryable.

</domain>

<decisions>
## Implementation Decisions

### Stats Aggregation
- Pre-aggregate at both **hourly and daily** granularity via ClickHouse materialized views
- Use **SummingMergeTree** engine with insert-triggered materialized views (real-time, zero query-time overhead)
- Pre-aggregate across all key dimensions: campaign/stream/offer, country (geo), device/OS/browser, traffic source
- Full metrics suite per aggregation row: clicks, unique clicks, conversions, CR%, revenue, cost, profit, ROI

### Reporting API Surface
- **Single flexible endpoint** (`/api/v1/reports`) with query params for dimensions, filters, date range, grouping
- Support **multi-level grouping**: e.g., `group_by=campaign,country` shows campaigns broken down by country
- Time range filtering via **presets + custom range**: presets (today, yesterday, last_7d, last_30d, this_month) plus custom `date_from`/`date_to`
- Response shape: **rows array + summary totals + metadata** (time range, grouping used, row count) — ready for table rendering

### Attribution & Dedup
- Keep **24h Valkey TTL** as fast-path attribution cache. ClickHouse fallback handles anything older. No per-campaign configurability needed
- Allow **multiple conversions per click** (multi-event tracking: lead, then sale, then upsell)
- **Append-only rows** — each postback creates a new conversion row. Status is immutable per row, history preserved naturally. No ClickHouse UPDATE needed

### Postback Flexibility
- **Global key only** for postback authentication (already implemented). Per-campaign keys deferred
- Generate **postback URL templates with macro substitution** for affiliate networks
- Use **Keitaro-compatible macro set**: `{click_id}`, `{subid}`, `{payout}`, `{status}`, `{external_id}`, `{campaign_id}` — covers common affiliate network needs

### Claude's Discretion
- Exact materialized view DDL and column selection
- Report response pagination approach (offset vs cursor)
- Sorting defaults and available sort fields
- Error response shapes for reporting API
- Postback URL template storage mechanism
- Hourly-to-daily rollup strategy (separate MV or query from hourly)

</decisions>

<specifics>
## Specific Ideas

- Keitaro-style reporting behavior is the reference — media buyers expect campaign/geo/device drilldowns with revenue/profit/ROI
- SummingMergeTree is the ClickHouse standard for analytics aggregation — no batch jobs
- Postback URL templates should feel like Keitaro's postback URL builder (paste into affiliate network, macros auto-replaced)
- Append-only conversions aligns with ClickHouse's immutable insert model — no ReplacingMergeTree complexity

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `internal/attribution/service.go`: Complete Valkey-based attribution (get/save with 24h TTL) — already wired
- `internal/admin/handler/postback.go`: Full postback handler with key validation, multi-param token lookup, CH fallback, async queue — already wired
- `internal/queue/writer.go`: Multi-table batch writer (clicks + conversions via ConversionRecord) — already wired
- `internal/model/conversion.go`: Conversion + AttributionData structs — already defined
- `internal/macro/macro.go`: Existing macro substitution logic — can be extended for postback URL templates
- `internal/pipeline/stage/23_store_raw_clicks.go`: Attribution caching in pipeline — already saves click metadata to Valkey

### Established Patterns
- Admin API pattern: Chi router + handler struct with injected dependencies (see `internal/admin/handler/*.go`)
- ClickHouse writes: async batching via channels → `queue.Writer` goroutine flush (500ms/5000 batch thresholds)
- Settings lookup: `repository.SettingsRepository` with cached reads (see postback key cache pattern)
- Context propagation: `context.Context` first param, timeouts on external calls

### Integration Points
- Reporting API routes: wire into `internal/server/routes.go` under `/api/v1/reports` (admin-auth protected)
- ClickHouse connection: `driver.Conn` already available in server.go and passed to handlers
- Materialized views: DDL migrations in `db/clickhouse/migrations/` (next is 005+)
- Postback URL template: settings or per-offer config in PostgreSQL, rendered via `internal/macro/`

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 05-conversion-tracking-analytics*
*Context gathered: 2026-04-03*
