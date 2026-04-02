CREATE TABLE settings (
    key   VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO settings (key, value) VALUES
    ('system.name', 'SkyPlix TDS'),
    ('system.timezone', 'UTC'),
    ('tracker.postback_key', encode(gen_random_bytes(16), 'hex')),
    ('tracker.default_campaign_id', ''),
    ('security.max_auth_tries', '5');
