# Coding Conventions

**Analysis Date:** 2026-04-03

## Naming Patterns

**Files:**
- Lowercase with underscores (snake_case) for multi-word filenames.
- Implementation files named after their primary purpose (e.g., `server.go`, `pipeline.go`).
- Stage files prefixed with order numbers in `internal/pipeline/stage/` (e.g., `08_update_global_uniqueness.go`, `20_execute_action.go`).
- Test files suffix with `_test.go`.

**Functions:**
- PascalCase for exported functions (e.g., `NewEngine`, `Process`, `Run`).
- camelCase for unexported functions (e.g., `register`, `matchIncludeExclude`).
- Constructors follow the `New[Type]` pattern (e.g., `NewCampaignRepository`, `NewEngine`).

**Variables:**
- Short, descriptive camelCase names.
- Single-letter receivers for methods (e.g., `e *Engine`, `r *CampaignRepository`, `p *Pipeline`).
- Error variables usually named `err`.

**Types:**
- PascalCase for all type definitions (e.g., `RawClick`, `Campaign`, `Stream`).
- Interfaces named after the behavior they provide (e.g., `Filter`, `Action`, `Stage`).

## Code Style

**Formatting:**
- Standard `gofmt` or `goimports` formatting.
- Tab indentation (Go default).

**Linting:**
- Not explicitly configured in the repository, but follows standard Go idioms.

## Import Organization

**Order:**
1. Standard library imports.
2. Third-party library imports (e.g., `github.com/google/uuid`, `go.uber.org/zap`).
3. Internal project imports (e.g., `github.com/skyplix/zai-tds/internal/model`).

**Path Aliases:**
- Rarely used, standard package names preferred.

## Error Handling

**Patterns:**
- Return early on error (guard clauses).
- Wrap errors with context using `fmt.Errorf("context: %w", err)`.
- Use specific error variables for branching (e.g., `ErrRedispatch`).
- Errors are returned as the last value from functions.

## Logging

**Framework:** `go.uber.org/zap` (Structured logging).

**Patterns:**
- Log at appropriate levels: `Debug`, `Info`, `Warn`, `Error`.
- Use structured fields instead of formatted strings (e.g., `zap.String("type", actionType)`).
- Log significant pipeline events, service starts, and errors with context.

## Comments

**When to Comment:**
- Header comments for package/file purpose.
- Documentation comments for exported types and functions (though inconsistently applied).
- "MODIFIED" and "PURPOSE" headers in some files to track changes.

**JSDoc/TSDoc:**
- N/A (Standard Go doc comments used).

## Function Design

**Size:**
- Generally small and focused on a single responsibility.
- Large structures like `New` in `server.go` orchestrate many components.

**Parameters:**
- `context.Context` is consistently the first parameter for I/O and request-aware functions.
- Dependency injection via constructors is the primary pattern.

**Return Values:**
- Functions returning results usually return `(result, error)`.
- Predicate functions return `bool` or `(result, bool)`.

## Module Design

**Exports:**
- Explicitly exports types and functions intended for use outside the package.
- Internal implementation details kept unexported.

**Barrel Files:**
- Packages often have a primary file (e.g., `filter.go`, `action.go`) that acts as an entry point and registry for the module.

---

*Convention analysis: 2026-04-03*
