# Codebase Structure

**Analysis Date:** 2026-04-03

## Directory Layout

```
zai-tds/
├── cmd/                # Application entry points
│   └── zai-tds/        # Main TDS server binary
├── db/                 # Database migrations and schemas
│   ├── clickhouse/     # Analytics storage (ClickHouse)
│   └── postgres/       # Configuration storage (PostgreSQL)
├── internal/           # Private business logic
│   ├── action/         # Post-click redirect/content handlers (Redirect, Proxy, etc.)
│   ├── binding/        # Visitor-to-stream/offer pinning (stickiness)
│   ├── cache/          # Valkey (Redis) caching layer with Postgres fallbacks
│   ├── config/         # YAML/Env configuration loader
│   ├── cookie/         # HTTP cookie management
│   ├── device/         # UA parsing and device detection
│   ├── filter/         # Traffic filtering rules (Geo, Bot, ISP, etc.)
│   ├── geo/            # GeoIP lookup service (MaxMind)
│   ├── hitlimit/       # Stream-level traffic capping (daily/total)
│   ├── lptoken/        # Landing page click token management (L1 -> L2 linking)
│   ├── macro/          # URL parameter/token replacement logic
│   ├── model/          # Shared domain entities (Campaign, Stream, Click)
│   ├── pipeline/       # Sequential processing engine
│   │   └── stage/      # Individual pipeline step implementations (numbered)
│   ├── queue/          # Async ClickHouse batch writer
│   ├── rotator/        # Weighted/sequential rotation logic
│   ├── server/         # HTTP router (chi) and server bootstrap
│   ├── session/        # Visitor session and uniqueness tracking
│   └── worker/         # Background management tasks (cleanup, warmup)
├── test/               # Test suites
│   ├── integration/    # End-to-end flow tests (TDS routes)
│   └── unit/           # Isolated package tests
└── config.yaml         # Main configuration file
```

## Directory Purposes

**internal/pipeline/stage/:**
- Purpose: The core logic of the TDS. Each file represents a single step in the click processing chain.
- Contains: Filter checks, campaign resolution, enrichment, and storage triggers.
- Key files: `internal/pipeline/stage/9_choose_stream.go`, `internal/pipeline/stage/20_execute_action.go`, `internal/pipeline/stage/23_store_raw_clicks.go`

**internal/model/:**
- Purpose: Defines the shared data structures used throughout the system.
- Contains: Structs for Campaigns, Streams, Offers, Landings, and the enriched RawClick.
- Key files: `internal/model/models.go`

**internal/action/:**
- Purpose: Implements the final "delivery" of the visitor.
- Contains: Logic for various redirect methods (302, JS, Meta) and content delivery (Iframe, Proxy).
- Key files: `internal/action/action.go` (registry), `internal/action/redirect.go`

**internal/filter/:**
- Purpose: Traffic validation and bot detection.
- Contains: Logic for 27+ different filter types used to qualify traffic for streams.
- Key files: `internal/filter/filter.go` (engine), `internal/filter/geo.go`, `internal/filter/device.go`

## Key File Locations

**Entry Points:**
- `cmd/zai-tds/main.go`: Server bootstrap, config loading, and lifecycle management.

**Configuration:**
- `internal/config/config.go`: Mapping YAML/Env to internal configuration structs.

**Core Logic:**
- `internal/pipeline/pipeline.go`: The orchestration engine for click requests.
- `internal/server/routes.go`: HTTP route definitions (Hot Path `/`, `/{alias}` vs. Admin `/api/v1/health`).
- `internal/cache/cache.go`: High-performance entity caching (Valkey + Postgres).

**Testing:**
- `test/integration/routing_test.go`: End-to-end validation of campaign routing and redirects.

## Naming Conventions

**Files:**
- [snake_case]: `choose_stream.go`, `raw_click.go`.
- [numbered_stages]: Files in `internal/pipeline/stage/` are prefixed with a number (e.g., `1_domain_redirect.go`) to indicate their order in the pipeline.

**Directories:**
- [lowercase, single-word]: `model`, `server`, `action`, `geo`.

## Where to Add New Code

**New Feature (TDS Path):**
- Primary code: A new numbered stage in `internal/pipeline/stage/`.
- Tests: Add unit tests in `test/unit/` and integration cases in `test/integration/`.

**New Redirect Method:**
- Implementation: A new struct in `internal/action/` implementing the `Action` interface.
- Registration: Register the action in the `NewEngine()` function in `internal/action/action.go`.

**New Filtering Rule:**
- Implementation: A new struct in `internal/filter/` implementing the `Filter` interface.
- Registration: Register the filter in the `NewEngine()` function in `internal/filter/filter.go`.

**New Data Field (Analytics):**
- Implementation: Update `RawClick` in `internal/model/models.go` and `ClickRecord` in `internal/queue/writer.go`.
- Migration: Add a ClickHouse migration in `db/clickhouse/migrations/`.

## Special Directories

**db/*/migrations/:**
- Purpose: SQL files for database schema versioning (Postgres for entities, ClickHouse for clicks).
- Generated: No.
- Committed: Yes.

**.gsd/:**
- Purpose: Contains AI agent planning artifacts (milestones, phases, decisions).
- Generated: No.
- Committed: Yes.

---

*Structure analysis: 2026-04-03*
