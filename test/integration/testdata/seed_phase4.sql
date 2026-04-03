-- Phase 4 Cloaking Test Seed
-- Campaign: cloaked-test
-- Stream 1: Human (REGULAR), Filter {IsBot: false}, Action: HttpRedirect (https://real-offer.com)
-- Stream 2: Bot (DEFAULT), Filter {IsBot: true}, Action: ShowHtml (safe-page-v1)

DELETE FROM campaigns WHERE alias = 'cloaked-test';
DELETE FROM users WHERE login = 'test-admin';

INSERT INTO users (id, login, password_hash, role, state, api_key)
VALUES ('eeeeeeee-eeee-4eee-aeee-eeeeeeeeeeee', 'test-admin', 'not-used', 'admin', 'active', 'test-api-key-32-chars-long-exactly-!!')
ON CONFLICT (id) DO UPDATE SET api_key = EXCLUDED.api_key;


INSERT INTO campaigns (id, alias, name, type, state)
VALUES ('77777777-7777-4777-a777-777777777777', 'cloaked-test', 'Cloaking Verification', 'POSITION', 'active');

-- Stream 1: Human
INSERT INTO streams (id, campaign_id, name, type, position, action_type, action_payload, filters, state)
VALUES (
    '11111111-1111-4111-a111-111111111111', 
    '77777777-7777-4777-a777-777777777777', 
    'Safe Human Stream', 
    'REGULAR', 
    1, 
    'HttpRedirect', 
    '{"url": "https://real-offer.com"}', 
    '[{"type": "IsBot", "payload": {"is_bot": false}}]', 
    'active'
);

-- Stream 2: Bot (Fallback)
INSERT INTO streams (id, campaign_id, name, type, position, action_type, action_payload, filters, state)
VALUES (
    '22222222-2222-4222-a222-222222222222', 
    '77777777-7777-4777-a777-777777777777', 
    'Bot Safe Page', 
    'DEFAULT', 
    2, 
    'ShowHtml', 
    '{"html": "<html><body><h1>Welcome to our safe page</h1></body></html>"}', 
    '[{"type": "IsBot", "payload": {"is_bot": true}}]', 
    'active'
);

-- Campaign: cloaked-proxy-test
DELETE FROM campaigns WHERE alias = 'cloaked-proxy-test';
INSERT INTO campaigns (id, alias, name, type, state)
VALUES ('99999999-9999-4999-a999-999999999999', 'cloaked-proxy-test', 'Proxy/Curl Verification', 'POSITION', 'active');

-- Stream 1: Remote Proxy (Bot)
INSERT INTO streams (id, campaign_id, name, type, position, action_type, action_payload, filters, state)
VALUES (
    '10101010-1010-4010-a010-101010101010', 
    '99999999-9999-4999-a999-999999999999', 
    'Remote Safe Page', 
    'REGULAR', 
    1, 
    'Remote', 
    '{"url": "http://localhost:8080/api/v1/health"}', 
    '[{"type": "IsBot", "payload": {"is_bot": true}}]', 
    'active'
);

-- Stream 2: Curl (Default)
INSERT INTO streams (id, campaign_id, name, type, position, action_type, action_payload, filters, state)
VALUES (
    '11111111-1111-4111-a111-111111110000', 
    '99999999-9999-4999-a999-999999999999', 
    'Curl Safe Page', 
    'DEFAULT', 
    2, 
    'Curl', 
    '{"url": "http://localhost:8080/api/v1/health"}', 
    '[]', 
    'active'
);
