# Coding Conventions

**Analysis Date:** 2026-04-03

## Naming Patterns

**Packages:**
- Lowercase, usually single word (examples: `internal/ratelimit`, `internal/hitlimit`, `internal/botdb`).

**Files:**
- Lowercase Go filenames, typically `noun.go` or `topic.go` (examples: `internal/server/server.go`, `internal/server/routes.go`, `internal/admin/middleware.go`).

**Types / Structs / Interfaces:**
- PascalCase (examples: `server.Server` in `internal/server/server.go`, `queue.Writer` in `internal/queue/writer.go`).

**Functions / Methods:**
- Exported: PascalCase (`server.New`, `config.Load`)
- Unexported: camelCase (`newLogger`, `rewriteRelativeURLs`)

**Variables / Fields:**
- camelCase for locals, PascalCase for exported fields; common abbreviations used (`cfg`, `srv`, `db`, `vk`).

## Code Style

**Formatting:**
- Standard `gofmt` formatting is expected (`go fmt ./...`).

**Imports:**
- Grouped into standard library, third-party, then internal modules with blank lines (common pattern in `internal/server/server.go`).

## Error Handling

**Patterns:**
- Prefer early returns: `if err != nil { return ..., fmt.Errorf("context: %w", err) }` (example: `internal/server/server.go`).
- Wrap errors with context using `fmt.Errorf("...: %w", err)`.
- Use `errors.Is` / `errors.As` for comparisons where applicable.

**HTTP Handlers:**
- Handler-level failures typically log and respond using `http.Error` (examples: `internal/server/routes.go`, `internal/admin/middleware.go`).

## Context Propagation

- Use `context.Context` in request paths (e.g. `r.Context()` is used across DB calls and HTTP client calls: `internal/admin/middleware.go`, `internal/action/proxy.go`).
- Prefer passing `ctx` explicitly rather than storing it in structs.

## Logging

**Framework:**
- Use `go.uber.org/zap` (constructed in `cmd/zai-tds/main.go`).

**Patterns:**
- Structured fields via `zap.*` (examples: `internal/server/server.go`, `internal/server/routes.go`).
- Avoid `log.Println` style logging.

## Comments

- Exported identifiers generally have doc comments (example: `// Server is the main application server.` in `internal/server/server.go`).
- Inline comments used to explain pipeline intent and operational constraints (examples throughout `internal/server/server.go`).

## Function Design

- Prefer small focused helpers (example: `config.defaults()` and `config.validate()` in `internal/config/config.go`).
- Pipelines are assembled as a list of stage structs rather than giant handler logic (`internal/server/server.go`).

## Module Design

- Domain-oriented internal packages (session/cache/botdb/ratelimit/etc.) under `internal/`.
- Admin endpoints are grouped by resource under `internal/server/routes.go` and implemented in `internal/admin/handler/`.

---

*Conventions analysis: 2026-04-03*
*Update as conventions evolve*
