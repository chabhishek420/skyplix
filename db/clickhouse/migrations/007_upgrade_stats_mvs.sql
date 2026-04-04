-- Phase 11: Real-time Analytics & ClickHouse Pro
-- 1. Upgrade stats tables with complete fingerprint dimensions
-- 2. Implement Automated Data Retention (TTL)
-- 3. Optimization indexes

-- Add missing fingerprint columns to stats tables
ALTER TABLE stats_hourly ADD COLUMN IF NOT EXISTS ja4 String AFTER ja3;
ALTER TABLE stats_hourly ADD COLUMN IF NOT EXISTS tls_host String AFTER ja4;

ALTER TABLE stats_daily ADD COLUMN IF NOT EXISTS ja3 String AFTER browser;
ALTER TABLE stats_daily ADD COLUMN IF NOT EXISTS ja4 String AFTER ja3;
ALTER TABLE stats_daily ADD COLUMN IF NOT EXISTS tls_host String AFTER ja4;

ALTER TABLE conv_stats_hourly ADD COLUMN IF NOT EXISTS ja4 String AFTER ja3;
ALTER TABLE conv_stats_daily ADD COLUMN IF NOT EXISTS ja3 String AFTER country_code;
ALTER TABLE conv_stats_daily ADD COLUMN IF NOT EXISTS ja4 String AFTER ja3;

-- Implement TTL (Automated Data Retention)
-- Raw logs: 60 days
ALTER TABLE clicks MODIFY TTL toDateTime(created_at) + INTERVAL 60 DAY;
ALTER TABLE conversions MODIFY TTL toDateTime(created_at) + INTERVAL 60 DAY;

-- Hourly stats: 6 months
ALTER TABLE stats_hourly MODIFY TTL hour + INTERVAL 180 DAY;
ALTER TABLE conv_stats_hourly MODIFY TTL hour + INTERVAL 180 DAY;

-- Daily stats: 2 years
ALTER TABLE stats_daily MODIFY TTL day + INTERVAL 2 YEAR;
ALTER TABLE conv_stats_daily MODIFY TTL day + INTERVAL 2 YEAR;

-- Recreate Materialized Views to include new dimensions
DROP VIEW IF EXISTS mv_stats_hourly_clicks;
CREATE MATERIALIZED VIEW mv_stats_hourly_clicks
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
  ja3,
  ja4,
  tls_host,
  count() AS clicks,
  sum(toUInt64(is_unique_global)) AS unique_clicks,
  sum(toUInt64(is_bot)) AS bots,
  CAST(sum(cost), 'Decimal(14, 4)') AS cost,
  CAST(sum(payout), 'Decimal(14, 4)') AS click_payout
FROM clicks
GROUP BY
  hour, campaign_id, stream_id, offer_id, landing_id, country_code,
  device_type, os, browser, ja3, ja4, tls_host;

DROP VIEW IF EXISTS mv_stats_daily_clicks;
CREATE MATERIALIZED VIEW mv_stats_daily_clicks
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
  ja3,
  ja4,
  tls_host,
  count() AS clicks,
  sum(toUInt64(is_unique_global)) AS unique_clicks,
  sum(toUInt64(is_bot)) AS bots,
  CAST(sum(cost), 'Decimal(14, 4)') AS cost,
  CAST(sum(payout), 'Decimal(14, 4)') AS click_payout
FROM clicks
GROUP BY
  day, campaign_id, stream_id, offer_id, landing_id, country_code,
  device_type, os, browser, ja3, ja4, tls_host;
