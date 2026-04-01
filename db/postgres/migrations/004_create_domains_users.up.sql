CREATE TABLE domains (
  id          UUID          PRIMARY KEY DEFAULT uuid_generate_v4(),
  domain      VARCHAR(255)  UNIQUE NOT NULL,
  campaign_id UUID          REFERENCES campaigns(id) ON DELETE SET NULL,
  state       VARCHAR(50)   NOT NULL DEFAULT 'active',
  created_at  TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

CREATE TABLE users (
  id             UUID          PRIMARY KEY DEFAULT uuid_generate_v4(),
  login          VARCHAR(255)  UNIQUE NOT NULL,
  password_hash  VARCHAR(255)  NOT NULL,
  role           VARCHAR(50)   NOT NULL DEFAULT 'user',
  state          VARCHAR(50)   NOT NULL DEFAULT 'active',
  created_at     TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
  updated_at     TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- Default admin user (password: admin123 — change immediately in production)
INSERT INTO users (login, password_hash, role)
VALUES ('admin', crypt('admin123', gen_salt('bf')), 'administrator');

CREATE TABLE traffic_sources (
  id          UUID          PRIMARY KEY DEFAULT uuid_generate_v4(),
  name        VARCHAR(255)  NOT NULL,
  postback_url TEXT,
  params      JSONB         NOT NULL DEFAULT '{}',
  state       VARCHAR(50)   NOT NULL DEFAULT 'active',
  created_at  TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);
