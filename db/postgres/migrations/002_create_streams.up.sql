CREATE TYPE stream_type AS ENUM ('REGULAR', 'FORCED', 'DEFAULT');

CREATE TABLE streams (
  id              UUID          PRIMARY KEY DEFAULT uuid_generate_v4(),
  campaign_id     UUID          NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
  name            VARCHAR(255)  NOT NULL,
  type            stream_type   NOT NULL DEFAULT 'REGULAR',
  position        INT           NOT NULL DEFAULT 0,
  weight          INT           NOT NULL DEFAULT 100,
  state           VARCHAR(50)   NOT NULL DEFAULT 'active',
  action_type     VARCHAR(100)  NOT NULL DEFAULT 'HttpRedirect',
  action_payload  JSONB         NOT NULL DEFAULT '{}',
  filters         JSONB         NOT NULL DEFAULT '[]',
  created_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
  updated_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_streams_campaign_id ON streams(campaign_id);
CREATE INDEX idx_streams_state       ON streams(state);

-- Association tables with weights (stream ↔ landing, stream ↔ offer)
-- These are join tables with rotation weights, pattern from ARCHITECTURE.md
CREATE TABLE stream_landings (
  stream_id   UUID  NOT NULL REFERENCES streams(id)  ON DELETE CASCADE,
  landing_id  UUID  NOT NULL,
  weight      INT   NOT NULL DEFAULT 100,
  PRIMARY KEY (stream_id, landing_id)
);

CREATE TABLE stream_offers (
  stream_id   UUID  NOT NULL REFERENCES streams(id)  ON DELETE CASCADE,
  offer_id    UUID  NOT NULL,
  weight      INT   NOT NULL DEFAULT 100,
  PRIMARY KEY (stream_id, offer_id)
);
