-- 008_add_bot_reason.sql
-- Adds BotReason column to the clicks table for better debugging of bot detection.

ALTER TABLE zai_analytics.clicks
ADD COLUMN IF NOT EXISTS bot_reason String AFTER tls_host;
