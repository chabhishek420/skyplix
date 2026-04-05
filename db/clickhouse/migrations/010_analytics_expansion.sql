-- Phase 2.1: Analytics expansion with workspace scoping and advanced bot detection.

-- 1. clicks table
ALTER TABLE clicks ADD COLUMN IF NOT EXISTS workspace_id UUID AFTER created_at;
ALTER TABLE clicks ADD COLUMN IF NOT EXISTS request_id String AFTER click_id;
ALTER TABLE clicks ADD COLUMN IF NOT EXISTS behavior_score UInt8 DEFAULT 0 AFTER is_proxy;
ALTER TABLE clicks ADD COLUMN IF NOT EXISTS tls_fingerprint String AFTER click_token;

-- 2. conversions table
ALTER TABLE conversions ADD COLUMN IF NOT EXISTS workspace_id UUID AFTER created_at;
ALTER TABLE conversions ADD COLUMN IF NOT EXISTS conversion_type LowCardinality(String) DEFAULT 'lead' AFTER status;
ALTER TABLE conversions ADD COLUMN IF NOT EXISTS visitor_code String AFTER click_token;

-- 3. Update stats_hourly to include workspace_id
ALTER TABLE stats_hourly ADD COLUMN IF NOT EXISTS workspace_id UUID AFTER hour;
ALTER TABLE stats_daily ADD COLUMN IF NOT EXISTS workspace_id UUID AFTER day;
ALTER TABLE conv_stats_hourly ADD COLUMN IF NOT EXISTS workspace_id UUID AFTER hour;
ALTER TABLE conv_stats_daily ADD COLUMN IF NOT EXISTS workspace_id UUID AFTER day;

-- 4. Update Materialized Views to populate workspace_id

DROP VIEW IF EXISTS mv_stats_hourly_clicks;
CREATE MATERIALIZED VIEW mv_stats_hourly_clicks
TO stats_hourly
AS
SELECT
  toStartOfHour(created_at) AS hour,
  workspace_id,
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
  sum(cost) AS cost,
  sum(payout) AS click_payout
FROM clicks
GROUP BY
  hour,
  workspace_id,
  campaign_id,
  stream_id,
  offer_id,
  landing_id,
  country_code,
  device_type,
  os,
  browser;

DROP VIEW IF EXISTS mv_stats_daily_clicks;
CREATE MATERIALIZED VIEW mv_stats_daily_clicks
TO stats_daily
AS
SELECT
  toDate(created_at) AS day,
  workspace_id,
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
  sum(cost) AS cost,
  sum(payout) AS click_payout
FROM clicks
GROUP BY
  day,
  workspace_id,
  campaign_id,
  stream_id,
  offer_id,
  landing_id,
  country_code,
  device_type,
  os,
  browser;

DROP VIEW IF EXISTS mv_conv_stats_hourly;
CREATE MATERIALIZED VIEW mv_conv_stats_hourly
TO conv_stats_hourly
AS
SELECT
  toStartOfHour(created_at) AS hour,
  workspace_id,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  toLowCardinality(status) AS status,
  count() AS conversions,
  sum(revenue) AS revenue,
  sum(payout) AS payout
FROM conversions
GROUP BY
  hour,
  workspace_id,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  status;

DROP VIEW IF EXISTS mv_conv_stats_daily;
CREATE MATERIALIZED VIEW mv_conv_stats_daily
TO conv_stats_daily
AS
SELECT
  toDate(created_at) AS day,
  workspace_id,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  toLowCardinality(status) AS status,
  count() AS conversions,
  sum(revenue) AS revenue,
  sum(payout) AS payout
FROM conversions
GROUP BY
  day,
  workspace_id,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  status;
