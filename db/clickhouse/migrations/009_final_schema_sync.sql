-- 009_final_schema_sync.sql
-- Synchronizes 'clicks' and 'conversions' tables with the latest model fields.

ALTER TABLE clicks
    ADD COLUMN IF NOT EXISTS ja3 String AFTER click_token,
    ADD COLUMN IF NOT EXISTS ja4 String AFTER ja3,
    ADD COLUMN IF NOT EXISTS tls_host String AFTER ja4;

-- Note: bot_reason was already added in 008.

-- Conversions alignment
ALTER TABLE conversions
    ADD COLUMN IF NOT EXISTS affiliate_network_id UUID DEFAULT toUUID('00000000-0000-0000-0000-000000000000') AFTER landing_id;
