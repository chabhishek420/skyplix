-- Optimize ClickHouse clicks table partitioning and indexing
-- This migration updates the partitioning strategy to daily for better pruning
-- and adds a skip index for click_token to speed up Phase 5 Join operations.
--
-- Since Partitioning changes are not allowed via simple ALTER, we:
-- 1. Create a shadow table 'clicks_v2'
-- 2. Swap it with 'clicks'

-- Step 1 — Shadow Table V2
CREATE TABLE IF NOT EXISTS clicks_v2 (
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
  click_token          String,
  
  -- Skip index for attribution performance
  INDEX idx_click_token (click_token) TYPE minmax GRANULARITY 8192
) ENGINE = MergeTree()
PARTITION BY toYYYYMMDD(created_at) -- Daily partitioning (Hardening improvement)
ORDER BY (campaign_id, created_at)   -- Keep primary clustering for aggregation
SETTINGS index_granularity = 8192;

-- Step 2 — Migration script (optional for live, but here for completeness)
-- INSERT INTO clicks_v2 SELECT * FROM clicks;

-- Step 3 — Replace tables
-- RENAME TABLE clicks TO clicks_v1, clicks_v2 TO clicks;
