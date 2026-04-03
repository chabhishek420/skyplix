<!-- Generated: 2026-04-03 | Updated: 2026-04-03 -->

# zai-tds

High-performance Go tracking/analytics system (TDS). Handles click tracking, redirect management, action processing, and real-time analytics with PostgreSQL, ClickHouse, and Valkey/Redis.

## Build Commands

```bash
# Build
go build -o zai-tds cmd/zai-tds/main.go

# Run
go run cmd/zai-tds/main.go

# Single test file
go test -v ./test/unit/queue/writer_test.go

# Single test function
go test -v -run TestParseUUIDVal_ValidUUID ./test/unit/queue/...

# All unit tests
go test ./test/unit/...

# All tests (requires services for integration)
go test ./...

# Integration tests (requires docker-compose)
go test -v -tags integration ./test/integration/... -timeout 30s

# With coverage
go test -cover ./...

# Format & vet
go fmt ./...
go vet ./...
```

## Code Style Guidelines

### Imports
- Standard library first, then third-party, then internal
- Group imports with blank line between groups:
  ```go
  import (
      "context"
      "net/http"
      "time"

      "github.com/go-chi/chi/v5"
      "go.uber.org/zap"

      "github.com/skyplix/zai-tds/internal/model"
  )
  ```

### Naming Conventions
| Type | Convention | Example |
|------|------------|---------|
| Packages | lowercase, single word | `botdb`, `ratelimit` |
| Structs | PascalCase | `type Server struct` |
| Interfaces | PascalCase, ending with -er when applicable | `Action`, `Worker` |
| Functions | PascalCase for exported, camelCase for unexported | `New()`, `parseInput()` |
| Variables | camelCase | `clickID`, `streamID` |
| Constants | PascalCase for exported, camelCase for unexported | `ErrRedispatch`, `maxRetries` |
| Errors | `Err` prefix for sentinel errors | `var ErrRedispatch = errors.New(...)` |

### Error Handling
- Use sentinel errors for recoverable errors: `var ErrXXX = errors.New("...")`
- Return errors immediately: `if err != nil { return ..., err }`
- Use `errors.Is()` and `errors.As()` for error checking
- Wrap errors with context: `fmt.Errorf("operation: %w", err)`
- Never suppress errors with `_`

```go
// Good
if err := fn(); err != nil {
    return fmt.Errorf("setup db: %w", err)
}

// Bad
_ = fn() // NEVER
```

### Context Propagation
- Always pass `context.Context` as first parameter
- Use `ctx` variable name consistently
- Never store context in structs

### Struct Composition
- Embed structs for composition, not inheritance
- Use interfaces for dependency injection
- Keep interfaces small (3-5 methods max)

```go
type Action interface {
    Type() string
    Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error
}
```

### Logging
- Use zap for structured logging: `logger.Info("msg", zap.String("key", val))`
- Never use `log.Println()`
- Include context fields for debugging

### Testing
- Test files: `*_test.go` in same package or `*_test` subpackage
- Use table-driven tests for multiple cases
- Use `go.uber.org/zap` logger for test logging: `zap.NewNop()`
- Mock external dependencies (Redis, DB)

```go
func TestParseUUIDVal_ValidUUID(t *testing.T) {
    w := &queue.Writer{Logger: zap.NewNop()}
    // ...
}
```

### Type Safety
- No `interface{}` unless necessary
- No type assertions without checking
- Use `any` instead of `interface{}` (Go 1.18+)
- Never use `//go:embed` for sensitive data

### Comments
- Export comments start with type name: `// Server is the main...`
- Unexport comments are lowercase
- Use `//go:linkname` only with explicit permission

## Project Structure

```
zai-tds/
├── cmd/zai-tds/     # Entry point
├── internal/         # Business logic (see internal/AGENTS.md)
│   ├── action/       # Post-click action handlers
│   ├── admin/        # Admin API handlers
│   ├── filter/       # Traffic filtering
│   ├── model/        # Data models
│   ├── pipeline/     # Click processing pipeline
│   ├── queue/        # Async click queue
│   ├── worker/       # Background workers
│   └── ...
├── db/               # Database schemas
├── test/             # Test suites
└── config.yaml       # Configuration
```

## Key Dependencies

| Package | Purpose |
|---------|---------|
| `chi/v5` | HTTP router |
| `pgx/v5` | PostgreSQL driver |
| `clickhouse-go/v2` | ClickHouse driver |
| `go-redis/v9` | Valkey/Redis client |
| `zap` | Structured logging |
| `google/uuid` | UUID generation |

## Conventions

- Configuration via YAML (`config.yaml`)
- Database transactions for consistency
- Async processing via queue/workers
- Repository pattern for data access
- Middleware chaining for cross-cutting concerns
