-- ClickHouse clicks table
-- Partitioned by month for efficient pruning
-- Ordered by (campaign_id, created_at) for fast per-campaign aggregations
CREATE TABLE IF NOT EXISTS clicks (
  click_id             UUID         DEFAULT generateUUIDv4(),
  created_at           DateTime64(3, 'UTC'),
  campaign_id          UUID,
  campaign_alias       String,
  stream_id            UUID,
  offer_id             UUID,
  landing_id           UUID,
  ip                   IPv6,
  country_code         FixedString(2),
  city                 String,
  isp                  String,
  device_type          String,
  device_model         String,
  os                   String,
  os_version           String,
  browser              String,
  browser_version      String,
  user_agent           String,
  referrer             String,
  is_bot               UInt8        DEFAULT 0,
  is_unique_global     UInt8        DEFAULT 0,
  is_unique_campaign   UInt8        DEFAULT 0,
  is_unique_stream     UInt8        DEFAULT 0,
  sub_id_1             String,
  sub_id_2             String,
  sub_id_3             String,
  sub_id_4             String,
  sub_id_5             String,
  cost                 Decimal(10, 4) DEFAULT 0,
  payout               Decimal(10, 4) DEFAULT 0,
  action_type          String,
  click_token          String
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(created_at)
ORDER BY (campaign_id, created_at)
SETTINGS index_granularity = 8192;
