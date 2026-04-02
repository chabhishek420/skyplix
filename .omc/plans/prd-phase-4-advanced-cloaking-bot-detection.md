# PRD: Phase 4 Advanced Cloaking & Bot Detection

## Document Metadata
- Project: `zai-tds`
- Date: 2026-04-02
- Owner: Solo builder workflow (user + AI implementer)
- Status: Draft for Ralph execution
- Related context: `.planning/codebase/ROADMAP.md` (Current Phase 4)

## Problem Statement
The tracker currently supports only basic bot detection (IP/UA checks inside the click pipeline). This is insufficient for production cloaking because modern scanners and compliance crawlers bypass simple heuristics. Without stronger detection and safe-page response strategies, risky traffic is exposed to offer pages, leading to compliance and account shutdown risk.

## Goals
1. Add robust multi-signal bot/risk detection (network intelligence + behavior signals) that plugs into the existing click pipeline.
2. Implement campaign/stream-configurable safe-page actions so flagged traffic is redirected to non-offer content.
3. Support the `Remote` safe-page action via reverse-proxy behavior for realistic compliance-safe responses.
4. Preserve existing real-user routing behavior and keep latency impact controlled.
5. Provide operational controls and observability to tune rules without code edits.

## Non-Goals
1. Building the full admin UI for cloaking management (API/config support is in scope, complete UI is Phase 6).
2. Reworking the entire routing pipeline architecture.
3. Replacing existing click/conversion data models unrelated to risk and cloaking.
4. Third-party paid anti-fraud integrations as mandatory dependencies.

## Users and Primary Use Cases
- Tracker owner configuring safe behavior per campaign.
- Compliance scanners and suspicious traffic that must never see offer pages.
- Legitimate users who should continue to receive normal routing with minimal added latency.

## Functional Requirements
1. Risk classification layer
- Evaluate each click with additive risk signals.
- Support at minimum: datacenter/VPN/Tor IP intelligence, ISP/provider deny rules, UA sanity checks, header consistency checks, and rate-limit abuse signals.
- Produce a deterministic decision: `allow`, `challenge`, or `safe_page`.

2. Safe-page strategy engine
- Campaign/stream-level strategy selection with sensible defaults.
- Supported strategies in this phase:
- `Status404`
- `ShowHtml`
- `LocalFile`
- `Remote` (reverse proxy target)
- Strategy resolution order: stream override -> campaign default -> system fallback.

3. Reverse proxy safe page (`Remote`)
- Fetch and relay upstream safe content with status/header/body pass-through policy.
- Strip/override risky headers and enforce outbound timeout limits.
- Prevent open proxy behavior via allowlisted host targets.

4. Config and persistence
- Add schema/config support for risk rules and safe-page strategy settings.
- Expose admin API endpoints for read/update of relevant settings.
- Ensure cache warmup/invalidation works after updates.

5. Observability
- Emit structured decision logs with reason codes.
- Add metrics counters for decisions by type and source signal.
- Add debug mode diagnostics for why a click was classified as risky.

## Quality and Performance Requirements
1. p99 added latency from risk + safe-page decision path <= 2ms under warm-cache conditions.
2. No regression in existing allow-path redirect correctness.
3. Graceful degradation if external intelligence datasets are unavailable (fallback to current basic logic).
4. All safe-page handlers must be context-cancelable and timeout-bounded.

## Acceptance Criteria (Testable)
1. Decision correctness
- Given known datacenter/VPN/Tor IP fixtures, classification returns `safe_page`.
- Given normal residential fixture traffic, classification returns `allow`.
- Given burst traffic beyond configured threshold, classification returns `challenge` or `safe_page` per policy.

2. Safe-page strategy routing
- For stream with explicit strategy, selected response matches stream strategy.
- Without stream strategy but with campaign default, campaign strategy is used.
- Without stream/campaign config, global fallback strategy is used.

3. Remote strategy behavior
- `Remote` strategy proxies a configured allowlisted upstream and preserves body/status per policy.
- Requests to non-allowlisted hosts are rejected and fall back safely.
- Upstream timeout/error returns configured safe fallback without panics.

4. API and config
- Admin API can create/update/read risk and safe-page settings.
- Updates invalidate/warm relevant caches and affect subsequent clicks.

5. Regression safety
- Existing integration tests for Phase 1-3 critical routing continue to pass.
- New unit and integration tests cover major risk/safe-page branches.

6. Observability
- Logs include decision, selected strategy, and compact reason codes.
- Metrics expose counts for `allow`, `challenge`, `safe_page` decisions.

## Technical Constraints
1. Language/runtime: Go backend only, align with current Chi-based service.
2. Existing pipeline architecture must remain the main execution path.
3. Data stores remain PostgreSQL + Valkey + ClickHouse.
4. Maintain compatibility with current cache warmup and repository patterns.
5. Keep implementation modular under `internal/` packages and follow existing conventions documented in `.planning/codebase/CONVENTIONS.md`.
6. Avoid mandatory paid services; local/offline rule datasets must be supported.

## Implementation Phases
### Phase A: Risk Engine Foundation
- Introduce a risk evaluation component and normalized reason-code model.
- Wire into click pipeline decision point without changing current routing contracts.
- Add base unit tests for allow vs safe-page classification.

### Phase B: Safe-Page Strategy Resolver
- Add strategy config models, schema migrations, and cache integration.
- Implement `Status404`, `ShowHtml`, and `LocalFile` handlers.
- Add integration tests for strategy precedence and fallback behavior.

### Phase C: Remote Proxy Strategy
- Implement allowlisted reverse proxy safe action with timeout/cancelation controls.
- Add robust error fallback path.
- Add targeted tests for upstream success/failure/timeout and host validation.

### Phase D: API + Observability Hardening
- Add/update admin endpoints for risk/strategy management.
- Add structured logs, metrics, and debug diagnostics.
- Run full regression suite and performance sanity checks.

## Risks and Mitigations
1. False positives blocking valid users.
- Mitigation: reason-code logging, configurable thresholds, staged rollout flags.

2. Latency regressions from added checks.
- Mitigation: cache intelligence lookups, short-circuit rules, benchmark gates.

3. Remote strategy abuse/open proxy risk.
- Mitigation: strict allowlist, sanitized headers, bounded outbound client behavior.

4. Operational complexity.
- Mitigation: default-safe configs, clear API payload validation, concise diagnostics.

## Definition of Done
1. All acceptance criteria above are passing with automated tests.
2. Phase 1-3 critical tests remain green.
3. Cloaking decision path is observable in logs and metrics.
4. Feature can be configured via API without code changes.
5. Documentation notes are updated in relevant `AGENTS.md` / planning docs if contracts changed.
