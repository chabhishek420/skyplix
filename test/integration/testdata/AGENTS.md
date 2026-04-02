<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# testdata

## Purpose
Fixture SQL and seed artifacts used by integration tests to stand up realistic routing and tracking scenarios.

## Key Files
| File | Description |
|------|-------------|
| `seed.sql.bak` | Baseline seed dataset used by integration flows for campaigns, streams, and related entities. |

## For AI Agents

### Working In This Directory
- Keep fixture data stable and deterministic so integration assertions stay reliable.
- Prefer additive fixture updates over broad rewrites to reduce test brittleness.
- Align seeded IDs/aliases with test expectations in `test/integration/*`.

### Testing Requirements
- Re-run integration tests after any fixture edit: `go test ./test/integration/...`.
- Validate seed files apply cleanly to local PostgreSQL before committing.

### Common Patterns
- SQL seed files checked into source control for repeatable local and CI setup.
- Fixture naming tied to scenario intent rather than temporary debug usage.

## Dependencies

### Internal
- `test/integration/` test suites consume these assets.
- `db/postgres/migrations/` defines schema expected by fixture inserts.

### External
- PostgreSQL tooling (`psql`/migration runner)

<!-- MANUAL: -->
