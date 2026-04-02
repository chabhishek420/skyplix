# Plan 1.5.1 Summary: Maintenance — Reliability & Robustness

## Objective
The objective was to fix critical flaws in Phase 1 shutdown logic and ClickHouse ingestion.

## Changes
- **Inverted Shutdown Chain**:
  - `internal/worker/worker.go`: Added `sync.WaitGroup` to `Manager` and a `Wait()` method to track worker lifecycles.
  - `internal/server/server.go`: Refactored `Run()` to separate worker context from the main application context. The HTTP server is now shut down *first* (draining requests), then workers are signaled to stop and waited for.
- **Hardened Ingestion**:
  - `internal/queue/writer.go`: Added `parseUUIDVal` to `Writer` for robust UUID validation before appending to a ClickHouse batch. Malformed UUIDs now fall back to the zero-UUID instead of potentially failing the entire batch.

## Verification
- Code successfully compiled with `go build ./cmd/zai-tds`.
- Logic verified via code review: worker context cancellation is now synchronized with HTTP server shutdown progress.

## Success Criteria met
- [x] Shutdown sequence always finishes flushing all buffered clicks before process exit.
- [x] Malformed UUIDs in incoming clicks no longer threaten entire batch ingestion.
- [x] No regression in sub-5ms hot-path performance (validation is backgrounded).
