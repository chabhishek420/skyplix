<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# queue

## Purpose
Unit tests for queue-related transformation helpers and writer-side data normalization logic.

## Key Files
| File | Description |
|------|-------------|
| `writer_test.go` | Validates UUID parsing, IP normalization, fixed-width string helpers, and `RawClick` to queue record mapping. |

## For AI Agents

### Working In This Directory
- Keep tests deterministic and table-driven where possible for conversion helpers.
- Mirror queue schema assumptions when asserting transformed fields.
- Prefer adding regression tests before changing queue serialization behavior.

### Testing Requirements
- Run `go test ./test/unit/queue/...` for focused verification.
- Run `go test ./...` when helper behavior affects shared packages.

### Common Patterns
- `zap.NewNop()` logger injection for pure helper tests.
- Explicit assertions on fallback/default behavior for invalid input.

## Dependencies

### Internal
- `internal/queue`
- `internal/model`

### External
- `github.com/google/uuid`
- `go.uber.org/zap`

<!-- MANUAL: -->
