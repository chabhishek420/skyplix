-- Fix: daily_limit and total_limit are in Go model + cache.go scans but missing from schema (AUDIT FIX #1)
ALTER TABLE streams ADD COLUMN IF NOT EXISTS daily_limit BIGINT NOT NULL DEFAULT 0;
ALTER TABLE streams ADD COLUMN IF NOT EXISTS total_limit BIGINT NOT NULL DEFAULT 0;

-- API key auth for admin endpoints
ALTER TABLE users ADD COLUMN IF NOT EXISTS api_key VARCHAR(64) UNIQUE;
UPDATE users SET api_key = encode(gen_random_bytes(32), 'hex') WHERE api_key IS NULL;
