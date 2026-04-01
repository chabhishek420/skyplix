CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

CREATE TYPE campaign_type AS ENUM ('POSITION', 'WEIGHT');

CREATE TABLE campaigns (
  id              UUID         PRIMARY KEY DEFAULT uuid_generate_v4(),
  alias           VARCHAR(255) UNIQUE NOT NULL,
  name            VARCHAR(255) NOT NULL,
  type            campaign_type NOT NULL DEFAULT 'POSITION',
  bind_visitors   BOOLEAN      NOT NULL DEFAULT false,
  state           VARCHAR(50)  NOT NULL DEFAULT 'active',
  traffic_source_id UUID,
  default_stream_id UUID,
  created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
  updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_campaigns_alias ON campaigns(alias);
CREATE INDEX idx_campaigns_state ON campaigns(state);
