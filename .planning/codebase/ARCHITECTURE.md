# Architecture

**Analysis Date:** 2026-04-03

## Pattern Overview

**Overall:** Monolithic Go HTTP service with a pipeline-based click-processing engine.

**Key Characteristics:**
- Single backend executable (`cmd/zai-tds/main.go`) serving both admin APIs and click/redirect endpoints
- Click handling implemented as a stage pipeline (chain-of-responsibility) (`internal/pipeline/`, `internal/pipeline/stage/`)
- Mixed storage: PostgreSQL for configuration/entities, Valkey for runtime state/caches, ClickHouse for analytics ingestion (`internal/server/server.go`)
- Background workers for periodic maintenance tasks (`internal/worker/*`)

## Layers

**Entry / Bootstrap:**
- Purpose: load config, init logger, build server, handle shutdown signals
- Contains: `cmd/zai-tds/main.go`, config loading in `internal/config/config.go`
- Depends on: `internal/server`

**HTTP / Routing Layer:**
- Purpose: define routes and middleware for public/admin/click paths
- Contains: `internal/server/routes.go`
- Depends on: admin handlers (`internal/admin/handler/*`) and click pipeline (`internal/pipeline/*`)

**Admin API Layer:**
- Purpose: CRUD APIs for campaigns/streams/offers/landings/users/domains/etc.
- Contains: handler package under `internal/admin/handler/`
- Auth: `internal/admin/middleware.go` (`X-Api-Key` backed by Postgres)
- Depends on: Postgres (`pgxpool`), cache (`internal/cache/*`), botdb (`internal/botdb/*`)

**Click Processing (Hot Path):**
- Purpose: evaluate incoming click request and decide redirect/action, while producing analytics
- Abstraction: `pipeline.Pipeline` + `pipeline.Payload` (`internal/pipeline/*`)
- Implementation: stage types in `internal/pipeline/stage/*` wired in `internal/server/server.go`
- Depends on: cache/session/rotator/binding/filter/action/etc. services in `internal/*`

**Data / Infra Adapters:**
- Purpose: connect to external systems and provide typed service APIs
- Contains:
  - Postgres pool (`internal/server/server.go`)
  - Valkey client (`internal/server/server.go`)
  - ClickHouse writer (`internal/queue/writer.go`)
  - GeoIP resolver (`internal/geo/*`)

**Background Work:**
- Purpose: periodic maintenance/warmup tasks
- Contains: `internal/worker/*` and manager `internal/worker/manager.go` (wired in `internal/server/server.go`)

## Data Flow

**Public click (Level 1) (`GET /{alias}` or `/`):**
1. Request enters Chi router (`internal/server/routes.go`).
2. `server.handleClick` builds `pipeline.Payload` with `Request`/`Writer` (`internal/server/routes.go`).
3. `pipelineL1.Run(payload)` executes ~20+ stages (wired in `internal/server/server.go`).
4. Stages read config/entities from cache/DB and compute click context (campaign/stream/landing/offer), uniqueness, bot checks, etc. (`internal/pipeline/stage/*`).
5. Action stage may execute redirect/cloaking behavior (`internal/action/*`).
6. Analytics stage pushes a `queue.ClickRecord` to ClickHouse writer channel (`internal/queue/writer.go`).
7. Response is written (redirect/proxy/etc.).

**Public click (Level 2) (`GET /lp/{token}/click`):**
1. Router matches `handleClickL2` (`internal/server/routes.go`).
2. `pipelineL2.Run(payload)` executes a smaller pipeline for landing→offer.
3. Similar analytics write to ClickHouse.

**Admin API request (`/api/v1/*`):**
1. Router enters `/api/v1` group and enforces API key middleware (`internal/server/routes.go`, `internal/admin/middleware.go`).
2. Handler executes DB/cache operations (`internal/admin/handler/*`).
3. JSON response written.

**State Management:**
- Postgres stores authoritative admin entities.
- Valkey stores runtime caches/sessions/botdb/limits.
- ClickHouse stores append-only analytics records via async batching.

## Key Abstractions

**Pipelines + Stages:**
- Purpose: keep click handling composable and benchmarkable.
- Examples: `pipeline.Pipeline`, `stage.BuildRawClickStage`, `stage.ChooseStreamStage`, `stage.StoreRawClicksStage`.
- Pattern: chain-of-responsibility with a shared mutable payload.

**Services (small focused packages):**
- Purpose: isolate specific domains (sessions, filters, rotators, bindings, attribution, etc.).
- Examples: `internal/session`, `internal/filter`, `internal/rotator`, `internal/binding`.

## Entry Points

**Server binary:**
- Location: `cmd/zai-tds/main.go`
- Triggers: `go run cmd/zai-tds/main.go` or built binary
- Responsibilities: config, logging, lifecycle/shutdown

## Error Handling

**Strategy:**
- Fatal errors during startup stop the process (`cmd/zai-tds/main.go`, `internal/server/server.go`).
- Request-level errors typically log and return `http.Error` in handlers/stages.

## Cross-Cutting Concerns

**Logging:**
- `zap` logger passed/injected into most services (`internal/server/server.go`).

**Authentication:**
- Admin API key middleware (`internal/admin/middleware.go`).

**Rate limiting / bot detection (inferred):**
- Services exist for rate limiting and bot DB (`internal/ratelimit/*`, `internal/botdb/*`) and are wired into pipeline stages.

---

*Architecture analysis: 2026-04-03*
*Update when major patterns change*
