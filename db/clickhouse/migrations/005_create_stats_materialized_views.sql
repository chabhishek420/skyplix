-- Phase 5.1: ClickHouse materialized views and stats tables
--
-- Creates SummingMergeTree target tables and insert-triggered materialized views for real-time stats aggregation.
--
-- NOTE: The `conversions` table was originally created without partitioning (see 002_create_conversions.sql).
-- ClickHouse does not allow changing PARTITION BY via a simple ALTER. If you need daily partitioning for conversions,
-- use a shadow-table swap approach similar to 003_optimize_clicks.sql (create `conversions_v2` with
-- `PARTITION BY toYYYYMMDD(created_at)` and then RENAME TABLE).

-- Click stats (hourly)
CREATE TABLE IF NOT EXISTS stats_hourly (
  hour         DateTime('UTC'),
  campaign_id  UUID,
  stream_id    UUID,
  offer_id     UUID,
  landing_id   UUID,
  country_code FixedString(2),
  device_type  LowCardinality(String),
  os           LowCardinality(String),
  browser      LowCardinality(String),

  clicks       UInt64,
  unique_clicks UInt64,
  bots         UInt64,
  cost         Decimal(14, 4) DEFAULT 0,
  click_payout Decimal(14, 4) DEFAULT 0
)
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(hour)
ORDER BY (campaign_id, hour, stream_id, offer_id, landing_id, country_code, device_type, os, browser)
SETTINGS index_granularity = 8192;

CREATE MATERIALIZED VIEW IF NOT EXISTS mv_stats_hourly_clicks
TO stats_hourly
AS
SELECT
  toStartOfHour(created_at) AS hour,
  campaign_id,
  stream_id,
  offer_id,
  landing_id,
  country_code,
  toLowCardinality(device_type) AS device_type,
  toLowCardinality(os) AS os,
  toLowCardinality(browser) AS browser,
  count() AS clicks,
  sum(toUInt64(is_unique_global)) AS unique_clicks,
  sum(toUInt64(is_bot)) AS bots,
  CAST(sum(cost), 'Decimal(14, 4)') AS cost,
  CAST(sum(payout), 'Decimal(14, 4)') AS click_payout
FROM clicks
GROUP BY
  hour,
  campaign_id,
  stream_id,
  offer_id,
  landing_id,
  country_code,
  device_type,
  os,
  browser;

-- Click stats (daily)
CREATE TABLE IF NOT EXISTS stats_daily (
  day          Date,
  campaign_id  UUID,
  stream_id    UUID,
  offer_id     UUID,
  landing_id   UUID,
  country_code FixedString(2),
  device_type  LowCardinality(String),
  os           LowCardinality(String),
  browser      LowCardinality(String),

  clicks       UInt64,
  unique_clicks UInt64,
  bots         UInt64,
  cost         Decimal(14, 4) DEFAULT 0,
  click_payout Decimal(14, 4) DEFAULT 0
)
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(day)
ORDER BY (campaign_id, day, stream_id, offer_id, landing_id, country_code, device_type, os, browser)
SETTINGS index_granularity = 8192;

CREATE MATERIALIZED VIEW IF NOT EXISTS mv_stats_daily_clicks
TO stats_daily
AS
SELECT
  toDate(created_at) AS day,
  campaign_id,
  stream_id,
  offer_id,
  landing_id,
  country_code,
  toLowCardinality(device_type) AS device_type,
  toLowCardinality(os) AS os,
  toLowCardinality(browser) AS browser,
  count() AS clicks,
  sum(toUInt64(is_unique_global)) AS unique_clicks,
  sum(toUInt64(is_bot)) AS bots,
  CAST(sum(cost), 'Decimal(14, 4)') AS cost,
  CAST(sum(payout), 'Decimal(14, 4)') AS click_payout
FROM clicks
GROUP BY
  day,
  campaign_id,
  stream_id,
  offer_id,
  landing_id,
  country_code,
  device_type,
  os,
  browser;

-- Conversion stats (hourly)
CREATE TABLE IF NOT EXISTS conv_stats_hourly (
  hour         DateTime('UTC'),
  campaign_id  UUID,
  stream_id    UUID,
  offer_id     UUID,
  country_code FixedString(2),
  status       LowCardinality(String),

  conversions  UInt64,
  revenue      Decimal(14, 4) DEFAULT 0,
  payout       Decimal(14, 4) DEFAULT 0
)
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(hour)
ORDER BY (campaign_id, hour, stream_id, offer_id, country_code, status)
SETTINGS index_granularity = 8192;

CREATE MATERIALIZED VIEW IF NOT EXISTS mv_conv_stats_hourly
TO conv_stats_hourly
AS
SELECT
  toStartOfHour(created_at) AS hour,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  toLowCardinality(status) AS status,
  count() AS conversions,
  CAST(sum(revenue), 'Decimal(14, 4)') AS revenue,
  CAST(sum(payout), 'Decimal(14, 4)') AS payout
FROM conversions
GROUP BY
  hour,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  status;

-- Conversion stats (daily)
CREATE TABLE IF NOT EXISTS conv_stats_daily (
  day          Date,
  campaign_id  UUID,
  stream_id    UUID,
  offer_id     UUID,
  country_code FixedString(2),
  status       LowCardinality(String),

  conversions  UInt64,
  revenue      Decimal(14, 4) DEFAULT 0,
  payout       Decimal(14, 4) DEFAULT 0
)
ENGINE = SummingMergeTree()
PARTITION BY toYYYYMM(day)
ORDER BY (campaign_id, day, stream_id, offer_id, country_code, status)
SETTINGS index_granularity = 8192;

CREATE MATERIALIZED VIEW IF NOT EXISTS mv_conv_stats_daily
TO conv_stats_daily
AS
SELECT
  toDate(created_at) AS day,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  toLowCardinality(status) AS status,
  count() AS conversions,
  CAST(sum(revenue), 'Decimal(14, 4)') AS revenue,
  CAST(sum(payout), 'Decimal(14, 4)') AS payout
FROM conversions
GROUP BY
  day,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  status;
