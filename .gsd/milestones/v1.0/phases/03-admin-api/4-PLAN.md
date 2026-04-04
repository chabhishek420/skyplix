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
    1. Register repositories:
       - Update `internal/admin/handler/handler.go` struct to include `trafficSources`, `domains`
       - Update `NewHandler` to initialize them.

    2. Traffic Sources CRUD (6 endpoints — AUDIT FIX #4, was 5, missing clone):
       - GET /traffic_sources — list
       - GET /traffic_sources/:id — show
       - POST /traffic_sources — create (name, postback_url, params JSONB)
       - PUT /traffic_sources/:id — update
       - DELETE /traffic_sources/:id — archive
       - POST /traffic_sources/:id/clone — clone

       The `params` field is JSONB storing parameter name→token mappings.
       Example: `{"cost": "{cost}", "keyword": "{keyword}"}`
       Validation: name required

    2. Domains CRUD (9 endpoints — AUDIT FIX #4, was 5, missing 4 routes):
       - GET /domains — list
       - GET /domains/:id — show
       - POST /domains — create (domain, campaign_id)
       - PUT /domains/:id — update
       - DELETE /domains/:id — archive (set state = 'archived')
       - GET /domains/deleted — list archived domains
       - POST /domains/:id/restore — restore from archive
       - POST /domains/:id/clone — clone domain
       - POST /domains/:id/check — trigger DNS validation (stub: sets status, not real DNS check)

       Validation: domain required, unique, campaign_id must exist

    All mutations call ScheduleWarmup().

    IMPORTANT: Domain cache invalidation is critical — domain→campaign mapping
    is used on the hot path by DomainRedirectStage. Must warmup after domain mutations.
    IMPORTANT: `/deleted` route must come BEFORE `/{id}` in Chi to avoid wildcard collision.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - 15 endpoints for traffic sources (6) + domains (9)
    - JSONB params field properly handled
    - Domain changes trigger cache warmup
    - Clone endpoints copy all fields except id/timestamps, append " (copy)" to name
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
    1. Register repositories:
       - Update `internal/admin/handler/handler.go` struct to include `users`, `settings`
       - Update `NewHandler` to initialize them.

    2. Create settings migration:
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

    2. Users CRUD (6 endpoints — AUDIT FIX #4, was 5, missing access-data):
       - GET /users — list (never return password_hash or api_key)
       - GET /users/:id — show
       - POST /users — create (login, password, role)
         Password hashed with pgcrypto's crypt() + gen_salt('bf')
       - PUT /users/:id — update (login, role, password optional)
       - DELETE /users/:id — deactivate (state = 'disabled')
       - PUT /users/:id/access — update access data (password + api_key regeneration)

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
- [ ] Traffic Sources: 6 endpoints with JSONB params (including clone)
- [ ] Domains: 9 endpoints with campaign linking (including deleted/restore/clone/check)
- [ ] Users: 6 endpoints with secure password handling (including access-data)
- [ ] Settings: 2 endpoints (GET all, PUT bulk upsert)
- [ ] Migration 006 creates settings table with default values
