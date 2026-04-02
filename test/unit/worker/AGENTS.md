<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# worker

## Purpose
Unit tests for background worker manager lifecycle behavior, including startup, cancellation, and wait semantics.

## Key Files
| File | Description |
|------|-------------|
| `worker_test.go` | Covers `worker.Manager` start/wait flow, context cancellation, and error-path handling with mock workers. |

## For AI Agents

### Working In This Directory
- Use synchronization primitives/channels to avoid flaky timing assertions.
- Validate both happy path and cancellation/error shutdown paths.
- Keep worker mocks minimal and concurrency-safe.

### Testing Requirements
- Run `go test ./test/unit/worker/...` for targeted checks.
- Run `go test ./...` after changing manager contracts or interfaces.

### Common Patterns
- Mock worker implementing `Name()` and `Run(context.Context)`.
- Timeout-guarded assertions to catch deadlocks.

## Dependencies

### Internal
- `internal/worker`

### External
- Go `testing`, `context`, `sync/atomic`, and `time` packages
- `go.uber.org/zap`

<!-- MANUAL: -->
