# Phase 5: Conversion Tracking & Analytics - Research

**Date:** 2026-04-03
**Phase:** 05-conversion-tracking-analytics
**Status:** Research complete, ready for planning

---

## 1. Current State Assessment

### What's Already Built (Plans 5.1 + 5.2)

| Component | File | Status |
|-----------|------|--------|
| Conversion model (`Conversion`, `AttributionData`) | `internal/model/conversion.go` | Complete |
| Attribution caching (Valkey, 24h TTL) | `internal/attribution/service.go` | Complete |
| Pipeline attribution save (Stage 23) | `internal/pipeline/stage/23_store_raw_clicks.go` | Complete |
| Multi-table queue writer (clicks + conversions) | `internal/queue/writer.go` | Complete |
| Postback handler (key validation, attribution lookup, async queue) | `internal/admin/handler/postback.go` | Complete |
| Postback routes (`GET/POST /postback/{key}`) | `internal/server/routes.go` | Complete |
| ClickHouse conversions schema expansion | `db/clickhouse/migrations/004_expand_conversions.sql` | Created (needs apply) |
| ClickHouse read client for fallback attribution | `internal/server/server.go` | Complete |

### What's Missing (Plan 5.3 + Context Decisions)

| Component | Status | Complexity |
|-----------|--------|------------|
| ClickHouse materialized views (hourly + daily SummingMergeTree) | Not started | High |
| Analytics/reporting service (`internal/analytics/`) | Not started | High |
| Reporting API endpoint (`/api/v1/reports`) | Not started | Medium |
| Postback URL template generation with macro substitution | Not started | Low |
| Integration tests for conversion flow | Not started | Medium |

---

## 2. ClickHouse Schema Analysis

### Existing Tables

**`clicks`** (via `003_optimize_clicks.sql` — daily partitioned MergeTree):
- Partition: `toYYYYMMDD(created_at)`
- Order: `(campaign_id, created_at)`
- Key columns for aggregation: `campaign_id`, `stream_id`, `offer_id`, `landing_id`, `country_code`, `device_type`, `os`, `browser`, `is_bot`, `is_unique_global`, `is_unique_campaign`, `is_unique_stream`
- Skip index on `click_token` for attribution lookups

**`conversions`** (via `002` + `004`):
- Order: `(created_at, click_token)`
- Columns: `id`, `created_at`, `click_token`, `campaign_id`, `stream_id`, `offer_id`, `landing_id`, `affiliate_network_id`, `source_id`, `country_code`, `status`, `payout`, `revenue`, `external_id`
- No partitioning defined (needs adding for production)

### Schema Gaps to Close

1. **Conversions table needs partitioning**: Add `PARTITION BY toYYYYMMDD(created_at)` for consistency and TTL pruning
2. **Conversions ORDER BY mismatch**: For reporting, `(campaign_id, created_at)` would be faster than `(created_at, click_token)` since most queries filter by campaign first. However, since the existing table is small and this is append-only, a separate MV handles aggregation
3. **No `source_id` or `affiliate_network_id` in clicks table**: Attribution data for these fields is only in the `conversions` table. Reporting that joins clicks with source/network must go through conversions or the MV dimensions

---

## 3. Materialized Views Design

### Decision: SummingMergeTree (Confirmed in 05-CONTEXT.md)

Based on user decisions and ClickHouse best practices:
- **Engine**: SummingMergeTree — auto-sums numeric columns during background merges
- **Trigger**: Insert-triggered materialized views (real-time, zero query-time overhead)
- **Granularity**: Both hourly and daily

### Critical ClickHouse Rule: Always Re-aggregate on Read

SummingMergeTree merges are asynchronous. Unmerged parts may have duplicate key rows. Queries MUST always use `GROUP BY` + `sum()`:

```sql
-- CORRECT
SELECT campaign_id, sum(clicks), sum(conversions) FROM stats_hourly GROUP BY campaign_id;

-- WRONG (may return partial/duplicate rows before background merge)
SELECT campaign_id, clicks, conversions FROM stats_hourly;
```

### Hourly-to-Daily Strategy Decision

Three options were researched:

| Approach | Pros | Cons | Recommendation |
|----------|------|------|----------------|
| **Two independent MVs from raw tables** | No dependency chain, each table optimal | Double write amplification | Good for high volume |
| **Query daily from hourly table** | Simple, single pipeline, less storage | 24x more rows for daily queries | Best for our cardinality |
| **Chained MVs (hourly -> daily)** | Best of both worlds | Fragile in ClickHouse; inner MV triggers add complexity | Avoid |

**Recommendation**: Start with **two independent MVs** from the raw `clicks` and `conversions` tables. The cardinality across dimensions (campaign x country x device x os x browser x hour) will be manageable for a single-operator TDS. The write amplification cost is negligible compared to the query simplicity for dashboards.

### Proposed MV Schema

#### Stats Hourly MV

**Target table**: `stats_hourly` (SummingMergeTree)

```sql
CREATE TABLE stats_hourly (
  hour           DateTime,
  campaign_id    UUID,
  stream_id      UUID,
  offer_id       UUID,
  landing_id     UUID,
  country_code   FixedString(2),
  device_type    LowCardinality(String),
  os             LowCardinality(String),
  browser        LowCardinality(String),
  -- Summable metrics
  clicks         UInt64,
  unique_clicks  UInt64,
  bots           UInt64,
  cost           Decimal(14, 4),
  click_payout   Decimal(14, 4)
) ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(hour)
ORDER BY (campaign_id, hour, stream_id, offer_id, landing_id, country_code, device_type, os, browser);
```

**MV from clicks**:
```sql
CREATE MATERIALIZED VIEW mv_stats_hourly_clicks TO stats_hourly AS
SELECT
  toStartOfHour(created_at) AS hour,
  campaign_id, stream_id, offer_id, landing_id,
  country_code, device_type, os, browser,
  count()                    AS clicks,
  sum(is_unique_global)      AS unique_clicks,
  sum(is_bot)                AS bots,
  sum(cost)                  AS cost,
  sum(payout)                AS click_payout
FROM clicks
GROUP BY hour, campaign_id, stream_id, offer_id, landing_id, country_code, device_type, os, browser;
```

#### Conversion Stats Hourly MV

**Target table**: `conv_stats_hourly` (SummingMergeTree)

```sql
CREATE TABLE conv_stats_hourly (
  hour               DateTime,
  campaign_id         UUID,
  stream_id           UUID,
  offer_id            UUID,
  country_code        FixedString(2),
  status              LowCardinality(String),
  -- Summable metrics
  conversions         UInt64,
  revenue             Decimal(14, 4),
  payout              Decimal(14, 4)
) ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(hour)
ORDER BY (campaign_id, hour, stream_id, offer_id, country_code, status);
```

**MV from conversions**:
```sql
CREATE MATERIALIZED VIEW mv_conv_stats_hourly TO conv_stats_hourly AS
SELECT
  toStartOfHour(created_at) AS hour,
  campaign_id, stream_id, offer_id,
  country_code, status,
  count()      AS conversions,
  sum(revenue) AS revenue,
  sum(payout)  AS payout
FROM conversions
GROUP BY hour, campaign_id, stream_id, offer_id, country_code, status;
```

#### Daily Stats Tables

Same structure but with `toStartOfDay(created_at)` and `PARTITION BY toYYYYMM(day)`. Populated by separate MVs reading from the same raw `clicks`/`conversions` tables.

### Cardinality Analysis

For a typical single-operator TDS:
- Campaigns: ~50-200
- Streams per campaign: ~3-5
- Offers: ~50-100
- Countries: ~100 active
- Device types: 3 (desktop, mobile, tablet)
- OS: ~10
- Browser: ~15

**Hourly cardinality estimate**: 200 x 5 x 100 x 100 x 3 x 10 x 15 = ~450M theoretical max, but in practice <<1% of combinations have traffic. Realistic: **10K-100K rows per hour** - very manageable for SummingMergeTree.

---

## 4. Reporting API Design

### User Decision: Single Flexible Endpoint

From 05-CONTEXT.md: Single endpoint `/api/v1/reports` with query parameters for dimensions, filters, date range, grouping.

### API Contract

```
GET /api/v1/reports?
  group_by=campaign,country      # Dimensions to group by
  date_from=2026-04-01           # Start date (inclusive)
  date_to=2026-04-03             # End date (inclusive)
  preset=today|yesterday|last_7d|last_30d|this_month  # Alternative to date_from/date_to
  campaign_id=uuid               # Filter: specific campaign
  country=US,GB                  # Filter: country codes
  device_type=mobile             # Filter: device type
  sort=clicks:desc               # Sort field:direction
  limit=50                       # Pagination limit
  offset=0                       # Pagination offset
```

### Response Shape (from context decisions)

```json
{
  "rows": [
    {
      "campaign_id": "uuid",
      "campaign_name": "Campaign A",
      "country": "US",
      "clicks": 1500,
      "unique_clicks": 1200,
      "conversions": 45,
      "cr": 3.75,
      "revenue": 225.00,
      "cost": 150.00,
      "profit": 75.00,
      "roi": 50.0,
      "epc": 0.15
    }
  ],
  "summary": {
    "clicks": 10000,
    "unique_clicks": 8500,
    "conversions": 350,
    "cr": 4.12,
    "revenue": 1750.00,
    "cost": 1000.00,
    "profit": 750.00,
    "roi": 75.0,
    "epc": 0.175
  },
  "meta": {
    "date_from": "2026-04-01",
    "date_to": "2026-04-03",
    "group_by": ["campaign", "country"],
    "total_rows": 120,
    "limit": 50,
    "offset": 0
  }
}
```

### Query Architecture

The reporting service must join click stats and conversion stats in Go (not ClickHouse JOIN). Rationale:
1. Click stats and conversion stats are in separate MVs with different sorting keys
2. ClickHouse JOINs are expensive and would negate MV benefits
3. Go-side merge is simple: query both tables with same GROUP BY/WHERE, merge by key in a map

**Pattern**:
```
1. Parse request params -> build ReportQuery struct
2. Build click stats SQL from stats_hourly/stats_daily (depending on date range granularity)
3. Build conversion stats SQL from conv_stats_hourly/conv_stats_daily
4. Execute both queries in parallel (goroutines)
5. Merge results in Go by grouping key
6. Calculate derived metrics (CR = conversions/unique_clicks*100, EPC = revenue/clicks, ROI = profit/cost*100)
7. Sort, paginate, build response
```

### Derived Metrics

| Metric | Formula | Notes |
|--------|---------|-------|
| CR (Conversion Rate) | `conversions / unique_clicks * 100` | Percentage |
| EPC (Earnings Per Click) | `revenue / clicks` | Revenue efficiency |
| CPC (Cost Per Click) | `cost / clicks` | Cost efficiency |
| Profit | `revenue - cost` | Net earnings |
| ROI | `profit / cost * 100` | Return on investment (%) |

### Date Range Optimization

- **Today/Yesterday**: Query from `stats_hourly` for hour-level freshness
- **Last 7d/30d/this_month**: Query from `stats_daily` for speed
- **Custom range**: If span <= 2 days use hourly, else daily
- **Time grouping**: `group_by=hour` always uses hourly; `group_by=day` always uses daily

### Groupable Dimensions

| Dimension Key | ClickHouse Column | Table |
|---------------|-------------------|-------|
| `campaign` | `campaign_id` | stats_hourly/daily |
| `stream` | `stream_id` | stats_hourly/daily |
| `offer` | `offer_id` | stats_hourly/daily |
| `landing` | `landing_id` | stats_hourly/daily |
| `country` | `country_code` | stats_hourly/daily |
| `device` | `device_type` | stats_hourly/daily (clicks only) |
| `os` | `os` | stats_hourly/daily (clicks only) |
| `browser` | `browser` | stats_hourly/daily (clicks only) |
| `day` | `toDate(hour)` / `day` | Time grouping |
| `hour` | `hour` | Time grouping |

**Note**: Device/OS/browser dimensions are only in click stats MVs. When these are used as group_by, conversion stats must be joined at the campaign+stream+offer+country level (without device detail), since conversions don't carry device info.

---

## 5. Postback URL Template Generation

### Decision: Keitaro-Compatible Macro Set

From 05-CONTEXT.md: `{click_id}`, `{subid}`, `{payout}`, `{status}`, `{external_id}`, `{campaign_id}`

### Existing Macro System

`internal/macro/macro.go` already has a `Replace()` function using `strings.ReplaceAll` with a replacement pairs slice. This can be extended with a new `ReplacePostback()` function for postback URL template rendering.

### Postback Macro Set

| Macro | Description | Source |
|-------|-------------|--------|
| `{click_id}` | Click token (sub_id value) | Click pipeline |
| `{subid}` | Alias for click_id | Click pipeline |
| `{payout}` | Conversion payout amount | Postback param |
| `{status}` | Conversion status (lead/sale/etc) | Postback param |
| `{external_id}` | External transaction ID | Postback param |
| `{campaign_id}` | Campaign UUID | Attribution data |
| `{offer_id}` | Offer UUID | Attribution data |
| `{sub_id_1}` through `{sub_id_5}` | Traffic source sub IDs | Click data |

### Template Storage

The postback URL template can be stored:
- **Per affiliate network**: `affiliate_networks.postback_url` (already exists in the model as `PostbackURL string`)
- **Per offer**: Can be added to offer model if needed
- **Global default**: Via settings

The existing `model.AffiliateNetwork.PostbackURL` field is the natural home. The admin UI/API can set the template with macros, and the system generates the actual URL when showing it to the user.

---

## 6. Integration Points & Wiring

### New Package: `internal/analytics/`

| File | Purpose |
|------|---------|
| `service.go` | ReportingService struct with ClickHouse reader, query builder logic |
| `models.go` | ReportQuery, ReportRow, ReportResponse, ReportSummary DTOs |
| `query_builder.go` | Dynamic SQL builder for flexible grouping/filtering |

### New Handler: `internal/admin/handler/reports.go`

Separate handler struct (like PostbackHandler) rather than adding to the main Handler, since it needs a ClickHouse reader connection instead of PostgreSQL:

```go
type ReportsHandler struct {
    logger    *zap.Logger
    analytics *analytics.Service
}
```

### Route Wiring (in `internal/server/routes.go`)

```go
// Inside /api/v1 protected group:
r.Get("/reports", s.reportsHandler.HandleReport)
```

### Server Wiring (in `internal/server/server.go`)

The analytics service needs:
- `driver.Conn` (ClickHouse reader) — already available as `s.chReader`
- `*zap.Logger`
- PostgreSQL pool for campaign/offer name resolution (optional, for enriching report rows with entity names)

---

## 7. Risk Analysis

### Technical Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| MV write amplification under high click volume | Storage growth, insert latency | Monitor `system.parts`, TTL on old data |
| Unmerged SummingMergeTree parts returning duplicates | Incorrect dashboard numbers | Always re-aggregate with `GROUP BY + sum()` |
| ClickHouse query timeout on large date ranges | 500 errors on reporting API | Query timeouts (5s default), pagination, date range limits |
| Device/OS/browser dimensions missing from conversion stats | Incomplete drilldowns | Document limitation; device drilldowns show click-only metrics for conversions |
| High cardinality dimension explosion | MV performance degradation | Use `LowCardinality(String)` for string dimensions |

### Implementation Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Dynamic SQL builder injection | Security vulnerability | Use parameterized queries for all user-supplied filter values; whitelist dimension/metric names |
| Report response too large | Memory exhaustion | Enforce max limit (1000 rows), offset pagination |
| Campaign name resolution N+1 | Slow reports | Batch-load campaign/offer names from PostgreSQL cache |

---

## 8. Dependency Map

```
Plan 5.3 depends on:
  [x] Plan 5.1 (Conversion models + attribution caching + multi-table writer)
  [x] Plan 5.2 (Postback handler + routes + server wiring)
  [ ] Migration 004 applied to ClickHouse (expand conversions)
  [ ] New migration 005: stats_hourly + stats_daily + MVs
  [ ] New migration 006: conv_stats_hourly + conv_stats_daily + MVs

Plan 5.4 (Postback URL Templates) depends on:
  [x] macro package (internal/macro/macro.go)
  [x] AffiliateNetwork model (PostbackURL field exists)
  [ ] Admin API for template CRUD (or settings-based)
```

---

## 9. Testing Strategy

### Unit Tests

| Test | What it validates |
|------|-------------------|
| `analytics/query_builder_test.go` | SQL generation for all grouping/filter combinations |
| `analytics/service_test.go` | Metric calculation (CR, EPC, ROI, profit) with mock data |
| `handler/reports_test.go` | Query param parsing, validation, error responses |

### Integration Tests

| Test | What it validates |
|------|-------------------|
| Postback E2E | Click -> postback -> verify conversion in ClickHouse |
| MV population | Insert clicks/conversions -> verify MV target tables populated |
| Reports E2E | Seed data -> query reports API -> verify aggregated metrics match |

### Verification Checklist

- [ ] ClickHouse migration 004 applied (conversions schema expanded)
- [ ] ClickHouse migration 005 applied (stats MVs created)
- [ ] Send test postback and verify conversion row in ClickHouse
- [ ] Insert test clicks and verify stats_hourly MV populated
- [ ] Insert test conversions and verify conv_stats_hourly MV populated
- [ ] Query `/api/v1/reports?group_by=campaign` returns correct aggregations
- [ ] Derived metrics (CR, EPC, ROI) calculated correctly
- [ ] Multi-level grouping works (`group_by=campaign,country`)
- [ ] Date range presets work (today, yesterday, last_7d)
- [ ] Pagination works (limit/offset)
- [ ] `go test ./...` passes with no regressions
- [ ] p99 latency still <5ms for click pipeline (MVs don't affect click hot path)

---

## 10. Recommended Plan Structure

### Plan 5.3: ClickHouse Materialized Views & Stats Tables
- **Scope**: DDL migrations only (no Go code)
- **Output**: `005_create_stats_materialized_views.sql`
- Tables: `stats_hourly`, `stats_daily`, `conv_stats_hourly`, `conv_stats_daily`
- MVs: `mv_stats_hourly_clicks`, `mv_stats_daily_clicks`, `mv_conv_stats_hourly`, `mv_conv_stats_daily`
- Add partitioning to conversions table if missing

### Plan 5.4: Analytics Reporting Service
- **Scope**: `internal/analytics/` package
- **Output**: `service.go`, `models.go`, `query_builder.go`
- Query builder with whitelist-validated dimensions/filters
- Go-side merge of click stats + conversion stats
- Derived metric calculation
- Pagination and sorting

### Plan 5.5: Reporting API Endpoint
- **Scope**: Handler, route wiring, server wiring
- **Output**: `internal/admin/handler/reports.go`, route updates
- Single `/api/v1/reports` endpoint with flexible query params
- Response shape: rows + summary + metadata
- Error handling and validation

### Plan 5.6: Postback URL Template Generation
- **Scope**: Macro extension, admin API enhancement
- **Output**: Template rendering in `internal/macro/`, settings or per-network storage
- Keitaro-compatible macro set
- Admin API endpoint to preview/generate postback URLs

---

## 11. Key References

- **ClickHouse SummingMergeTree**: [Official Docs](https://clickhouse.com/docs/en/engines/table-engines/mergetree-family/summingmergetree) - Always re-aggregate on read, use `LowCardinality` for string dimensions
- **Keitaro API Reference**: [Admin API](https://admin-api.docs.keitaro.io) - Report builder API shape (`POST /admin_api/v1/report/build`)
- **Keitaro Documentation**: [Docs](https://docs.keitaro.io) - Postback URL templates, conversion tracking setup
- **Existing codebase patterns**: Admin handler struct (DI via constructor), Chi routing, `respondJSON`/`respondError` helpers, ClickHouse writer connection reuse

---

*Research completed: 2026-04-03*
*Ready for: `/gsd:plan-phase` to generate detailed execution plans*
