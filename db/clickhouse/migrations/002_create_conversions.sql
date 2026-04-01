-- ClickHouse conversions table
-- Linked to clicks via click_token for attribution
CREATE TABLE IF NOT EXISTS conversions (
  conversion_id        UUID          DEFAULT generateUUIDv4(),
  created_at           DateTime64(3, 'UTC'),
  click_token          String,
  campaign_id          UUID,
  affiliate_network_id UUID,
  status               String        DEFAULT 'lead',
  payout               Decimal(10, 4) DEFAULT 0,
  revenue              Decimal(10, 4) DEFAULT 0
) ENGINE = MergeTree()
ORDER BY (created_at, click_token)
SETTINGS index_granularity = 8192;
