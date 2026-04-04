---
phase: 1
plan: 1
wave: 1
---

# Plan 1.1: Go Project Scaffold + Docker Compose + Database Schemas

## Objective
Bootstrap the Go project with the correct module structure, set up the three-service Docker Compose environment (PostgreSQL, Valkey, ClickHouse), define and apply all core database migrations for Phase 1 entities, and ensure `go build` produces a valid binary. This is the foundation every subsequent plan builds on.

## Context
- .gsd/SPEC.md — Technical spec (FINALIZED)
- .gsd/ARCHITECTURE.md — Target Go project structure (lines 81–128)
- .gsd/STACK.md — All dependency versions and rationale
- .gsd/DECISIONS.md — ADR-002 (DB split), ADR-003 (Chi), ADR-004 (sqlc+pgx)

## Tasks

<task type="auto">
  <name>Scaffold Go module and project directory structure</name>
  <files>
    go.mod
    go.sum
    cmd/zai-tds/main.go
    internal/server/server.go
    internal/config/config.go
    config.yaml
    .gitignore (update)
  </files>
  <action>
    1. Run: `go mod init github.com/skyplix/zai-tds`
    2. Create directory tree exactly as documented in ARCHITECTURE.md (lines 81-128):
       cmd/zai-tds/, internal/server/, internal/pipeline/, internal/pipeline/stage/,
       internal/action/, internal/model/, internal/cache/, internal/queue/,
       internal/filter/, internal/rotator/, internal/geo/, internal/device/,
       internal/macro/, internal/session/, internal/hitlimit/, internal/cookie/,
       internal/lptoken/, internal/auth/, internal/admin/, internal/config/,
       internal/metrics/, internal/valkey/, db/postgres/queries/,
       db/postgres/migrations/, db/clickhouse/migrations/
    3. Create `config.yaml` with all keys from STACK.md (lines 153-165):
       server.host, server.port, postgres.dsn, valkey.addr, clickhouse.addr,
       geoip.country_db, geoip.city_db, geoip.asn_db, system.salt, system.debug, system.log_level
    4. Create `internal/config/config.go` — struct matching config.yaml, uses `gopkg.in/yaml.v3`,
       loads file then overrides with os.Getenv. No global state — return *Config.
    5. Create `cmd/zai-tds/main.go` — minimal: load config, print "ZAI TDS starting", os.Exit(0).
       No server yet — just proves the binary compiles.
    6. Create `internal/server/server.go` — empty Server struct with New() and Run() stubs
       that return nil. Will be filled in Plan 1.2.
    7. Add to .gitignore: `zai-tds` binary, `*.mmdb`, `config.local.yaml`

    DO NOT use `init()` functions — leads to hidden initialization order bugs.
    DO NOT use global `var` for config — pass *Config explicitly.
    DO NOT add chi or any HTTP deps yet — main.go just proves compile.
  </action>
  <verify>cd /Users/roshansharma/Desktop/zai-yt-keitaro && go build ./... 2>&1 | head -20 && echo "BUILD OK"</verify>
  <done>
    - `go build ./...` exits 0 with "BUILD OK"
    - All directories from ARCHITECTURE.md exist (verify with `find . -type d | grep internal`)
    - config.yaml exists with all required keys
    - go.mod has module `github.com/skyplix/zai-tds` and `go 1.23`
  </done>
</task>

<task type="auto">
  <name>Docker Compose: PostgreSQL + Valkey + ClickHouse</name>
  <files>
    docker-compose.yml
    docker-compose.override.yml (dev overrides)
  </files>
  <action>
    Create `docker-compose.yml` with three services matching STACK.md (lines 124-132):

    1. **postgres** — image: `postgres:16-alpine`
       - POSTGRES_DB: zai_tds
       - POSTGRES_USER: zai
       - POSTGRES_PASSWORD: zai_dev_pass
       - Port: 5432:5432
       - Volume: postgres_data:/var/lib/postgresql/data
       - Healthcheck: `pg_isready -U zai -d zai_tds` every 5s, 10 retries

    2. **valkey** — image: `valkey/valkey:8-alpine`
       - Port: 6379:6379
       - Volume: valkey_data:/data
       - Command: `valkey-server --save 60 1 --loglevel warning`
       - Healthcheck: `valkey-cli ping` every 5s

    3. **clickhouse** — image: `clickhouse/clickhouse-server:24-alpine`
       - Port: 8123:8123 (HTTP), 9000:9000 (native)
       - Volume: clickhouse_data:/var/lib/clickhouse
       - Environment: CLICKHOUSE_DB=zai_analytics, CLICKHOUSE_USER=default, CLICKHOUSE_DEFAULT_ACCESS_MANAGEMENT=1
       - Healthcheck: `wget -qO- http://localhost:8123/ping` every 10s

    4. Add named volumes: postgres_data, valkey_data, clickhouse_data

    DO NOT use `latest` tags — locks to specific major versions as documented.
    DO NOT expose ClickHouse on 0.0.0.0 in production config — bind to 127.0.0.1 for dev.
  </action>
  <verify>cd /Users/roshansharma/Desktop/zai-yt-keitaro && docker compose config --quiet && echo "COMPOSE CONFIG OK" && docker compose up -d --wait 2>&1 | tail -5</verify>
  <done>
    - `docker compose config --quiet` exits 0
    - `docker compose up -d --wait` starts all 3 services
    - `docker compose ps` shows all 3 services as "healthy"
    - `docker compose exec postgres pg_isready -U zai` exits 0
    - `docker compose exec valkey valkey-cli ping` returns PONG
    - `wget -qO- http://localhost:8123/ping` returns "Ok."
  </done>
</task>

<task type="auto">
  <name>PostgreSQL migrations: core entity schema</name>
  <files>
    db/postgres/migrations/001_create_campaigns.up.sql
    db/postgres/migrations/001_create_campaigns.down.sql
    db/postgres/migrations/002_create_streams.up.sql
    db/postgres/migrations/002_create_streams.down.sql
    db/postgres/migrations/003_create_offers_landings.up.sql
    db/postgres/migrations/003_create_offers_landings.down.sql
    db/postgres/migrations/004_create_domains_users.up.sql
    db/postgres/migrations/004_create_domains_users.down.sql
    db/clickhouse/migrations/001_create_clicks.sql
    db/clickhouse/migrations/002_create_conversions.sql
  </files>
  <action>
    Create SQL migration files matching ARCHITECTURE.md (lines 399-427).

    **001_create_campaigns.up.sql:**
    ```sql
    CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
    CREATE TYPE campaign_type AS ENUM ('POSITION', 'WEIGHT');
    CREATE TABLE campaigns (
      id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
      alias VARCHAR(255) UNIQUE NOT NULL,
      name VARCHAR(255) NOT NULL,
      type campaign_type NOT NULL DEFAULT 'POSITION',
      bind_visitors BOOLEAN NOT NULL DEFAULT false,
      state VARCHAR(50) NOT NULL DEFAULT 'active',
      traffic_source_id UUID,
      default_stream_id UUID,
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );
    CREATE INDEX idx_campaigns_alias ON campaigns(alias);
    CREATE INDEX idx_campaigns_state ON campaigns(state);
    ```

    **002_create_streams.up.sql:**
    ```sql
    CREATE TYPE stream_type AS ENUM ('REGULAR', 'FORCED', 'DEFAULT');
    CREATE TABLE streams (
      id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
      campaign_id UUID NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
      name VARCHAR(255) NOT NULL,
      type stream_type NOT NULL DEFAULT 'REGULAR',
      position INT NOT NULL DEFAULT 0,
      weight INT NOT NULL DEFAULT 100,
      state VARCHAR(50) NOT NULL DEFAULT 'active',
      action_type VARCHAR(100) NOT NULL DEFAULT 'HttpRedirect',
      action_payload JSONB NOT NULL DEFAULT '{}',
      filters JSONB NOT NULL DEFAULT '[]',
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );
    CREATE INDEX idx_streams_campaign_id ON streams(campaign_id);
    -- Association tables with weights (from ARCHITECTURE.md)
    CREATE TABLE stream_landings (
      stream_id UUID NOT NULL REFERENCES streams(id) ON DELETE CASCADE,
      landing_id UUID NOT NULL,
      weight INT NOT NULL DEFAULT 100,
      PRIMARY KEY (stream_id, landing_id)
    );
    CREATE TABLE stream_offers (
      stream_id UUID NOT NULL REFERENCES streams(id) ON DELETE CASCADE,
      offer_id UUID NOT NULL,
      weight INT NOT NULL DEFAULT 100,
      PRIMARY KEY (stream_id, offer_id)
    );
    ```

    **003_create_offers_landings.up.sql:**
    ```sql
    CREATE TABLE landings (
      id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
      name VARCHAR(255) NOT NULL,
      url TEXT NOT NULL,
      state VARCHAR(50) NOT NULL DEFAULT 'active',
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );
    CREATE TABLE offers (
      id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
      name VARCHAR(255) NOT NULL,
      url TEXT NOT NULL,
      affiliate_network_id UUID,
      payout DECIMAL(10,4) NOT NULL DEFAULT 0,
      state VARCHAR(50) NOT NULL DEFAULT 'active',
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );
    ```

    **004_create_domains_users.up.sql:**
    ```sql
    CREATE TABLE domains (
      id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
      domain VARCHAR(255) UNIQUE NOT NULL,
      campaign_id UUID REFERENCES campaigns(id),
      state VARCHAR(50) NOT NULL DEFAULT 'active',
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );
    CREATE TABLE users (
      id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
      login VARCHAR(255) UNIQUE NOT NULL,
      password_hash VARCHAR(255) NOT NULL,
      role VARCHAR(50) NOT NULL DEFAULT 'user',
      state VARCHAR(50) NOT NULL DEFAULT 'active',
      created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    );
    -- Default admin user (password: admin123 — MUST change in production)
    INSERT INTO users (login, password_hash, role) VALUES
      ('admin', crypt('admin123', gen_salt('bf')), 'administrator');
    ```
    Note: 004 needs `pgcrypto` extension — add `CREATE EXTENSION IF NOT EXISTS pgcrypto;` at top.

    **ClickHouse 001_create_clicks.sql:**
    ```sql
    CREATE TABLE IF NOT EXISTS clicks (
      click_id UUID DEFAULT generateUUIDv4(),
      created_at DateTime64(3, 'UTC'),
      campaign_id UUID,
      campaign_alias String,
      stream_id UUID,
      offer_id UUID,
      landing_id UUID,
      ip IPv6,
      country_code FixedString(2),
      city String,
      isp String,
      device_type String,
      device_model String,
      os String,
      os_version String,
      browser String,
      browser_version String,
      user_agent String,
      referrer String,
      is_bot UInt8 DEFAULT 0,
      is_unique_campaign UInt8 DEFAULT 0,
      is_unique_stream UInt8 DEFAULT 0,
      sub_id_1 String,
      sub_id_2 String,
      sub_id_3 String,
      sub_id_4 String,
      sub_id_5 String,
      cost Decimal(10, 4) DEFAULT 0,
      payout Decimal(10, 4) DEFAULT 0,
      action_type String,
      click_token String
    ) ENGINE = MergeTree()
    PARTITION BY toYYYYMM(created_at)
    ORDER BY (campaign_id, created_at)
    SETTINGS index_granularity = 8192;
    ```

    **ClickHouse 002_create_conversions.sql:**
    ```sql
    CREATE TABLE IF NOT EXISTS conversions (
      conversion_id UUID DEFAULT generateUUIDv4(),
      created_at DateTime64(3, 'UTC'),
      click_token String,
      affiliate_network_id UUID,
      status String DEFAULT 'lead',
      payout Decimal(10, 4) DEFAULT 0,
      revenue Decimal(10, 4) DEFAULT 0
    ) ENGINE = MergeTree()
    ORDER BY (created_at, click_token);
    ```

    Write all .down.sql files to DROP the tables/types in reverse order.

    DO NOT add golang-migrate runner yet — that's Part of the server startup in Plan 1.2.
    DO NOT create a `settings` table yet — out of Phase 1 scope.
  </action>
  <verify>
    # Apply migrations manually to verify SQL is valid
    docker compose exec -T postgres psql -U zai -d zai_tds -f /dev/stdin < db/postgres/migrations/001_create_campaigns.up.sql && \
    docker compose exec -T postgres psql -U zai -d zai_tds -f /dev/stdin < db/postgres/migrations/002_create_streams.up.sql && \
    docker compose exec -T postgres psql -U zai -d zai_tds -c "\dt" | grep campaigns && echo "PG SCHEMA OK" && \
    curl -s "http://localhost:8123/" --data-binary @db/clickhouse/migrations/001_create_clicks.sql && echo "CH SCHEMA OK"
  </verify>
  <done>
    - All PostgreSQL migration files are valid SQL (no syntax errors)
    - `\dt` in postgres shows: campaigns, streams, stream_landings, stream_offers, landings, offers, domains, users
    - ClickHouse clicks table created successfully
    - All .down.sql files exist and correctly reverse the .up.sql
  </done>
</task>

## Success Criteria
- [ ] `go build ./...` exits 0 — binary compiles cleanly
- [ ] `docker compose up -d --wait` starts all 3 services with healthy status
- [ ] PostgreSQL has 8 tables: campaigns, streams, stream_landings, stream_offers, landings, offers, domains, users
- [ ] ClickHouse has clicks and conversions tables
- [ ] All migration files have matching .up.sql and .down.sql pairs
- [ ] config.yaml has all required keys from STACK.md
