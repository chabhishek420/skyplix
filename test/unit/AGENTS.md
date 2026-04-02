<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# unit

## Purpose
Fast, isolated unit tests for core packages and utility behavior without external service dependencies.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `queue/` | Queue helper and transformation tests (see `queue/AGENTS.md`) |
| `worker/` | Background worker lifecycle tests (see `worker/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Keep unit tests deterministic and independent from network/database state.
- Prefer focused regressions near changed logic over broad integration-style assertions.
- Use clear names that encode behavior under test.

### Testing Requirements
- Run `go test ./test/unit/...` for fast feedback.
- Run `go test ./...` before finalizing cross-package behavior changes.

### Common Patterns
- `*_test.go` file naming and package-level black-box tests.
- Table-driven tests for parsing/validation helpers.

## Dependencies

### Internal
- `internal/queue`
- `internal/worker`

### External
- Go `testing` package and supporting stdlib concurrency/time primitives

<!-- MANUAL: -->
