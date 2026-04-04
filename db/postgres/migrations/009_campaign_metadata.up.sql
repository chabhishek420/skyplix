-- Create Campaign Groups table
CREATE TABLE IF NOT EXISTS campaign_groups (
    id              UUID          PRIMARY KEY DEFAULT uuid_generate_v4(),
    workspace_id    UUID          NOT NULL REFERENCES workspaces(id) ON DELETE CASCADE,
    name            VARCHAR(255)  NOT NULL,
    notes           TEXT,
    created_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_campaign_groups_workspace_id ON campaign_groups(workspace_id);

-- Add metadata to campaigns
CREATE TYPE cost_model AS ENUM ('CPC', 'CPM', 'CPA', 'RevShare');

ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS group_id UUID REFERENCES campaign_groups(id) ON DELETE SET NULL;
ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS cost_model cost_model NOT NULL DEFAULT 'CPC';
ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS cost_value BIGINT NOT NULL DEFAULT 0;
ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS notes TEXT;
ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS tags JSONB NOT NULL DEFAULT '[]';

-- Add metadata to offers and landings
ALTER TABLE offers ADD COLUMN IF NOT EXISTS notes TEXT;
ALTER TABLE offers ADD COLUMN IF NOT EXISTS daily_cap INT NOT NULL DEFAULT 0;

ALTER TABLE landings ADD COLUMN IF NOT EXISTS notes TEXT;

-- Create indexes
CREATE INDEX idx_campaigns_group_id ON campaigns(group_id);
CREATE INDEX idx_campaigns_tags ON campaigns USING GIN (tags);
