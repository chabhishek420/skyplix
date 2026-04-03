<!-- Generated: 2026-04-03 10:25 | Updated: 2026-04-03 10:25 -->
# zai-yt-keitaro

## Purpose
High-performance Go traffic distribution and analytics system (TDS). The repository combines click pipeline logic, admin APIs, database schemas, tests, and GSD planning artifacts used to drive milestone-based delivery.

## Key Files
| File | Purpose / Responsibility |
|------|--------------------------|
| `go.mod` | Go module dependencies and versions for backend runtime. |
| `config.yaml` | Default runtime configuration values used by local and production execution. |
| `docker-compose.yml` | Local infrastructure stack for PostgreSQL, ClickHouse, and Valkey. |
| `AGENTS.md` | Project-specific agent instructions and coding conventions. |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `.agent/` | Hidden workspace/tooling support assets (see `.agent/CLAUDE.md`). |
| `.claude/` | Hidden workspace/tooling support assets (see `.claude/CLAUDE.md`). |
| `.gemini/` | Hidden workspace/tooling support assets (see `.gemini/CLAUDE.md`). |
| `.gsd/` | AI agent planning artifacts. Contains phases, milestones, decisions, research, architecture docs, and workflow templates for the SkyPlix TDS project. (see `.gsd/CLAUDE.md`). |
| `.gsd-source/` | Source code for the GSD workflow/tooling framework (see `.gsd-source/CLAUDE.md`). |
| `admin-ui/` | Frontend admin interface workspace for the tracking system. This directory is currently scaffolded with structure in place but no implemented UI source files yet. (see `admin-ui/CLAUDE.md`). |
| `cmd/` | Application entry points and CLI command implementations. (see `cmd/CLAUDE.md`). |
| `data/` | Organized module group under `data` (see `data/CLAUDE.md`). |
| `db/` | Database schemas, migrations, and query definitions for PostgreSQL and ClickHouse. (see `db/CLAUDE.md`). |
| `docs/` | Organized module group under `docs` (see `docs/CLAUDE.md`). |
| `internal/` | Core application business logic organized by domain. Contains HTTP handlers, models, middleware, and processing pipelines. (see `internal/CLAUDE.md`). |
| `test/` | Test suites including unit tests and integration tests. (see `test/CLAUDE.md`). |

## Claude-specific Guidance
### When Editing Files Here
- Respect the TDS hot-path goal: keep request-path changes deterministic and low-allocation.
- Prefer additive, backwards-compatible changes across APIs, schema, and pipeline stages.
- Do not mix planning artifacts and product code changes in one commit unless tightly coupled.

### Testing Expectations
- Use `go test ./test/unit/...` for fast checks and `go test -v -tags integration ./test/integration/... -timeout 30s` for end-to-end behavior.
- For pipeline or action changes, validate both unit coverage and integration routes before merge.
- If datastore contracts change, run the affected migration and query tests before finalizing.

### Common Patterns & Conventions
- Context is the first parameter for runtime operations; do not store context in structs.
- Wrap errors with operation context and use `errors.Is`/`errors.As` for branching.
- Use structured logging (`zap`) with stable keys; avoid free-form log spam.

### Dependencies - Internal
- `cmd/zai-tds/` bootstraps the server and wires `internal/` packages.
- `db/` migrations and schemas define persistent contracts consumed by admin and pipeline logic.
- `test/` verifies package behavior, integration flows, and latency expectations.

### Dependencies - External
- Go 1.25 runtime, PostgreSQL, ClickHouse, and Valkey are required core infrastructure.
- `chi`, `pgx`, `go-redis`, `clickhouse-go/v2`, `zap`, and `google/uuid` are foundational libraries.

<!-- MANUAL SECTION -->
<!-- Add project-specific notes, warnings, future plans, etc. below -->
