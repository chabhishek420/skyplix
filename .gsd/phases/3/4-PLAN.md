---
phase: 3
plan: 4
wave: 2
---

# Plan 3.4: Traffic Sources, Domains, Users, Settings CRUD

## Objective
Complete the P1 entity CRUD surface: Traffic Sources (with parameter templating),
Domains (domain→campaign mapping), Users (admin auth management), and
Settings (system-wide config key-value store).

## Context
- .gsd/phases/3/3-PLAN.md — Offers/Landings/Networks (must be complete)
- internal/model/models.go — TrafficSource struct (with JSONB Params)
- db/postgres/migrations/004_create_domains_users.up.sql — domains, users, traffic_sources tables
- reference/Keitaro_source_php/application/Component/Settings/Initializer.php

## Tasks

<task type="auto">
  <name>Traffic Sources + Domains repositories and handlers</name>
  <files>
    internal/admin/repository/traffic_sources.go (NEW)
    internal/admin/repository/domains.go (NEW)
    internal/admin/handler/traffic_sources.go (NEW)
    internal/admin/handler/domains.go (NEW)
    internal/server/routes.go (MODIFY)
  </files>
  <action>
    1. Traffic Sources CRUD (5 endpoints):
       - GET /traffic_sources — list
       - GET /traffic_sources/:id — show
       - POST /traffic_sources — create (name, postback_url, params JSONB)
       - PUT /traffic_sources/:id — update
       - DELETE /traffic_sources/:id — archive

       The `params` field is JSONB storing parameter name→token mappings.
       Example: `{"cost": "{cost}", "keyword": "{keyword}"}`
       Validation: name required

    2. Domains CRUD (5 endpoints):
       - GET /domains — list
       - GET /domains/:id — show
       - POST /domains — create (domain, campaign_id)
       - PUT /domains/:id — update
       - DELETE /domains/:id — archive (set state = 'archived')

       Validation: domain required, unique, campaign_id must exist

    All mutations call ScheduleWarmup().

    IMPORTANT: Domain cache invalidation is critical — domain→campaign mapping
    is used on the hot path by DomainRedirectStage. Must warmup after domain mutations.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - 10 endpoints for traffic sources + domains
    - JSONB params field properly handled
    - Domain changes trigger cache warmup
  </done>
</task>

<task type="auto">
  <name>Users + Settings repositories and handlers</name>
  <files>
    internal/admin/repository/users.go (NEW)
    internal/admin/repository/settings.go (NEW)
    internal/admin/handler/users.go (NEW)
    internal/admin/handler/settings.go (NEW)
    db/postgres/migrations/006_create_settings.up.sql (NEW)
    db/postgres/migrations/006_create_settings.down.sql (NEW)
    internal/server/routes.go (MODIFY)
  </files>
  <action>
    1. Create settings migration:
       ```sql
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
       ```

    2. Users CRUD (5 endpoints):
       - GET /users — list (never return password_hash or api_key)
       - GET /users/:id — show
       - POST /users — create (login, password, role)
         Password hashed with pgcrypto's crypt() + gen_salt('bf')
       - PUT /users/:id — update (login, role, password optional)
       - DELETE /users/:id — deactivate (state = 'disabled')

       SECURITY: Never expose password_hash in JSON responses.
       SECURITY: Password updates use pgcrypto bcrypt.
       SECURITY: Only admin role can manage users.

    3. Settings CRUD (2 endpoints):
       - GET /settings — return all settings as JSON object
       - PUT /settings — bulk upsert: receives JSON object, updates each key

       ```go
       // GET response: {"system.name": "SkyPlix TDS", ...}
       // PUT body: {"system.name": "My Tracker", "system.timezone": "US/Eastern"}
       ```

    Wire all routes in routes.go under the auth group.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - 7 endpoints for users + settings
    - Settings table created with default values
    - Password hashing via pgcrypto
    - User responses exclude password_hash and api_key fields
  </done>
</task>

## Success Criteria
- [ ] `go build ./...` succeeds
- [ ] Traffic Sources: 5 endpoints with JSONB params
- [ ] Domains: 5 endpoints with campaign linking
- [ ] Users: 5 endpoints with secure password handling
- [ ] Settings: 2 endpoints (GET all, PUT bulk upsert)
- [ ] Migration 006 creates settings table with default values
