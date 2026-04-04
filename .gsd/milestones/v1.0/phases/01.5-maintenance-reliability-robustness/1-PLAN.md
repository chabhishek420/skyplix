---
phase: 1.5
plan: 1
wave: 1
---

# Plan 1.5.1: Maintenance — Reliability & Robustness

## Objective
Fix critical technical flaws identified in Phase 1:
1. **Graceful Shutdown**: Prevent data loss by ensuring all click records are drained and flushed *after* the HTTP server stops accepting new connections.
2. **Data Integrity**: Harden the ClickHouse writer against batch-level failures caused by malformed UUIDs from incoming traffic.
3. **Technical Debt**: Formally acknowledge the default-duplicate analytics status as a known Phase 1 artifact.

## Context
- .gsd/SPEC.md
- /internal/server/server.go
- /internal/queue/writer.go
- /internal/worker/worker.go

## Tasks

<task type="auto">
  <name>Harden Worker Manager and Shutdown Chain</name>
  <files>
    - internal/worker/worker.go
    - internal/server/server.go
  </files>
  <action>
    1. **internal/worker/worker.go**: Add a `sync.WaitGroup` to `Manager`. Pass the WG to the worker goroutines in `StartAll` and ensure `Done()` is call on exit. Add a `Wait()` method.
    2. **internal/server/server.go**:
       - Create a `workerCtx, stopWorkers := context.WithCancel(context.Background())`.
       - Start workers with `workerCtx` instead of the main `ctx`.
       - In `Run()`, after `s.http.Shutdown(shutdownCtx)` returns successfully (or times out), then call `stopWorkers()`.
       - Call `mgr.Wait()` (or a similar mechanism) to block until all workers (including the click-writer) have finished draining.
  </action>
  <verify>
    - Ensure code compiles.
    - Run manual shutdown (Ctrl+C) and check logs for: "shutting down HTTP server" followed by "click writer shut down" with a count > 0 if a click was sent during shutdown.
  </verify>
  <done>
    - Worker Manager has `Wait()` method and correctly tracks worker lifecycles.
    - HTTP server shuts down *before* background workers are signaled to stop.
  </done>
</task>

<task type="auto">
  <name>Hardened ClickHouse UUID Validation</name>
  <files>
    - internal/queue/writer.go
  </files>
  <action>
    - In `flush(records []ClickRecord)`:
      - Use the existing (unused) `parseUUID` helper (or `uuid.Parse`) to validate `campaignID`, `streamID`, `offerID`, and `landingID` *before* calling `b.Append`.
      - Fallback to the zero-UUID string if parsing fails, ensuring `b.Append` always receives a string that the ClickHouse driver can definitely parse.
      - Log a warning when a malformed UUID is detected but do NOT error the entire batch.
  </action>
  <verify>
    - Ensure `go build` passes.
  </verify>
  <done>
    - Batch append only proceeds with valid UUID strings or fallback zero-UUID.
    - `b.Append` should never return an error due to UUID formatting.
  </done>
</task>

## Success Criteria
- [ ] Shutdown sequence always finishes flushing all buffered clicks before process exit.
- [ ] Malformed UUIDs in incoming clicks no longer threaten entire batch ingestion.
- [ ] No regression in sub-5ms hot-path performance (validation added at flush level, not hot path).
