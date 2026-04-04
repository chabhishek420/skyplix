ALTER TABLE affiliate_networks DROP COLUMN IF EXISTS workspace_id;
ALTER TABLE traffic_sources DROP COLUMN IF EXISTS workspace_id;
ALTER TABLE domains DROP COLUMN IF EXISTS workspace_id;
ALTER TABLE landings DROP COLUMN IF EXISTS workspace_id;
ALTER TABLE offers DROP COLUMN IF EXISTS workspace_id;
ALTER TABLE campaigns DROP COLUMN IF EXISTS workspace_id;
