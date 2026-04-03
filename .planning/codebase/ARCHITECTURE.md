# ARCHITECTURE

## High-Level Shape
- Monolithic Go service with explicit layers under `internal/`.
- Runtime boot sequence:
- config load (`internal/config/config.go`)
- service wiring (`internal/server/server.go`)
- HTTP route setup (`internal/server/routes.go`)
- worker + writer goroutines (`internal/worker/*.go`, `internal/queue/writer.go`)

## Request Processing Model
- Click handling is stage-pipeline based:
- Shared mutable payload type: `internal/pipeline/pipeline.go`.
- L1 pipeline (campaign/stream flow) assembled in `internal/server/server.go`.
- L2 pipeline (landing token flow) assembled in `internal/server/server.go`.
- Individual stage implementations live in `internal/pipeline/stage/*.go`.

## Pipeline Responsibilities
- Data extraction and bot heuristics begin in `internal/pipeline/stage/3_build_raw_click.go`.
- Campaign/stream/offer/landing resolution uses cache + DB fallback through `internal/cache/cache.go`.
- Action execution uses registry-based engine in `internal/action/action.go`.
- Final persistence stage writes to async queue in `internal/pipeline/stage/23_store_raw_clicks.go`.

## Data Access Pattern
- Admin API uses repository pattern (`internal/admin/repository/*.go`).
- Click hot path tries Valkey first via cache service (`internal/cache/cache.go`), then falls back to Postgres queries.
- Analytical writes are decoupled from request path through buffered channels and batch inserts (`internal/queue/writer.go`).

## Concurrency Model
- HTTP server handles request concurrency naturally via net/http.
- Writer runs as background goroutine and periodically flushes batches (`internal/queue/writer.go`).
- Worker manager starts independent goroutines and tracks lifecycle via WaitGroup (`internal/worker/worker.go`).
- Some operations intentionally best-effort async (example: attribution save goroutine in `internal/pipeline/stage/23_store_raw_clicks.go`).

## Cross-Cutting Concerns
- Structured logging with zap across server/services.
- API auth middleware for admin endpoints (`internal/admin/middleware.go`).
- Rate limiting service on IP keys in Valkey (`internal/ratelimit/ratelimit.go`).
- Session uniqueness and visitor binding in Valkey (`internal/session/session.go`).

## Persistence Boundaries
- PostgreSQL: source of truth for campaigns/streams/offers/landings/users/settings.
- ClickHouse: append-heavy event analytics (`clicks`, `conversions`).
- Valkey: fast lookup/caching/session counters and anti-bot/rate-limit state.

## Shutdown Strategy
- Graceful sequence in `internal/server/server.go`:
- stop accepting HTTP traffic
- wait worker + writer drain
- close DB/Valkey handles

## Architectural Strengths
- Clear package-level boundaries under `internal/`.
- Pipeline stages make click logic composable and inspectable.
- Queue-based analytics sink protects response latency from ClickHouse variability.

## Architectural Tradeoff Areas
- `server.New` currently swallows some initialization errors (`geo.New`, `queue.NewWriter`), creating partial-degraded boots without explicit fail-fast behavior.
- Request path contains shared mutable payload object; stage coupling requires discipline and tests per stage interaction.
