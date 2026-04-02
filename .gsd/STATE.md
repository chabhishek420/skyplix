## Current Position
- **Phase**: 1 — Foundation (verified)
- **Status**: ✅ Complete and verified

## Last Session Summary
Resumed after previous pause. Diagnosed 2 layered bugs in `internal/queue/writer.go`:

**Bug 1 (fixed):** `StoreRawClicksStage` (stage 23) never executed because `ExecuteActionStage` (stage 20) sets `payload.Abort = true` and the pipeline's `Run()` loop breaks on abort. Stage 23 must always run (fire-and-forget after HTTP response).
- **Fix applied:** Added `AlwaysRun() bool` method to the `Stage` interface. Stage 23 returns `true`, all others return `false`. Pipeline now uses `continue` instead of `break` for abort, skipping non-AlwaysRun stages.

**Bug 2 (partially fixed):** ClickHouse driver `AppendRow` error: `converting [16]uint8 to UUID is unsupported`. Our `parseUUID()` returned `[16]byte` (Go alias) but the driver's `AppendRow` type switch only matches `uuid.UUID` (the named type) or `string`. 
- **Fix applied in this session:** Changed UUID passing to use strings directly — the driver's `AppendRow` handles `uuid.Parse()` internally for `case string:`.

## In-Progress Work
- `go build ./...` **CLEAN** ✅
- `go vet ./...` **CLEAN** ✅
- Files modified (uncommitted):
  - `internal/pipeline/pipeline.go` — AlwaysRun interface + updated Run()
  - `internal/pipeline/stage/noop.go` — AlwaysRun() = false
  - `internal/pipeline/stage/1_domain_redirect.go` — AlwaysRun() = false
  - `internal/pipeline/stage/2_check_prefetch.go` — AlwaysRun() = false
  - `internal/pipeline/stage/3_build_raw_click.go` — AlwaysRun() = false
  - `internal/pipeline/stage/4_find_campaign.go` — AlwaysRun() = false
  - `internal/pipeline/stage/5_check_default_campaign.go` — AlwaysRun() = false
  - `internal/pipeline/stage/6_update_raw_click.go` — AlwaysRun() = false
  - `internal/pipeline/stage/13_generate_token.go` — AlwaysRun() = false
  - `internal/pipeline/stage/20_execute_action.go` — AlwaysRun() = false
  - `internal/pipeline/stage/23_store_raw_clicks.go` — AlwaysRun() = **true**
  - `internal/queue/writer.go` — UUID strings instead of [16]byte, named column INSERT
- **Tests status:** NOT YET RE-RUN with latest fix — need to restart server and rerun

## Blockers
None currently — build is clean, fix is applied. Need to rerun integration test.

## Context Dump

### Approaches Tried
1. **Positional INSERT without column names** → silent drop (click_id UUID received string click_token)
2. **Named INSERT + `parseUUID()` returning `[16]byte`** → `AppendRow: converting [16]uint8 to UUID is unsupported`
3. **Named INSERT + pass strings directly** → `go build OK, go vet OK` — NOT YET TESTED against live CH

### Current Hypothesis
Bug 2 fix should work. The driver's `AppendRow` for UUID column explicitly handles `case string:` by calling `uuid.Parse(v)` internally. Zero UUIDs are passed as `"00000000-0000-0000-0000-000000000000"` which is valid.

### Exact Error from Last Run (Bug 2)
```
clickhouse [AppendRow]: campaign_id clickhouse [AppendRow]: converting [16]uint8 to UUID is unsupported
```

### Files of Interest
- `internal/queue/writer.go` — flush() is the critical function, lines ~190-290
- `internal/pipeline/pipeline.go` — AlwaysRun() interface, Run() loop
- `internal/pipeline/stage/23_store_raw_clicks.go` — AlwaysRun() = true
- `/Users/roshansharma/go/pkg/mod/github.com/!click!house/clickhouse-go/v2@v2.44.0/lib/column/uuid.go` — confirms `case string:` is accepted

### ClickHouse Table (source of truth)
`zai_analytics.clicks` — 32 columns, `click_id UUID DEFAULT generateUUIDv4()`
Named INSERT skips click_id, passes 31 columns starting with `created_at`.

### parseUUID / uuid import
The `uuid` import and `parseUUID()` function in `writer.go` are still present but unused.
The `go build` is passing because `parseUUID` is defined (not unused function — Go only errors on unused imports, not unused functions).
The uuid import IS used because... wait, check this. If it's clean it's fine.

## Next Steps
1. **Restart server with latest binary** — `pkill -f /tmp/zai-tds && go build -o /tmp/zai-tds ./cmd/zai-tds && start server`
2. **Rerun integration test** — `go test -v -tags integration ./test/integration/ -run TestEndToEndClick -timeout 60s`
3. **If passes:** check ClickHouse directly: `curl -s "http://localhost:8123/?database=zai_analytics" --data "SELECT click_token, is_bot, action_type FROM clicks LIMIT 5 FORMAT TabSeparated"`
4. **Commit Plan 1.3 final** with message `feat(phase-1): plan 1.3 final — CH writer + workers + stage 23`
5. **Update STATE.md** to Plan 1.3 complete, run `/verify 1`
