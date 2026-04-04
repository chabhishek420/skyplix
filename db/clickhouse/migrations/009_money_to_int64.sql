-- Phase 1.5: Migrate monetary columns to Int64 (integer cents)
-- This avoids floating point precision issues and aligns with "All monetary: integer cents" production constraint.

-- 1. clicks table
ALTER TABLE clicks MODIFY COLUMN cost Int64 DEFAULT 0;
ALTER TABLE clicks MODIFY COLUMN payout Int64 DEFAULT 0;

-- 2. conversions table
ALTER TABLE conversions MODIFY COLUMN payout Int64 DEFAULT 0;
ALTER TABLE conversions MODIFY COLUMN revenue Int64 DEFAULT 0;

-- 3. stats_hourly and stats_daily
ALTER TABLE stats_hourly MODIFY COLUMN cost Int64 DEFAULT 0;
ALTER TABLE stats_hourly MODIFY COLUMN click_payout Int64 DEFAULT 0;

ALTER TABLE stats_daily MODIFY COLUMN cost Int64 DEFAULT 0;
ALTER TABLE stats_daily MODIFY COLUMN click_payout Int64 DEFAULT 0;

-- 4. conv_stats_hourly and conv_stats_daily
ALTER TABLE conv_stats_hourly MODIFY COLUMN revenue Int64 DEFAULT 0;
ALTER TABLE conv_stats_hourly MODIFY COLUMN payout Int64 DEFAULT 0;

ALTER TABLE conv_stats_daily MODIFY COLUMN revenue Int64 DEFAULT 0;
ALTER TABLE conv_stats_daily MODIFY COLUMN payout Int64 DEFAULT 0;
