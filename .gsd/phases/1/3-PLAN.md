---
phase: 1
plan: 3
wave: 2
---

# Plan 1.3: Background Workers + ClickHouse Async Writer + Stages 13+20-23 + Integration Test

## Objective
Complete the Phase 1 click pipeline by adding the remaining required stages (GenerateToken, ExecuteAction, PrepareRawClickToStore, StoreRawClicks), implement the background worker framework (goroutines + ticker), wire the async ClickHouse click writer (buffered Go channel → batch INSERT every 500ms), and write an integration test that proves an end-to-end click is stored in ClickHouse. After this plan, Phase 1 Deliverable is fully met.

## Context
- .gsd/SPEC.md — Pipeline stages 13, 20-23 (lines 97-108); background worker requirement
- .gsd/ARCHITECTURE.md — Background workers (lines 339-363); ClickHouse schema; queue system (lines 151-152)
- .gsd/STACK.md — clickhouse-go/v2 (line 43); go-redis/v9 for queue list (line 49)
- .gsd/DECISIONS.md — ADR-002 (async ClickHouse write, never on hot path)

## Tasks

<task type="auto">
  <name>Pipeline stages 13+20-23: GenerateToken, ExecuteAction, PrepareClick, StoreClick</name>
  <files>
    internal/pipeline/stage/13_generate_token.go
    internal/pipeline/stage/20_execute_action.go
    internal/pipeline/stage/21_prepare_raw_click.go
    internal/pipeline/stage/22_check_sending_to_another_campaign.go
    internal/pipeline/stage/23_store_raw_clicks.go
    internal/queue/writer.go
    internal/server/routes.go (update: wire full pipeline)
  </files>
  <action>
    **Stage 13 — GenerateTokenStage** (13_generate_token.go):
    - Generate cryptographically random 16-byte token, hex-encoded (32 chars)
    - `token := make([]byte, 16); rand.Read(token); rawClick.ClickToken = hex.EncodeToString(token)`
    - Use `crypto/rand` from stdlib — NOT `math/rand`
    - Store token in Valkey for conversion postback lookup: `SET token:{token} {click_id} EX 2592000` (30 days)

    **Stage 20 — ExecuteActionStage** (20_execute_action.go):
    - For Phase 1: implement HttpRedirect action only (most common — >90% of traffic)
    - If payload.Offer != nil: redirect to payload.Offer.URL
    - If no offer selected: redirect to campaign default URL or 404
    - Build 302 response: `http.Redirect(w, r, url, http.StatusFound)`
    - Set payload.Response.ActionType = "HttpRedirect"
    - Set payload.Abort = true AFTER setting response (stop remaining stages from running)
    - Meta redirect and other action types: placeholder for Phase 2

    **Stage 21 — PrepareRawClickToStoreStage** (21_prepare_raw_click.go):
    - Serialize payload.RawClick into a `ClickRecord` struct ready for ClickHouse insert
    - Map all RawClick fields to ClickHouse column names (from ARCHITECTURE.md lines 411-413)
    - Set CreatedAt if not already set

    **Stage 22 — CheckSendingToAnotherCampaignStage** (22_check_sending_to_another_campaign.go):
    - For Phase 1: no-op stub (ToCampaign action type not implemented until Phase 2)
    - Log at debug level: "CheckSendingToAnotherCampaign: no-op in Phase 1"

    **Stage 23 — StoreRawClicksStage** (23_store_raw_clicks.go):
    - Push ClickRecord to buffered Go channel (non-blocking):
      ```go
      select {
      case s.clickChan <- record:
        // queued
      default:
        s.logger.Warn("click channel full, dropping click", zap.String("click_id", record.ClickToken))
      }
      ```
    - The channel is provided via dependency injection (not a global)
    - This stage MUST NOT block — hot path requirement (<5ms)

    **`internal/queue/writer.go`** — ClickHouse async batch writer:
    - `Writer` struct: ch driver, buffered `chan ClickRecord` (capacity: 10,000)
    - `Run(ctx context.Context)` — long-running goroutine:
      ```
      ticker := time.NewTicker(500 * time.Millisecond)
      batch := make([]ClickRecord, 0, 5000)
      for {
        select {
        case record := <-w.clickChan:
          batch = append(batch, record)
          if len(batch) >= 5000 { flush(batch) }
        case <-ticker.C:
          if len(batch) > 0 { flush(batch) }
        case <-ctx.Done():
          flush(batch) // drain on shutdown
          return
        }
      }
      ```
    - `flush(records)`: INSERT INTO clicks batch using clickhouse-go v2 batch API
    - On ClickHouse error: log error + retain records in next batch (up to 3 retries)

    Add dependency: `go get github.com/ClickHouse/clickhouse-go/v2`

    Wire full pipeline in routes.go click handler:
    Stages in order: 1,2,3,4,5,6,[7-12 stubs],13,[14-19 stubs],20,21,22,23
    Stubs (7-12, 14-19): implement as no-op stages that log "stage N: no-op in Phase 1"

    DO NOT implement the Valkey command queue (ExecuteDelayedCommand) yet — Phase 2.
    DO NOT share the clickChan across goroutines without proper channel ownership — writer.go owns the receive end.
  </action>
  <verify>
    go build -o /tmp/zai-tds ./cmd/zai-tds && echo "BUILD OK" && \
    CLICKHOUSE_URL="localhost:9000" \
    DATABASE_URL="postgres://zai:zai_dev_pass@localhost:5432/zai_tds" \
    VALKEY_URL="localhost:6379" \
    /tmp/zai-tds &
    sleep 1

    # Send a click
    curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/test
    sleep 2  # wait for batch flush

    # Check ClickHouse for the stored click
    curl -s "http://localhost:8123/" --data "SELECT count(*) FROM zai_analytics.clicks" && echo " clicks in ClickHouse"

    kill %1
  </verify>
  <done>
    - `GET /test` returns HTTP 302 (not 501)
    - After 500ms+ delay, ClickHouse `SELECT count(*) FROM clicks` returns >= 1
    - Server logs show: "click stored" or batch flush log entry
    - GenerateToken: click_token is 32-char hex in ClickHouse record
    - No goroutine leaks on SIGINT — writer drains channel before exit
  </done>
</task>

<task type="auto">
  <name>Background worker framework: hit limit reset + cache warmup stub + integration test</name>
  <files>
    internal/worker/worker.go
    internal/worker/hitlimit_reset.go
    internal/worker/cache_warmup.go
    internal/worker/session_janitor.go
    internal/server/server.go (update: start workers on Run())
    internal/integration_test.go (or test/integration/click_test.go)
  </files>
  <action>
    **`internal/worker/worker.go`** — Worker framework:
    ```go
    type Worker interface {
      Name() string
      Run(ctx context.Context) error
    }

    type Manager struct {
      workers []Worker
      logger  *zap.Logger
    }

    func (m *Manager) StartAll(ctx context.Context) {
      for _, w := range m.workers {
        go func(worker Worker) {
          m.logger.Info("worker started", zap.String("worker", worker.Name()))
          if err := worker.Run(ctx); err != nil && !errors.Is(err, context.Canceled) {
            m.logger.Error("worker error", zap.String("worker", worker.Name()), zap.Error(err))
          }
        }(w)
      }
    }
    ```

    **`internal/worker/hitlimit_reset.go`** — HitLimitResetWorker:
    - Name(): "hitlimit-reset"
    - Run(ctx): Ticker every 24h at midnight UTC
    - Action: delete all `hitlimit:*` keys from Valkey (SCAN + DEL pattern)
    - Log: "hit limit counters reset" with count of deleted keys

    **`internal/worker/cache_warmup.go`** — CacheWarmupWorker:
    - Name(): "cache-warmup"
    - Run(ctx): Ticker every 30s
    - Phase 1 stub: check Valkey key `warmup:scheduled` — if exists, log "warmup triggered"
    - Full implementation (loading campaigns into Valkey) comes in Phase 2

    **`internal/worker/session_janitor.go`** — SessionJanitorWorker:
    - Name(): "session-janitor"
    - Run(ctx): Ticker every 1h
    - Phase 1 stub: log "session janitor: no-op in Phase 1"

    Update `internal/server/server.go`:
    - In Run(): create WorkerManager, register ClickWriter + HitLimitReset + CacheWarmup + SessionJanitor
    - Call workerManager.StartAll(ctx) before http.ListenAndServe
    - ClickHouse connection initialized here and passed to ClickWriter worker

    **Integration test** (`test/integration/click_test.go` — build tag `//go:build integration`):
    ```go
    func TestEndToEndClick(t *testing.T) {
      // Requires: DATABASE_URL, VALKEY_URL, CLICKHOUSE_URL set in env

      // 1. Insert test campaign into Postgres
      // 2. Start server in goroutine (random port)
      // 3. Send GET /{alias} request
      // 4. Assert: response is 302
      // 5. Sleep 1s (batch flush)
      // 6. Assert: ClickHouse has 1 row for this click_token
      // 7. Assert: is_bot=0 for normal UA
      // 8. Send Googlebot request
      // 9. Assert: ClickHouse has 1 row with is_bot=1
    }
    ```

    RUN: `go test -v -tags integration ./test/integration/...`
    with all DB env vars set.

    DO NOT use `time.Sleep` in production code — only in test setup.
    DO NOT panic in workers — always log and continue (self-healing workers).
    Workers use ctx.Done() for shutdown — no other termination mechanism.
  </action>
  <verify>
    # Build + run with all services
    go build -o /tmp/zai-tds ./cmd/zai-tds && \
    CLICKHOUSE_URL="localhost:9000" \
    DATABASE_URL="postgres://zai:zai_dev_pass@localhost:5432/zai_tds" \
    VALKEY_URL="localhost:6379" \
    go test -v -tags integration ./test/integration/ -run TestEndToEndClick -timeout 30s
  </verify>
  <done>
    - Integration test passes: TestEndToEndClick PASS
    - Server startup logs show all 4 workers started: "worker started" x4
    - Normal click: is_bot=0 in ClickHouse
    - Bot click (Googlebot): is_bot=1 in ClickHouse
    - click_token is a 32-char hex string in ClickHouse row
    - `go vet ./...` returns clean (no vet errors)
    - `go build ./...` exits 0
  </done>
</task>

## Success Criteria
- [ ] Full 23-stage Level 1 pipeline runs on every click (stubs for 7-12, 14-19)
- [ ] HTTP 302 returned for valid campaign alias
- [ ] ClickHouse `clicks` table has row within 1 second of click request
- [ ] is_bot=1 for Googlebot UA, is_bot=0 for real browser UA
- [ ] ClickToken is 32-char cryptographic hex string
- [ ] 4 background workers start on server launch (click-writer, hitlimit-reset, cache-warmup, session-janitor)
- [ ] Integration test `TestEndToEndClick` passes
- [ ] `go vet ./...` is clean
