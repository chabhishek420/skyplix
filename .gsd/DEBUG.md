# Debug Session: IntegrationTestFailures

## Symptom
`TestPhase2Routing` fails with multiple issues:
1. `BotGetsBlocked`: Expected 200 (ShowHtml), got 302. Generic "Found" relative link instead of custom HTML.
2. `GeoFilterRouting`: Expected 302 (HttpRedirect), got 200. Empty redirect URL.
3. `DefaultStreamFallback`: Expected 302, got 200. Empty redirect URL.
4. `WeightedStreamSelection`: Both `high` and `low` counts are 0.
5. `Level2LandingClick`: Expected landing redirect, got empty.

**When:** During `go test -tags integration ./test/integration/routing_test.go`.
**Expected:** Requests should be routed to appropriate streams, actions executed with correct URLs/headers.
**Actual:** Many requests return 200 with empty bodies or 302 with generic "Found" messages.

## Evidence
- `server.log`: Shows `click processed` but `latency` is low, implying success.
- `routing_test.go`: Captures `302` when `ShowHtml` (200) was expected.
- `routing_test.go`: Captures `200` with empty Location when `302` was expected.

## Hypotheses

| # | Hypothesis | Likelihood | Status |
|---|------------|------------|--------|
| 1 | Action registration name mismatch (PascalCase vs snake_case) | 90% | PARTIALLY FIXED (I fixed some, but maybe not all) |
| 2 | `ctx.RedirectURL` is empty because of macro replacement error | 80% | UNTESTED |
| 3 | `ExecuteActionStage` doesn't set `payload.Abort = true` correctly | 50% | UNTESTED |
| 4 | Test data enum mismatch | 40% | PARTIALLY FIXED |
