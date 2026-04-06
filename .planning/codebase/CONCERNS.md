# Concerns and Technical Debt

**Analysis Date:** 2026-04-06

## High-Priority Risks

1. Partial migration command implementation
- `cmd/zai-tds/main.go` prints that Keitaro migration is not fully implemented in CLI wrapper.
- Operational risk: users may assume command is production-ready when it delegates to script.

2. Weak default secret in checked config
- `config.yaml` contains `system.salt: "change-me-in-production-min-32-chars"`.
- `internal/config/config.go` protects production mode, but debug environments can still run insecure defaults.

3. Optional dependency degradation can hide analytics gaps
- ClickHouse reader init failures only warn and continue (`internal/server/server.go`).
- Readiness can report degraded/skipped dependencies (`internal/server/routes.go`).
- Risk: partial service behavior without immediate hard failure.

## Security and Abuse Surface

- Public postback endpoints (`GET|POST /postback/{key}`) are intentionally open and need strict key hygiene (`internal/server/routes.go`).
- Traffic endpoints are public by design and depend heavily on anti-bot and rate-limit logic.
- Some admin bot/UA operations are write-heavy and require auth middleware correctness (`internal/server/routes.go`, `internal/auth/service.go`).

## Reliability and Maintainability Concerns

- Server composition in `internal/server/server.go` is large and centralizes many responsibilities; this raises regression risk during feature changes.
- Pipeline stage ordering is encoded manually in server wiring; accidental order changes can alter traffic behavior.
- Dependency on external reference file for CIDR bot list (`reference/YellowCloaker/bases/bots.txt`) introduces runtime coupling to non-core directory content.

## Style/Consistency Debt

- Logging style is mixed:
  - zap in runtime services.
  - `log.Printf`/`log.Println` in migration script and `log.Fatalf` in CLI bootstrap.
- This weakens uniform observability and structured log analysis.

## Testing Gaps with Production Impact

- `admin-ui` lacks visible automated tests in this repository.
- Integration tests rely on real services and are unlikely to run on every local edit without automation.
- No explicit CI pipeline files were observed, so regressions may rely on manual discipline.

## Performance Considerations

- High-throughput click paths depend on queue flush behavior and worker cadence (`internal/queue/writer.go`, `internal/worker/*`).
- Misconfiguration of ClickHouse or Valkey can degrade throughput while service stays partially alive.
- Large `reference/` tree increases repository weight and can affect tooling performance.

## Suggested Follow-Up Work

- Promote migration command to a fully integrated and validated CLI flow.
- Harden startup checks for required production-safe secrets and dependency availability by mode.
- Split `server.New(...)` wiring into smaller module assemblers to reduce blast radius.
- Introduce CI test baseline and minimal frontend smoke tests.
