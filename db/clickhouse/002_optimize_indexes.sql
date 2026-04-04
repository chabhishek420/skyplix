-- Add SKIP indexes on frequently joined or filtered columns
-- Using bloom_filter for high-cardinality equality checks (UUIDs, tokens)
-- Using set(0) for low-cardinality discrete sets (country code, status)
-- Using minmax for boolean flags

-- Clicks Table Indexes
ALTER TABLE clicks ADD INDEX IF NOT EXISTS idx_campaign_id campaign_id TYPE bloom_filter() GRANULARITY 4;
ALTER TABLE clicks MATERIALIZE INDEX IF NOT EXISTS idx_campaign_id;

ALTER TABLE clicks ADD INDEX IF NOT EXISTS idx_click_token click_token TYPE bloom_filter() GRANULARITY 4;
ALTER TABLE clicks MATERIALIZE INDEX IF NOT EXISTS idx_click_token;

ALTER TABLE clicks ADD INDEX IF NOT EXISTS idx_country_code country_code TYPE set(0) GRANULARITY 1;
ALTER TABLE clicks MATERIALIZE INDEX IF NOT EXISTS idx_country_code;

ALTER TABLE clicks ADD INDEX IF NOT EXISTS idx_is_bot is_bot TYPE minmax GRANULARITY 1;
ALTER TABLE clicks MATERIALIZE INDEX IF NOT EXISTS idx_is_bot;

-- Conversions Table Indexes
ALTER TABLE conversions ADD INDEX IF NOT EXISTS idx_conv_click_token click_token TYPE bloom_filter() GRANULARITY 4;
ALTER TABLE conversions MATERIALIZE INDEX IF NOT EXISTS idx_conv_click_token;

ALTER TABLE conversions ADD INDEX IF NOT EXISTS idx_conv_campaign_id campaign_id TYPE bloom_filter() GRANULARITY 4;
ALTER TABLE conversions MATERIALIZE INDEX IF NOT EXISTS idx_conv_campaign_id;

ALTER TABLE conversions ADD INDEX IF NOT EXISTS idx_conv_status status TYPE set(0) GRANULARITY 1;
ALTER TABLE conversions MATERIALIZE INDEX IF NOT EXISTS idx_conv_status;
