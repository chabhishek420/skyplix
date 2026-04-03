-- Expand ClickHouse conversions table to match Phase 5 conversion attribution requirements.
-- Aligns schema with `internal/queue/writer.go` ConversionRecord insert list.

-- Rename primary key column to match writer.
ALTER TABLE conversions RENAME COLUMN conversion_id TO id;

-- Add attribution fields.
ALTER TABLE conversions ADD COLUMN IF NOT EXISTS stream_id UUID DEFAULT toUUID('00000000-0000-0000-0000-000000000000') AFTER campaign_id;
ALTER TABLE conversions ADD COLUMN IF NOT EXISTS offer_id UUID DEFAULT toUUID('00000000-0000-0000-0000-000000000000') AFTER stream_id;
ALTER TABLE conversions ADD COLUMN IF NOT EXISTS landing_id UUID DEFAULT toUUID('00000000-0000-0000-0000-000000000000') AFTER offer_id;
ALTER TABLE conversions ADD COLUMN IF NOT EXISTS source_id UUID DEFAULT toUUID('00000000-0000-0000-0000-000000000000') AFTER affiliate_network_id;
ALTER TABLE conversions ADD COLUMN IF NOT EXISTS country_code FixedString(2) DEFAULT '  ' AFTER source_id;
ALTER TABLE conversions ADD COLUMN IF NOT EXISTS external_id String DEFAULT '' AFTER revenue;

