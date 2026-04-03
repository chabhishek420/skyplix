---
phase: 5
plan: 1
wave: 1
depends_on: []
files_modified:
  - db/clickhouse/migrations/005_create_stats_materialized_views.sql
autonomous: true
requirements:
  - ClickHouse materialized views
  - stats aggregation
must_haves:
  truths:
    - "SummingMergeTree target tables exist for click stats (hourly + daily) and conversion stats (hourly + daily)."
    - "Insert-triggered materialized views automatically populate stats tables from raw clicks and conversions inserts."
    - "Conversions table has daily partitioning added for consistency with clicks table."
    - "All MV target tables use LowCardinality(String) for string dimension columns."
    - "Click stats MVs aggregate: clicks, unique_clicks, bots, cost, click_payout."
    - "Conversion stats MVs aggregate: conversions, revenue, payout."
  artifacts:
    - "db/clickhouse/migrations/005_create_stats_materialized_views.sql"
---

# Plan 5.1: ClickHouse Materialized Views & Stats Tables

<objective>
Create ClickHouse SummingMergeTree target tables and insert-triggered materialized views for real-time stats aggregation. This is DDL-only (no Go code) and forms the foundation for the reporting API.

Two independent MV pairs read from the raw `clicks` and `conversions` tables:
- Click stats: `stats_hourly` + `stats_daily` (dimensions: campaign, stream, offer, landing, country, device, os, browser)
- Conversion stats: `conv_stats_hourly` + `conv_stats_daily` (dimensions: campaign, stream, offer, country, status)

Device/OS/browser dimensions are click-only since conversions don't carry device data.

Output:
- Single migration file `005_create_stats_materialized_views.sql` with all 8 DDL statements.
- Conversions table gains daily partitioning via an ALTER.
</objective>

<context>
Load for context:
- db/clickhouse/migrations/003_optimize_clicks.sql (clicks schema — column names, types, partition/order)
- db/clickhouse/migrations/002_create_conversions.sql (conversions base schema)
- db/clickhouse/migrations/004_expand_conversions.sql (conversions expansion — new columns)
- .planning/phases/05-conversion-tracking-analytics/05-RESEARCH.md (MV schema design, cardinality analysis)
</context>

<tasks>

<task type="auto">
  <name>Create Migration 005 — Stats Tables and Materialized Views</name>
  <files>db/clickhouse/migrations/005_create_stats_materialized_views.sql</files>
  <action>
    Create a single migration file with the following DDL statements in order:

    **1. Add partitioning to conversions table** (if not already partitioned):
    Since ClickHouse doesn't allow ALTER PARTITION, use a comment noting this was addressed during
    table creation or requires a shadow table swap for existing data. For new deploys, the conversions
    table from 002+004 already has no partitioning — add a note that fresh installs should use the
    partitioned version. For migration safety, skip the partition ALTER (ClickHouse limitation) and
    document the gap.

    **2. Click stats hourly target table** (`stats_hourly`):
    - Engine: SummingMergeTree()
    - Partition: toYYYYMM(hour)
    - Order: (campaign_id, hour, stream_id, offer_id, landing_id, country_code, device_type, os, browser)
    - Columns: hour (DateTime), campaign_id (UUID), stream_id (UUID), offer_id (UUID), landing_id (UUID),
      country_code (FixedString(2)), device_type (LowCardinality(String)), os (LowCardinality(String)),
      browser (LowCardinality(String)), clicks (UInt64), unique_clicks (UInt64), bots (UInt64),
      cost (Decimal(14,4)), click_payout (Decimal(14,4))

    **3. MV from clicks -> stats_hourly** (`mv_stats_hourly_clicks`):
    - SELECT toStartOfHour(created_at) AS hour, dimension columns, count() AS clicks,
      sum(is_unique_global) AS unique_clicks, sum(is_bot) AS bots, sum(cost) AS cost,
      sum(payout) AS click_payout
    - GROUP BY all dimension columns

    **4. Click stats daily target table** (`stats_daily`):
    - Same structure as stats_hourly but with `day Date` instead of `hour DateTime`
    - Partition: toYYYYMM(day)
    - Order: same pattern but with day

    **5. MV from clicks -> stats_daily** (`mv_stats_daily_clicks`):
    - SELECT toDate(created_at) AS day, same aggregation as hourly

    **6. Conversion stats hourly target table** (`conv_stats_hourly`):
    - Engine: SummingMergeTree()
    - Partition: toYYYYMM(hour)
    - Order: (campaign_id, hour, stream_id, offer_id, country_code, status)
    - Columns: hour (DateTime), campaign_id (UUID), stream_id (UUID), offer_id (UUID),
      country_code (FixedString(2)), status (LowCardinality(String)),
      conversions (UInt64), revenue (Decimal(14,4)), payout (Decimal(14,4))
    - NOTE: No landing_id, device_type, os, browser — conversions lack these dimensions

    **7. MV from conversions -> conv_stats_hourly** (`mv_conv_stats_hourly`):
    - SELECT toStartOfHour(created_at) AS hour, campaign_id, stream_id, offer_id,
      country_code, status, count() AS conversions, sum(revenue) AS revenue,
      sum(payout) AS payout
    - GROUP BY all dimension columns

    **8. Conversion stats daily target table** (`conv_stats_daily`):
    - Same as conv_stats_hourly but with `day Date`

    **9. MV from conversions -> conv_stats_daily** (`mv_conv_stats_daily`):
    - SELECT toDate(created_at) AS day, same aggregation

    CRITICAL RULES:
    - Use `CREATE TABLE IF NOT EXISTS` and `CREATE MATERIALIZED VIEW IF NOT EXISTS` for idempotency.
    - Use `LowCardinality(String)` for all variable-length string dimension columns.
    - Use `FixedString(2)` for country_code (matching source tables).
    - Use `Decimal(14,4)` for monetary columns (wider than source's 10,4 to handle aggregation overflow).
    - Use `TO` clause on MVs to write to the target tables.
    - Include clear section comments separating each DDL block.

    AVOID:
    - Chained MVs (hourly -> daily). Both read from raw tables independently.
    - Using `PARTITION BY toYYYYMMDD` for MVs (monthly is sufficient for aggregated data).
    - Adding columns not present in the source tables.
  </action>
  <verify>
    Review the SQL for correctness:
    - Column types match source table types (UUID, FixedString(2), UInt8 for booleans).
    - ORDER BY columns exactly match GROUP BY columns in the MV SELECT.
    - All MV SELECTs use proper aggregation functions (count, sum).
    - No ClickHouse syntax errors (validate against ClickHouse 24 DDL spec).
  </verify>
  <done>Migration file creates all 4 target tables and 4 materialized views. DDL is idempotent.</done>
</task>

</tasks>

<verification>
After all tasks, verify:
- [ ] Migration file `005_create_stats_materialized_views.sql` exists and contains 8 DDL statements.
- [ ] All `CREATE TABLE` use `IF NOT EXISTS` and `SummingMergeTree()` engine.
- [ ] All `CREATE MATERIALIZED VIEW` use `IF NOT EXISTS` and `TO` clause.
- [ ] Click stats have device_type/os/browser dimensions; conversion stats do not.
- [ ] ORDER BY columns in target tables match GROUP BY columns in corresponding MVs exactly.
- [ ] Monetary columns use Decimal(14,4) for aggregation headroom.
- [ ] String dimension columns use LowCardinality(String).
</verification>

<success_criteria>
- [ ] Migration file is syntactically valid ClickHouse DDL.
- [ ] Stats tables are SummingMergeTree with correct partition and order keys.
- [ ] MVs correctly aggregate from raw clicks and conversions tables.
- [ ] Click stats and conversion stats are independent (no cross-table JOIN in DDL).
</success_criteria>
