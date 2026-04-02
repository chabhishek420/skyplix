# Codebase Structure

**Analysis Date:** 2026-04-02

## Directory Layout

```
zai-yt-keitaro/
├── cmd/                    # Application entry points
│   └── zai-tds/
│       └── main.go        # Main function
├── internal/               # Private application code
│   ├── action/            # Action executors (stub)
│   ├── admin/              # Admin API (stub)
│   ├── auth/               # Authentication (stub)
│   ├── cache/              # Caching utilities (stub)
│   ├── config/             # Configuration loading
│   ├── cookie/             # Cookie handling (stub)
│   ├── device/             # Device/UA detection
│   ├── filter/             # Campaign filters (stub)
│   ├── geo/                # GeoIP resolution
│   ├── hitlimit/           # Hit limiting (stub)
│   ├── lptoken/            # Landing page tokens (stub)
│   ├── macro/              # Macro processing (stub)
│   ├── metrics/            # Metrics collection (stub)
│   ├── model/              # Domain models
│   ├── pipeline/           # Click processing pipeline
│   │   ├── pipeline.go     # Pipeline runner
│   │   └── stage/          # Pipeline stages
│   ├── queue/              # Async ClickHouse writer
│   ├── rotator/            # Offer/stream rotation (stub)
│   ├── server/             # HTTP server & routes
│   ├── session/             # Session management (stub)
│   ├── valkey/             # Valkey/Redis utilities (stub)
│   └── worker/             # Background workers
├── db/                     # Database schema
│   ├── postgres/
│   │   ├── migrations/     # SQL migrations
│   │   └── queries/        # Query files (not used - inline queries)
│   └── clickhouse/         # ClickHouse schema (reference)
├── admin-ui/               # Admin frontend (stub - likely Next.js)
│   └── src/
├── test/                   # Test files
│   └── integration/        # Integration tests
├── reference/              # Reference implementations
│   ├── legacy-nextjs/      # Original Next.js TDS (reference only)
│   └── pp_adsensor/        # Other reference code
├── .planning/              # Planning documents (GSD)
├── .claude/               # Claude configuration
├── .gsd/                  # GSD state
├── AGENTS.md              # Agent instructions
├── go.mod                 # Go module definition
├── go.sum                 # Go dependencies lock
├── config.yaml            # Configuration file
├── docker-compose.yml     # Local development services
└── project_status.md      # Project status
```

## Directory Purposes

**`cmd/zai-tds/`:**
- Purpose: Application entry point
- Contains: `main.go`
- Key files: `main.go` - initializes config, logger, server

**`internal/server/`:**
- Purpose: HTTP server and routing
- Contains: `server.go` (lifecycle), `routes.go` (handlers)
- Key files: `routes.go` - wires chi router, instantiates pipeline

**`internal/pipeline/`:**
- Purpose: Core click processing orchestration
- Contains: `pipeline.go` (Stage interface, Payload, Runner)
- Key files: `pipeline.go`, `stage/` directory

**`internal/pipeline/stage/`:**
- Purpose: Individual processing steps
- Contains: 10 stage implementations + noop.go
- Key files: Named by stage number: `4_find_campaign.go`, `20_execute_action.go`

**`internal/model/`:**
- Purpose: Domain models mirroring Keitaro
- Contains: `models.go` - RawClick, Campaign, Stream, Offer, Landing
- Key files: `models.go`

**`internal/config/`:**
- Purpose: Configuration loading and validation
- Contains: `config.go`
- Key files: `config.go` - YAML load + env var overrides

**`internal/geo/`:**
- Purpose: GeoIP resolution
- Contains: `geo.go`
- Key files: `geo.go` - wraps geoip2-golang

**`internal/device/`:**
- Purpose: User-Agent parsing and device detection
- Contains: `detector.go`
- Key files: `detector.go` - wraps mileusna/useragent

**`internal/queue/`:**
- Purpose: Async ClickHouse batch writing
- Contains: `writer.go`
- Key files: `writer.go` - buffered channel + batch flush

**`internal/worker/`:**
- Purpose: Background task management
- Contains: `worker.go`, `cache_warmup.go`, `hitlimit_reset.go`
- Key files: `worker.go` - Worker interface and Manager

**`db/postgres/migrations/`:**
- Purpose: PostgreSQL schema migrations
- Contains: 4 migration files (001-004)
- Key files: `001_create_campaigns.up.sql`, `002_create_streams.up.sql`

**`db/clickhouse/`:**
- Purpose: ClickHouse schema reference
- Contains: Schema definitions (referenced in comments)

## Key File Locations

**Entry Points:**
- `cmd/zai-tds/main.go`: Application bootstrap
- `internal/server/server.go`: Server initialization
- `internal/server/routes.go`: HTTP handler registration

**Configuration:**
- `config.yaml`: YAML configuration
- `internal/config/config.go`: Config loader

**Core Logic:**
- `internal/pipeline/pipeline.go`: Pipeline runner
- `internal/pipeline/stage/*.go`: Stage implementations
- `internal/model/models.go`: Domain models

**Data Storage:**
- `db/postgres/migrations/*.sql`: PostgreSQL schema
- `internal/queue/writer.go`: ClickHouse writer
- `internal/server/server.go`: DB connection setup

**Testing:**
- `test/integration/click_test.go`: End-to-end integration test

## Naming Conventions

**Files:**
- Go source: `lowercase.go` (e.g., `detector.go`, `pipeline.go`)
- Stage files: Include stage number: `4_find_campaign.go`, `20_execute_action.go`
- Migrations: `XXX_name.up.sql`, `XXX_name.down.sql`

**Directories:**
- All lowercase with underscores: `internal/server`, `internal/pipeline/stage`
- Package directories plural: `internal/pipeline/stage` (stages), not `internal/pipeline/stages`

**Types:**
- Structs: PascalCase: `Server`, `Pipeline`, `FindCampaignStage`
- Interfaces: PascalCase with "er" suffix optional: `Stage`, `Worker`
- Struct fields: PascalCase (exported), camelCase (unexported)

**Variables:**
- Local vars: camelCase: `clickChan`, `abortCode`
- Acronyms: lowercase: `IP`, `UA`, `DB`, `ID`
- Constants: PascalCase: `CampaignTypePosition`, `StreamTypeRegular`

**Functions:**
- Methods: PascalCase: `Process()`, `Name()`, `AlwaysRun()`
- Constructors: `New()`, `NewWriter()`, `NewNoOp()`
- Helpers: camelCase: `extractRealIP()`, `detectBot()`

## Where to Add New Code

**New Pipeline Stage:**
1. Create `internal/pipeline/stage/NN_name.go` where NN is the stage number
2. Implement `Stage` interface with `Process()`, `Name()`, `AlwaysRun()`
3. Inject dependencies via struct fields
4. Register in `routes.go` `handleClick()` pipeline construction

**New Background Worker:**
1. Create `internal/worker/worker_name.go`
2. Implement `Worker` interface with `Name()`, `Run()`
3. Register in `server.go` `Run()` worker slice

**New Domain Model:**
1. Add to `internal/model/models.go`
2. Mirror Keitaro structure for compatibility
3. Add corresponding PostgreSQL migration if needed

**New HTTP Endpoint:**
1. Add handler method to `internal/server/server.go`
2. Register route in `routes.go` `routes()` function
3. Use chi router: `r.Get()`, `r.Post()`, etc.

**Database Migration:**
1. Create `db/postgres/migrations/XXX_name.up.sql`
2. Create corresponding `XXX_name.down.sql`
3. Use sequential numbering (005, 006, etc.)

## Special Directories

**`reference/`:**
- Purpose: Reference implementations for migration context
- Contains: `legacy-nextjs/` (original TDS), `pp_adsensor/` (ad server reference)
- Generated: No
- Committed: Yes
- Usage: Context for understanding Keitaro compatibility

**`admin-ui/`:**
- Purpose: Admin interface (stub)
- Contains: Empty `src/` directory
- Generated: No
- Committed: Yes
- Status: Placeholder for future admin UI

**`.planning/`:**
- Purpose: GSD planning documents
- Contains: Phase plans, context documents
- Generated: Yes (by GSD commands)
- Committed: Yes

**`.claude/`, `.gsd/`:**
- Purpose: Claude Code configuration and state
- Generated: Yes
- Committed: Yes

---

*Structure analysis: 2026-04-02*
