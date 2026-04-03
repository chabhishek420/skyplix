# Architecture

**Analysis Date:** 2026-04-03

## Pattern Overview

**Overall:** Pipe and Filter / Layered Architecture

**Key Characteristics:**
- **Pipeline-driven Execution:** Click processing is handled by a strictly ordered sequence of stages, ensuring a predictable and extensible "hot path".
- **Stateless Core with Progressive Payload:** Each click request is represented by a `Payload` that is passed through stages, where it is progressively enriched (Geo, Device, Campaign/Stream selection).
- **Externalized Logic Engines:** Complex behaviors like filtering (27+ types) and actions (19+ types) are abstracted into modular engines, keeping pipeline stages lean.

## Layers

**Entry Layer (HTTP):**
- Purpose: Handles incoming HTTP requests and initiates the click pipeline.
- Location: `internal/server/`
- Contains: `server.go`, `routes.go`
- Depends on: `internal/pipeline`, `internal/config`, `internal/model`
- Used by: External traffic (visitors)

**Processing Layer (Pipeline):**
- Purpose: Orchestrates the sequential execution of logic for a click.
- Location: `internal/pipeline/`
- Contains: `pipeline.go`, `stage/`
- Depends on: `internal/model`, `internal/cache`, `internal/filter`, `internal/action`
- Used by: `internal/server`

**Domain Logic Layer (Engines & Services):**
- Purpose: Provides specialized logic for routing decisions and entity lookups.
- Location: `internal/filter/`, `internal/action/`, `internal/rotator/`, `internal/cache/`
- Contains: Filter engines, Action engines, Weighted rotators, Entity caches.
- Depends on: `internal/model`, `internal/valkey`, `internal/postgres`
- Used by: `internal/pipeline/stage`

**Data Access Layer:**
- Purpose: Provides high-performance access to stateful data (Valkey) and persistence (ClickHouse/Postgres).
- Location: `internal/cache/`, `internal/queue/`, `db/`
- Contains: `cache.go` (Valkey + Postgres fallback), `writer.go` (ClickHouse batch writer).
- Depends on: `internal/model`, External drivers (pgx, redis, clickhouse-go)
- Used by: Domain Logic Layer

## Data Flow

**Standard Click Flow (Level 1):**

1. **Request Ingestion:** `Server.handleClick` receives a request at `/{alias}`.
2. **Payload Initialization:** A `pipeline.Payload` is created to track the "current click state".
3. **Sequential Processing:** The `pipelineL1` (23 stages) executes in order:
    - `BuildRawClickStage`: Extracts IP, UA, Referrer.
    - `FindCampaignStage`: Resolves campaign via `Cache` (Valkey/Postgres).
    - `UpdateRawClickStage`: Enriches with Geo (MaxMind) and Device data.
    - `ChooseStreamStage`: Evaluates `FilterEngine` and `Rotator` to select a stream.
    - `ChooseLanding/OfferStage`: Selects the final target via `Rotator`.
    - `ExecuteActionStage`: Runs the selected `Action` (Redirect, Proxy, etc.).
4. **Asynchronous Persistence:** `StoreRawClicksStage` sends the final `RawClick` to `internal/queue/Writer` for batch insertion into ClickHouse.

**State Management:**
- **Transient State:** Stored in `pipeline.Payload` for the duration of a single request.
- **Hot State:** Stored in Valkey (Redis) for fast lookups of campaigns, streams, and visitor uniqueness.
- **Persistent State:** PostgreSQL for configuration/entities; ClickHouse for immutable analytics (clicks).

## Key Abstractions

**Stage (`internal/pipeline/pipeline.go`):**
- Purpose: Interface for a single discrete step in the click process.
- Examples: `DomainRedirectStage`, `BuildRawClickStage`, `ExecuteActionStage`.
- Pattern: Strategy / Interceptor.

**Filter (`internal/filter/filter.go`):**
- Purpose: Interface for a single routing condition (e.g., "Is Country == US").
- Examples: `CountryFilter`, `IpFilter`, `IsBotFilter`.
- Pattern: Specification.

**Action (`internal/action/action.go`):**
- Purpose: Interface for the final routing result (e.g., "Redirect to URL").
- Examples: `HttpRedirect`, `RemoteProxyAction`, `JsAction`.
- Pattern: Command.

## Entry Points

**Main TDS Server:**
- Location: `cmd/zai-tds/main.go`
- Triggers: System startup / CLI execution.
- Responsibilities: Config loading, Logger initialization, Server bootstrapping, Graceful shutdown handling.

**L1 Click Handler:**
- Location: `internal/server/routes.go` (`handleClick`)
- Triggers: HTTP GET `/{alias}` or `/`.
- Responsibilities: Running the full 23-stage pipeline for initial campaign traffic.

**L2 Click Handler:**
- Location: `internal/server/routes.go` (`handleClickL2`)
- Triggers: HTTP GET `/lp/{token}/click`.
- Responsibilities: Running the Level 2 pipeline for Landing → Offer transitions.

## Error Handling

**Strategy:** Pipeline-level abortion with centralized logging and HTTP 500 fallback.

**Patterns:**
- **Abort Flag:** `payload.Abort = true` stops further non-mandatory stages.
- **Error Wrapping:** Stages wrap errors with their name for traceabilty.
- **AlwaysRun Stages:** Critical stages (like storage) run even if the pipeline is aborted to ensure data integrity.

## Cross-Cutting Concerns

**Logging:** Structured logging using `uber-go/zap` throughout all layers.
**Validation:** Filter engine handles logical validation; `internal/config` handles runtime settings validation.
**Authentication:** Not detected in the click hot path (expected for TDS).
**Caching:** Multi-tier caching in `internal/cache/cache.go` (Valkey with Postgres fallbacks and async re-caching).

---

*Architecture analysis: 2026-04-03*
