-- Phase 13: Entity Grouping
CREATE TABLE IF NOT EXISTS groups (
  id          UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name        VARCHAR(255) NOT NULL,
  type        VARCHAR(50) NOT NULL, -- 'campaign', 'offer', 'landing'
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS group_id UUID REFERENCES groups(id);
ALTER TABLE offers ADD COLUMN IF NOT EXISTS group_id UUID REFERENCES groups(id);
ALTER TABLE landings ADD COLUMN IF NOT EXISTS group_id UUID REFERENCES groups(id);
