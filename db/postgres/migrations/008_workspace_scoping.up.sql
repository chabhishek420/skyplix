-- Add workspace_id to major entities
ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS workspace_id UUID REFERENCES workspaces(id) ON DELETE SET NULL;
ALTER TABLE offers ADD COLUMN IF NOT EXISTS workspace_id UUID REFERENCES workspaces(id) ON DELETE SET NULL;
ALTER TABLE landings ADD COLUMN IF NOT EXISTS workspace_id UUID REFERENCES workspaces(id) ON DELETE SET NULL;
ALTER TABLE domains ADD COLUMN IF NOT EXISTS workspace_id UUID REFERENCES workspaces(id) ON DELETE SET NULL;
ALTER TABLE traffic_sources ADD COLUMN IF NOT EXISTS workspace_id UUID REFERENCES workspaces(id) ON DELETE SET NULL;
ALTER TABLE affiliate_networks ADD COLUMN IF NOT EXISTS workspace_id UUID REFERENCES workspaces(id) ON DELETE SET NULL;
ALTER TABLE streams ADD COLUMN IF NOT EXISTS workspace_id UUID REFERENCES workspaces(id) ON DELETE SET NULL;

-- Default all existing entities to the default workspace created in 007
DO $$
DECLARE
    default_ws_id UUID;
BEGIN
    SELECT id INTO default_ws_id FROM workspaces WHERE name = 'Default Workspace' LIMIT 1;

    IF default_ws_id IS NOT NULL THEN
        UPDATE campaigns SET workspace_id = default_ws_id WHERE workspace_id IS NULL;
        UPDATE streams SET workspace_id = default_ws_id WHERE workspace_id IS NULL;
        UPDATE offers SET workspace_id = default_ws_id WHERE workspace_id IS NULL;
        UPDATE landings SET workspace_id = default_ws_id WHERE workspace_id IS NULL;
        UPDATE domains SET workspace_id = default_ws_id WHERE workspace_id IS NULL;
        UPDATE traffic_sources SET workspace_id = default_ws_id WHERE workspace_id IS NULL;
        UPDATE affiliate_networks SET workspace_id = default_ws_id WHERE workspace_id IS NULL;
    END IF;
END $$;

-- Create indexes for workspace_id
CREATE INDEX IF NOT EXISTS idx_campaigns_workspace_id ON campaigns(workspace_id);
CREATE INDEX IF NOT EXISTS idx_offers_workspace_id ON offers(workspace_id);
CREATE INDEX IF NOT EXISTS idx_landings_workspace_id ON landings(workspace_id);
CREATE INDEX IF NOT EXISTS idx_domains_workspace_id ON domains(workspace_id);
CREATE INDEX IF NOT EXISTS idx_traffic_sources_workspace_id ON traffic_sources(workspace_id);
CREATE INDEX IF NOT EXISTS idx_affiliate_networks_workspace_id ON affiliate_networks(workspace_id);
CREATE INDEX IF NOT EXISTS idx_streams_workspace_id ON streams(workspace_id);
