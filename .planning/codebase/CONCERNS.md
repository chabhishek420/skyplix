# Codebase Concerns

**Analysis Date:** 2026-04-03

## Tech Debt

**Bypassing Repository Layer:**
- Issue: Several pipeline stages directly use the `Cache` or `Valkey` clients instead of a unified repository or service layer. This makes testing harder and leaks implementation details.
- Files: `internal/pipeline/stage/4_find_campaign.go`, `internal/pipeline/stage/9_choose_stream.go`, `internal/pipeline/stage/12_choose_offer.go`
- Impact: Increased coupling between pipeline stages and specific storage implementations (Redis/Valkey).
- Fix approach: Introduce a domain-driven repository layer or use the existing `Service` pattern (like `lpTokenSvc`) for all entities.

**Manual JSON Marshalling in Cache:**
- Issue: The `Cache` service manually marshals and unmarshals JSON for every entity.
- Files: `internal/cache/cache.go`
- Impact: Performance overhead in the hot path and repetitive code.
- Fix approach: Implement a generic helper or use a more efficient serialization format (e.g., Protobuf or MessagePack) if performance becomes a bottleneck.

**Large Pipeline Payload:**
- Issue: The `Payload` struct is becoming quite large as it threads through 23+ stages.
- Files: `internal/pipeline/pipeline.go`
- Impact: Memory allocation overhead for each request.
- Fix approach: Consider splitting the payload into smaller, stage-specific context objects or using an interface to limit access to only necessary fields.

## Known Bugs

**Case-Sensitivity in Filter Types:**
- Issue: Historically, filter type lookups were case-sensitive, which caused issues with external DB/UI sources.
- Files: `internal/filter/filter.go`
- Impact: Filters might fail to load or match if the casing doesn't exactly match "Title Case".
- Trigger: Registering a filter with "country" but looking it up as "Country".
- Workaround: A recent fix added `cases.Title` normalization, but existing data in DB might still have inconsistent casing.

**ClickHouse Batch Drop:**
- Issue: If the ClickHouse writer channel is full, clicks are silently dropped (though logged).
- Files: `internal/pipeline/stage/23_store_raw_clicks.go`
- Impact: Potential data loss during extreme traffic spikes.
- Trigger: ClickHouse being slow or down while traffic is high (> 10k records in buffer).
- Workaround: Monitor logs for "channel full" warnings and scale ClickHouse or increase buffer size.

## Security Considerations

**Unprotected Admin API:**
- Issue: The admin API handlers seem to rely on API keys added recently, but older migrations didn't have robust auth.
- Files: `internal/admin/handler/`, `db/postgres/migrations/005_add_api_key.up.sql`
- Risk: Potential unauthorized access to campaign configuration if the API key middleware is bypassed or misconfigured.
- Current mitigation: API key validation in middleware.
- Recommendations: Implement role-based access control (RBAC) and audit logging for all mutations.

**IP Spoofing:**
- Issue: `extractRealIP` trusts `X-Forwarded-For` and `X-Real-IP` headers blindly.
- Files: `internal/pipeline/stage/3_build_raw_click.go`
- Risk: Attackers can spoof their IP to bypass geo-filtering or rate limiting.
- Current mitigation: None detected.
- Recommendations: Add a configuration for "trusted proxies" and only accept these headers from known sources.

## Performance Bottlenecks

**Sequential Pipeline Execution:**
- Problem: The pipeline runs stages sequentially. While mostly non-blocking, some stages (like GeoIP or Valkey lookups) add latency.
- Files: `internal/pipeline/pipeline.go`
- Cause: Synchronous loop over stages.
- Improvement path: Identify independent stages (e.g., Geo vs. UA parsing) and run them in parallel using `errgroup`.

**Reflective Rotator:**
- Problem: The `Rotator.Pick` method uses `interface{}` and type assertions.
- Files: `internal/rotator/rotator.go`, `internal/pipeline/stage/9_choose_stream.go`
- Cause: Generic implementation to support streams, offers, and landings.
- Improvement path: Use Go generics (introduced in 1.18+) to avoid type assertions and improve performance.

## Fragile Areas

**3-Tier Stream Selection:**
- Files: `internal/pipeline/stage/9_choose_stream.go`
- Why fragile: The logic for FORCED -> REGULAR -> DEFAULT is complex and relies on correct `Position` and `Type` fields. Small changes in selection logic can have large impacts on traffic distribution.
- Safe modification: Extensive unit tests for all selection scenarios.
- Test coverage: Partially covered by integration tests, but needs more edge-case unit tests.

**Macro Replacement:**
- Files: `internal/macro/macro.go`
- Why fragile: Uses string replacement for a variety of tokens. If not carefully managed, it could lead to broken URLs or security issues (e.g., if a token contains control characters).
- Safe modification: Use a structured parser or builder for URL construction.

## Dependencies at Risk

**Go-Redis (v9):**
- Risk: High dependency on Valkey/Redis for the hot path. If Valkey is down, the TDS stops processing clicks.
- Impact: Complete service outage.
- Migration plan: Implement a degraded mode where clicks are processed using local cache or default settings if Redis is unavailable.

## Missing Critical Features

**Real-time Monitoring Hooks:**
- Problem: No obvious hooks for real-time alerting on click drops or high latency within the pipeline.
- Blocks: Proactive response to infrastructure issues.

## Test Coverage Gaps

**GeoIP Resolver:**
- What's not tested: Behavior when MaxMind DBs are missing or corrupt.
- Files: `internal/geo/geo.go`
- Risk: Server might panic or fail to start in production environments.
- Priority: Medium

---

*Concerns audit: 2026-04-03*
