-- 010_analytics_expansion.sql
-- Expand conversions with tracking type and improve stats performance.

ALTER TABLE conversions
    ADD COLUMN IF NOT EXISTS conversion_type String DEFAULT 'postback' AFTER external_id;

-- Add tracking type to conversion stats
ALTER TABLE conv_stats_hourly ADD COLUMN IF NOT EXISTS conversion_type LowCardinality(String) AFTER status;
ALTER TABLE conv_stats_daily  ADD COLUMN IF NOT EXISTS conversion_type LowCardinality(String) AFTER status;

-- Recreate Conversion Materialized Views to include tracking type
DROP VIEW IF EXISTS mv_conv_stats_hourly;
CREATE MATERIALIZED VIEW mv_conv_stats_hourly
TO conv_stats_hourly
AS
SELECT
  toStartOfHour(created_at) AS hour,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  toLowCardinality(status) AS status,
  toLowCardinality(conversion_type) AS conversion_type,
  count() AS conversions,
  CAST(sum(revenue), 'Decimal(14, 4)') AS revenue,
  CAST(sum(payout), 'Decimal(14, 4)') AS payout
FROM conversions
GROUP BY
  hour, campaign_id, stream_id, offer_id, country_code, status, conversion_type;

DROP VIEW IF EXISTS mv_conv_stats_daily;
CREATE MATERIALIZED VIEW mv_conv_stats_daily
TO conv_stats_daily
AS
SELECT
  toDate(created_at) AS day,
  campaign_id,
  stream_id,
  offer_id,
  country_code,
  toLowCardinality(status) AS status,
  toLowCardinality(conversion_type) AS conversion_type,
  count() AS conversions,
  CAST(sum(revenue), 'Decimal(14, 4)') AS revenue,
  CAST(sum(payout), 'Decimal(14, 4)') AS payout
FROM conversions
GROUP BY
  day, campaign_id, stream_id, offer_id, country_code, status, conversion_type;

-- Optional: Add index for click_token if not primary to speed up attribution lookups
-- in environments without a warm Valkey cache.
-- ALTER TABLE clicks ADD INDEX IF NOT EXISTS idx_click_token click_token TYPE minmax GRANULARITY 1;
