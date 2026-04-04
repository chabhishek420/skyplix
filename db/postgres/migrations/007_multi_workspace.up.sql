CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS workspaces (
    id          UUID          PRIMARY KEY DEFAULT uuid_generate_v4(),
    name        VARCHAR(255)  NOT NULL,
    owner_id    UUID          NOT NULL, -- REFERENCES users(id) -- Adding constraint after users table migration check
    state       VARCHAR(50)   NOT NULL DEFAULT 'active',
    created_at  TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS user_workspaces (
    user_id      UUID NOT NULL, -- REFERENCES users(id)
    workspace_id UUID NOT NULL REFERENCES workspaces(id) ON DELETE CASCADE,
    role         VARCHAR(50) NOT NULL DEFAULT 'member', -- member, admin, etc.
    PRIMARY KEY (user_id, workspace_id)
);

-- We add the FK to owner_id after users is ensured to exist in previous migrations
ALTER TABLE workspaces ADD CONSTRAINT fk_workspaces_owner FOREIGN KEY (owner_id) REFERENCES users(id);
ALTER TABLE user_workspaces ADD CONSTRAINT fk_user_workspaces_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Create a default workspace for the existing admin user
DO $$
DECLARE
    admin_id UUID;
    -- Deterministic System Default Workspace UUID
    default_ws_id UUID := '00000000-0000-4000-a000-000000000001';
BEGIN
    SELECT id INTO admin_id FROM users WHERE login = 'admin' LIMIT 1;

    IF admin_id IS NOT NULL THEN
        INSERT INTO workspaces (id, name, owner_id)
        VALUES (default_ws_id, 'Default Workspace', admin_id)
        ON CONFLICT (id) DO NOTHING;

        INSERT INTO user_workspaces (user_id, workspace_id, role)
        VALUES (admin_id, default_ws_id, 'owner')
        ON CONFLICT (user_id, workspace_id) DO NOTHING;
    END IF;
END $$;
