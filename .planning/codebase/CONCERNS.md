# Codebase Concerns

**Analysis Date:** 2026-04-02

## Tech Debt

**[Pipeline Stage Gaps]:**
- Issue: 13 of 23 pipeline stages are stub NoOps (stages 7-12, 14-19, 21-22)
- Files: `internal/pipeline/stage/noop.go`, `internal/server/routes.go` (lines 70-75, 80-93)
- Impact: Core features like stream selection, uniqueness tracking, hit limiting, cost calculation, and cookie management are not implemented
- Fix approach: Implement stages incrementally in subsequent phases per ROADMAP.md

**[Device Detection - Basic Library]:**
- Issue: Using `mileusna/useragent` which lacks DeviceModel/Brand accuracy
- Files: `internal/device/detector.go`
- Impact: Mobile device brand detection is inaccurate; Phase 2 planned upgrade to `robicode/device-detector` with PCRE
- Fix approach: Upgrade to device-detector library (Phase 2 per `detector.go` line 19)

**[Bot Detection - Hardcoded Patterns]:**
- Issue: Bot detection uses hardcoded UA patterns and limited IP ranges instead of database-backed approach
- Files: `internal/pipeline/stage/3_build_raw_click.go` (lines 137-172)
- Impact: Incomplete bot detection; crawlers/spiders not in pattern list will pass through
- Fix approach: Phase 4 upgrade to ipinfo.io ASN-based bot mapping

**[No ClickHouse Schema Migration System]:**
- Issue: Manual SQL files in `db/clickhouse/migrations/` with no version control or tooling
- Files: `db/clickhouse/migrations/001_create_clicks.sql`, `db/clickhouse/migrations/002_create_conversions.sql`
- Impact: Schema changes require manual intervention; no rollback capability
- Fix approach: Add sqlc or golang-migrate for versioned migrations

**[Fallback Hardcoded Offer URL]:**
- Issue: `ExecuteActionStage` uses `https://example.com` as fallback when no offer is selected
- Files: `internal/pipeline/stage/20_execute_action.go` (line 31)
- Impact: Clicks may redirect to unexpected destination; test/dev data could leak
- Fix approach: Implement proper offer rotation in Phase 2, or fail with 404 instead

---

## Known Bugs

**[ClickHouse Write Failure Silently Drops Records]:**
- Symptoms: Click records lost when ClickHouse batch send fails
- Files: `internal/queue/writer.go` (lines 275-280)
- Trigger: ClickHouse connection timeout or query error during `b.Send()`
- Workaround: None — records are dropped without alerting
- Fix approach: Add dead-letter queue or persistent retry buffer

**[Channel Backpressure Silently Drops Clicks]:**
- Symptoms: Clicks lost when ClickHouse writer channel (10k buffer) is full
- Files: `internal/pipeline/stage/23_store_raw_clicks.go` (lines 34-40)
- Trigger: High traffic burst exceeding flush rate
- Workaround: Channel capacity is high (10k) so rare under normal load
- Fix approach: Add metrics for dropped clicks; consider bounded queue with alerting

**[Pipeline Error Aborts Early - StoreRawClicks May Miss Data]:**
- Symptoms: If earlier stage returns error, click may not be stored
- Files: `internal/pipeline/pipeline.go` (lines 70-77)
- Trigger: Any stage error causes immediate abort
- Workaround: StoreRawClicksStage has `AlwaysRun() = true` but only if pipeline doesn't error
- Fix approach: Ensure error handling in stages returns nil, using Abort pattern instead

---

## Security Considerations

**[IP Spoofing via X-Forwarded-For Header]:**
- Risk: Attackers can set arbitrary X-Forwarded-For values to bypass geo-targeting or rate limits
- Files: `internal/pipeline/stage/3_build_raw_click.go` (lines 103-109)
- Current mitigation: None — first IP in X-Forwarded-For is trusted blindly
- Recommendations: Validate against known proxy IP ranges; require X-Forwarded-For be from trusted proxies only

**[No Rate Limiting on Click Endpoints]:**
- Risk: DDoS or click fraud via unlimited click requests
- Files: `internal/server/routes.go` (lines 29-31)
- Current mitigation: None
- Recommendations: Add per-IP/per-campaign rate limiting using Valkey in Phase 2

**[No Authentication on Admin/API Routes]:**
- Risk: Unauthenticated access to any admin functionality (once routes are added)
- Files: `internal/server/routes.go` (line 27 health endpoint is public only)
- Current mitigation: Only health check is exposed; admin routes not yet implemented
- Recommendations: Implement JWT/session auth before admin UI

**[Hardcoded Default Salt in Config]:**
- Risk: Using `change-me-in-production-min-32-chars` in production would compromise token security
- Files: `config.yaml` (line 25), `internal/config/config.go` (line 136-139)
- Current mitigation: Validation fails in production mode unless salt is changed
- Recommendations: Enforce secure salt via environment variable only in production

---

## Performance Bottlenecks

**[Direct Postgres Queries - No Caching]:**
- Problem: Campaign lookup hits PostgreSQL on every click
- Files: `internal/pipeline/stage/4_find_campaign.go` (lines 54-60)
- Cause: Phase 1 intentionally skips Valkey caching for simplicity
- Improvement path: Phase 2 implements campaign entity caching in Valkey

**[GeoIP Lookup on Every Request]:**
- Problem: MaxMind GeoIP database lookup adds latency to each click
- Files: `internal/pipeline/stage/6_update_raw_click.go` (line 31), `internal/geo/geo.go` (lines 64-79)
- Cause: In-memory DB but still requires lookup
- Improvement path: Cache frequent country codes in-memory or use connection pooling

**[ClickHouse Batch Flush Timing]:**
- Problem: 500ms flush interval + 5000 record batch size means data may be delayed
- Files: `internal/queue/writer.go` (lines 152-185)
- Cause: Trade-off between write efficiency and data freshness
- Impact: Analytics data up to 500ms stale; high-traffic bursts may exceed buffer

**[Worker Panic Recovery - Silent Failure]:**
- Problem: Workers catching panics without proper error propagation
- Files: `internal/worker/worker.go` (lines 36-46)
- Cause: Panic in worker goroutine only logs error, doesn't restart worker
- Impact: Background tasks may stop silently without recovery

---

## Fragile Areas

**[Pipeline Abort Code Handling]:**
- Files: `internal/pipeline/pipeline.go`, `internal/server/routes.go` (lines 111-115)
- Why fragile: Multiple places set Abort/AbortCode; order matters; response may be double-written
- Safe modification: Only use Abort pattern in stages; avoid returning errors from non-critical stages
- Test coverage: Integration test doesn't verify abort paths

**[XFF Parsing Edge Cases]:**
- Files: `internal/pipeline/stage/3_build_raw_click.go` (lines 103-124)
- Why fragile: Handles comma-separated XFF but doesn't validate IP authenticity
- Safe modification: Add trust boundary checks for proxy IPs
- Test coverage: Not covered in tests

**[ClickHouse UUID Handling]:**
- Files: `internal/queue/writer.go` (lines 215-230)
- Why fragile: Falls back to zero UUID for empty strings; may cause data integrity issues
- Safe modification: Ensure campaign/stream IDs are always set before flush
- Test coverage: Only tests with valid campaigns

---

## Scaling Limits

**[ClickHouse Writer Channel Capacity]:**
- Current capacity: 10,000 records in buffered channel
- Limit: High traffic bursts can overflow channel (non-blocking send drops records)
- Scaling path: Increase channel size with memory trade-off; add dead-letter queue for persistence

**[PostgreSQL Connection Pool - Default Settings]:**
- Current capacity: pgx default pool (likely 4-10 connections)
- Limit: May bottleneck under high concurrency
- Scaling path: Configure `pgxpool.Config{MaxConns}` based on workload

**[No Horizontal Scaling Support]:**
- Current: Single-instance Go server
- Limit: Vertical scaling only
- Scaling path: Stateless design enables load balancing; add shared Valkey for coordination

**[Valkey Memory - Session Storage]:**
- Current: Session data with TTL
- Limit: Depends on dataset size and traffic patterns
- Scaling path: Use Valkey cluster for sharding in Phase 3+

---

## Dependencies at Risk

**[mileusna/useragent - Limited Accuracy]:**
- Risk: Basic user-agent parsing misses device brands and newer browsers
- Impact: DeviceType may be "mobile" without identifying iPhone vs Samsung
- Migration plan: Phase 2 upgrade to `robicode/device-detector` (requires PCRE/CGO)

**[go-redis/v9 - Redis Compatibility]:**
- Risk: Using Redis client for Valkey (binary-compatible but not guaranteed)
- Impact: Subtle compatibility issues possible with Valkey 8 specific features
- Migration plan: Monitor for issues; consider `valkey-go` if available

**[clickhouse-go/v2 - Active Development]:**
- Risk: v2 API changes may require migration
- Impact: ClickHouse write path would need updates
- Migration plan: Pin to specific minor version; test on ClickHouse upgrades

---

## Missing Critical Features

**[Conversion Tracking - Level 2 Pipeline]:**
- Problem: Only Level 1 click pipeline implemented (23 stages); Level 2 (13 stages) for Landing→Offer linking not started
- Blocks: Attributing conversions back to clicks; affiliate network postback

**[Stream Selection - 3-Tier Hierarchy]:**
- Problem: No stream filtering or selection logic (FORCED → REGULAR → DEFAULT)
- Blocks: Core TDS functionality of routing visitors to different offers

**[Offer Rotation - Weighted Random]:**
- Problem: No offer selection with WEIGHT/POSITION logic
- Blocks: Even traffic distribution across offers; campaign monetization

**[Cost/Payout Calculation]:**
- Problem: No cost model or payout calculation implemented
- Blocks: Profit tracking; affiliate network integration

**[Uniqueness Tracking]:**
- Problem: Stages 8, 10, 18 (uniqueness checks) are no-ops
- Blocks: Accurate unique visitor counts; fraud detection

**[Cookie-Based Visitor Binding]:**
- Problem: Stage 19 (SetCookie) and visitor_code tracking not implemented
- Blocks: Multi-touch attribution; consistent visitor identification

---

## Test Coverage Gaps

**[No Unit Tests]:**
- What's not tested: Individual pipeline stages, config validation, geo resolver, device detector
- Files: Entire `internal/` directory lacks `*_test.go` files
- Risk: Logic errors in stages undetected until integration testing
- Priority: HIGH

**[Limited Integration Test]:**
- What's not tested: Abort paths, error conditions, ClickHouse failures, Postgres failures
- Files: `test/integration/click_test.go`
- Risk: Edge cases fail silently in production
- Priority: HIGH

**[No Performance Benchmarks]:**
- What's not tested: p50/p95/p99 latency, throughput under load, memory usage
- Files: None
- Risk: Performance regressions undetected; latency targets (<5ms p99) unverified
- Priority: MEDIUM

**[No Bot Detection Tests]:**
- What's not tested: Various bot UA patterns, IP range matching, edge cases
- Files: `internal/pipeline/stage/3_build_raw_click.go`
- Risk: False negatives in bot detection
- Priority: MEDIUM

**[No E2E Tests for Click Flow]:**
- What's not tested: Full click → redirect → conversion attribution chain
- Files: `test/integration/click_test.go` only covers click → ClickHouse
- Risk: Integration breaks undetected
- Priority: HIGH

---

*Concerns audit: 2026-04-02*
