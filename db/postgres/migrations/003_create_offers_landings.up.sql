CREATE TABLE landings (
  id          UUID          PRIMARY KEY DEFAULT uuid_generate_v4(),
  name        VARCHAR(255)  NOT NULL,
  url         TEXT          NOT NULL,
  state       VARCHAR(50)   NOT NULL DEFAULT 'active',
  created_at  TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

CREATE TABLE offers (
  id                   UUID           PRIMARY KEY DEFAULT uuid_generate_v4(),
  name                 VARCHAR(255)   NOT NULL,
  url                  TEXT           NOT NULL,
  affiliate_network_id UUID,
  payout               BIGINT         NOT NULL DEFAULT 0,
  state                VARCHAR(50)    NOT NULL DEFAULT 'active',
  created_at           TIMESTAMPTZ    NOT NULL DEFAULT NOW(),
  updated_at           TIMESTAMPTZ    NOT NULL DEFAULT NOW()
);

CREATE TABLE affiliate_networks (
  id              UUID          PRIMARY KEY DEFAULT uuid_generate_v4(),
  name            VARCHAR(255)  NOT NULL,
  postback_url    TEXT,
  state           VARCHAR(50)   NOT NULL DEFAULT 'active',
  created_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
  updated_at      TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
