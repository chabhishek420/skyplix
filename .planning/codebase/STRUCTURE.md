# Codebase Structure

**Analysis Date:** 2026-04-03

## Directory Layout

```
[zai-yt-keitaro]/
├── admin-ui/              # (stub) admin UI area (no implementation files currently)
├── cmd/                   # Go entrypoints/binaries
│   └── zai-tds/            # Main server binary
├── data/                  # Local data assets (GeoIP DB files expected under `data/geoip/`)
├── db/                    # SQL schema/migrations
├── docs/                  # Project documentation
├── internal/              # Go application packages (business logic)
├── reference/             # Reference/legacy material (e.g. prior Next.js/Keitaro parity work)
├── test/                  # Unit/integration/benchmark tests
├── .gsd-source/           # GSD tooling sources (node packages)
├── .planning/             # GSD planning artifacts (created by Codex)
├── docker-compose.yml     # Local Postgres/Valkey/ClickHouse
├── config.yaml            # Runtime configuration (YAML)
├── go.mod                 # Go module manifest
├── go.sum                 # Go dependency lock
└── events.jsonl           # Runtime/artifact data (project-specific)
```

## Directory Purposes

**cmd/:**
- Purpose: executable entry points.
- Key files: `cmd/zai-tds/main.go`

**internal/:**
- Purpose: application logic grouped by domain.
- Key subdirectories (examples):
  - `internal/server/` — HTTP server wiring + routes
  - `internal/pipeline/` — pipeline engine
  - `internal/pipeline/stage/` — pipeline stages (click processing)
  - `internal/admin/` — admin middleware + handler layer
  - `internal/action/` — post-click actions (redirect/proxy/cloaking)
  - `internal/cache/`, `internal/session/` — runtime state
  - `internal/queue/` — ClickHouse async writer
  - `internal/worker/` — background workers

**db/:**
- Purpose: database schemas/migrations for Postgres/ClickHouse (project-defined).

**test/:**
- Purpose: automated tests.
- Structure:
  - `test/unit/` — unit tests
  - `test/integration/` — integration tests (require services)
  - `test/benchmark/` — latency benchmarks (integration-tagged)

**reference/:**
- Purpose: legacy/reference code and prior work; not part of the Go runtime.
- Notable: `reference/legacy-nextjs/` contains previous planning/docs.

**.planning/:**
- Purpose: GSD planning state.
- Current: `.planning/codebase/*.md` (this mapping output)

## Key File Locations

**Entry Points:**
- `cmd/zai-tds/main.go` — process bootstrap

**Routing / HTTP:**
- `internal/server/routes.go` — routes + middleware
- `internal/server/server.go` — dependency wiring + server lifecycle

**Configuration:**
- `config.yaml` — default runtime config
- `internal/config/config.go` — config loading + env overrides
- `docker-compose.yml` — local dependencies

**Core Logic:**
- `internal/pipeline/` — pipeline runtime
- `internal/pipeline/stage/` — stage implementations
- `internal/action/` — action implementations

**Data Access:**
- `internal/admin/handler/` — admin CRUD handlers (Postgres)
- `internal/cache/` — cache layer (Valkey + Postgres)
- `internal/queue/writer.go` — ClickHouse writer

**Testing:**
- `test/unit/queue/writer_test.go` — example unit tests
- `test/integration/suite_test.go` — integration suite setup

## Naming Conventions

**Go packages:**
- Directory names are lowercase single words (e.g. `internal/ratelimit`, `internal/hitlimit`).

**Go files:**
- Lowercase with underscores when needed (e.g. `routes.go`, `server.go`, `middleware.go`).

**Tests:**
- `*_test.go` (e.g. `test/unit/queue/writer_test.go`).

## Where to Add New Code

**New click pipeline stage:**
- Implementation: `internal/pipeline/stage/`
- Wiring into pipeline: `internal/server/server.go`
- Tests: `test/unit/` for pure logic; `test/integration/` when DB/Valkey needed

**New admin endpoint:**
- Route wiring: `internal/server/routes.go`
- Handler: `internal/admin/handler/`
- Tests: `test/integration/admin_test.go` style

**New background task:**
- Worker implementation: `internal/worker/`
- Registration: `internal/server/server.go`

---

*Structure analysis: 2026-04-03*
*Update when directory structure changes*
