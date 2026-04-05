-- 010_analytics_expansion.sql
-- Expand conversions with tracking type and improve stats performance.

ALTER TABLE conversions
    ADD COLUMN IF NOT EXISTS conversion_type String DEFAULT 'postback' AFTER external_id;

-- Optional: Add index for click_token if not primary to speed up attribution lookups
-- in environments without a warm Valkey cache.
-- ALTER TABLE clicks ADD INDEX IF NOT EXISTS idx_click_token click_token TYPE minmax GRANULARITY 1;
