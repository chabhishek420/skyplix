# Codebase Conventions

**Analysis Date:** 2026-04-06

## Language and Style

- Go code follows package-per-domain structure under `internal/`.
- Structured logging is preferred via zap fields (`internal/server/server.go`, `internal/admin/handler/*.go`).
- Context is passed explicitly (`ctx context.Context`) through services/repositories.
- Errors are wrapped with context using `fmt.Errorf("...: %w", err)` in many backend paths (`internal/server/server.go`, `internal/config/config.go`).

## Naming Conventions

- Packages: lowercase (`internal/ratelimit`, `internal/attribution`).
- Exported symbols: PascalCase (`New`, `Run`, `Service`, `Handler`).
- Unexported symbols: camelCase (`handleClick`, `requestLogger`).
- Sentinel error pattern exists in action layer (`internal/action/action.go`: `ErrRedispatch`).

## Architecture and Code Organization Patterns

- Constructor pattern for services: `New(...)` across packages (`internal/cache`, `internal/session`, `internal/auth`).
- Repository abstraction for admin DB access (`internal/admin/repository/db.go` interface + concrete repos).
- Pipeline stage interface with `Process`, `Name`, and `AlwaysRun` (`internal/pipeline/pipeline.go`).
- Worker manager orchestrates periodic/background workers (`internal/worker/*`).

## HTTP and API Patterns

- Router is composed with nested `r.Route(...)` per resource (`internal/server/routes.go`).
- Middleware stack centralized in router setup (`Recoverer`, `RealIP`, request logger, auth middleware).
- Readiness endpoint returns dependency status map, not just boolean (`internal/server/routes.go`).

## Configuration and Environment Patterns

- YAML config with env-var overrides (`internal/config/config.go`).
- Defaults applied first, then file, then env overrides.
- Validation and warning split:
  - hard failures via `validate()`
  - soft concerns via `Warnings()`

## Testing and Mocking Conventions (Observed)

- Unit tests use `zap.NewNop()` for logger dependencies (`test/unit/queue/writer_test.go`).
- Redis-dependent tests use `miniredis` (`internal/attribution/service_test.go`, `internal/admin/handler/postback_test.go`).
- DB-dependent tests use `pgxmock` for auth service (`internal/auth/service_test.go`).

## Notable Inconsistencies

- `cmd/zai-tds/main.go` still uses `log.Fatalf` in bootstrap while most runtime code uses zap.
- `scripts/migrate_keitaro.go` uses `log.Println` style instead of zap.
- Minor naming mismatch in route style (`affiliate_networks` vs other resource names) is intentional but mixed with snake_case and standard path segments.
