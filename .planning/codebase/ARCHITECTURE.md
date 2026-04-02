# Architecture

**Analysis Date:** 2026-04-02

## Pattern Overview

**Overall:** Pipeline Architecture with Stage-Based Processing

**Key Characteristics:**
- Request-response model with a 23-stage pipeline for click processing
- Each stage is a discrete, testable unit with a shared `Payload` context
- Stages execute sequentially; pipeline supports abort control via `Abort` flag
- "AlwaysRun" pattern allows critical stages (e.g., ClickHouse storage) to execute after abort
- Separation between hot path (click processing) and admin operations

## Layers

**HTTP/Routing Layer:**
- Purpose: Receive HTTP requests and dispatch to pipeline
- Location: `internal/server/`
- Contains: `server.go`, `routes.go`
- Depends on: All internal packages, chi router
- Used by: External HTTP clients, load balancers

**Pipeline Layer:**
- Purpose: Orchestrate 23-stage click processing pipeline
- Location: `internal/pipeline/`, `internal/pipeline/stage/`
- Contains: Core pipeline logic, stage definitions
- Depends on: `internal/model`, `internal/server`
- Used by: `routes.go` handleClick

**Stage Layer:**
- Purpose: Individual processing steps in the pipeline
- Location: `internal/pipeline/stage/*.go`
- Contains: Stage implementations (FindCampaign, ExecuteAction, etc.)
- Depends on: Database clients, geo resolver, device detector
- Used by: Pipeline via `New()` constructor

**Data Access Layer:**
- Purpose: PostgreSQL queries and ClickHouse writes
- Location: `db/postgres/`, `internal/queue/`
- Contains: SQL migrations, ClickHouse writer
- Depends on: pgx driver, clickhouse-go
- Used by: Pipeline stages

**Infrastructure Services:**
- Purpose: Cross-cutting concerns (geo IP, device detection, caching, workers)
- Location: `internal/geo/`, `internal/device/`, `internal/worker/`, `internal/cache/`
- Contains: GeoIP resolver, UA parser, background worker manager
- Depends on: External databases (GeoIP, Redis)
- Used by: Pipeline stages, server initialization

**Configuration Layer:**
- Purpose: YAML-based configuration with environment variable overrides
- Location: `internal/config/`
- Contains: Config struct, Load function
- Depends on: YAML parser
- Used by: All packages via server dependency injection

## Data Flow

**Click Processing Flow:**

```
HTTP Request
    ↓
[1] DomainRedirectStage     - Check for bare domain redirect
    ↓
[2] CheckPrefetchStage      - Detect prefetch requests
    ↓
[3] BuildRawClickStage      - Extract IP, UA, referrer, sub_ids, bot detection
    ↓
[4] FindCampaignStage        - Lookup campaign from PostgreSQL by alias
    ↓
[5] CheckDefaultCampaignStage - Handle domain-level default campaigns
    ↓
[6] UpdateRawClickStage      - Enrich with GeoIP + device detection
    ↓
[7-12] NoOp Stages          - Stream selection (Phase 2)
    ↓
[13] GenerateTokenStage      - Create cryptographic click token
    ↓
[14-19] NoOp Stages         - Cost, payout, cookies (Phase 2)
    ↓
[20] ExecuteActionStage     - Execute HTTP response action (redirect)
    ↓
[21-22] NoOp Stages         - Final processing (Phase 2)
    ↓
[23] StoreRawClicksStage     - Async write to ClickHouse (AlwaysRun)
    ↓
HTTP Response (302 redirect or error)
```

**State Management:**
- `Payload` struct threads through all stages as shared context
- `RawClick` progressively populated as pipeline advances
- `Campaign`, `Stream`, `Offer`, `Landing` resolved entities stored in payload
- `Abort` flag controls early termination (except AlwaysRun stages)
- Redis (Valkey) used for session/uniqueness state (Phase 2)

## Key Abstractions

**Pipeline Interface:**
```go
type Stage interface {
    Process(payload *Payload) error
    Name() string
    AlwaysRun() bool
}
```
- Purpose: Define contract for all pipeline stages
- Examples: `internal/pipeline/stage/4_find_campaign.go`, `stage/20_execute_action.go`
- Pattern: Functional options with struct embedding for dependencies

**Payload:**
```go
type Payload struct {
    Ctx, Request, Writer  // HTTP context
    RawClick               // Progressive click data
    Campaign, Stream, Offer, Landing  // Resolved entities
    Response              // Final HTTP response
    Abort, AbortCode       // Pipeline control
}
```
- Purpose: Shared state container threaded through pipeline
- Examples: `internal/pipeline/pipeline.go`
- Pattern: Mutable context object passed by pointer

**Worker Interface:**
```go
type Worker interface {
    Name() string
    Run(ctx context.Context) error
}
```
- Purpose: Background task abstraction
- Examples: `internal/worker/worker.go`, `cache_warmup.go`, `hitlimit_reset.go`
- Pattern: Goroutine-based workers managed by WorkerManager

**ClickRecord:**
```go
type ClickRecord struct {
    // 31 columns for ClickHouse INSERT
}
```
- Purpose: Flat representation of RawClick for analytics storage
- Examples: `internal/queue/writer.go`
- Pattern: Separate from domain model for storage optimization

## Entry Points

**Main Application:**
- Location: `cmd/zai-tds/main.go`
- Triggers: Binary execution
- Responsibilities: Config loading, logger initialization, server bootstrap, signal handling

**HTTP Server:**
- Location: `internal/server/server.go`
- Triggers: `server.New()` from main
- Responsibilities: DB connections, worker startup, HTTP server lifecycle

**Click Handler:**
- Location: `internal/server/routes.go` (`handleClick`)
- Triggers: GET request to `/{alias}` or `/`
- Responsibilities: Pipeline instantiation, execution, telemetry logging

**Health Check:**
- Location: `internal/server/routes.go` (`handleHealth`)
- Triggers: GET request to `/api/v1/health`
- Responsibilities: Return server status and version

## Error Handling

**Strategy:** Abort-based pipeline control with error propagation

**Patterns:**
- Stages return `nil` on success, set `payload.Abort = true` + `AbortCode` for controlled aborts
- Unrecoverable errors return Go `error` and are logged at server level
- ClickHouse writer failures are logged but don't affect HTTP response (async)
- NoOp stages for unimplemented Phase 2 features log debug messages

## Cross-Cutting Concerns

**Logging:** Zap logger
- Production: JSON structured logs
- Development: Human-readable format
- Per-request logging via chi middleware

**Validation:** Inline per-stage
- Campaign alias existence checked in `FindCampaignStage`
- Bot detection inline in `BuildRawClickStage`
- Config validation on startup in `config.go`

**Authentication:** Not implemented (Phase 1)
- Admin API endpoints in `internal/admin/` are stubs
- Future: Token-based auth for admin endpoints

**Observability:** Structured logging + health endpoint
- Health check at `/api/v1/health`
- Per-click telemetry logged with latency

---

*Architecture analysis: 2026-04-02*
