# DECISIONS.md — Architecture Decision Records

## ADR-001: Go over TypeScript/Next.js for TDS Core
**Date**: 2026-04-01
**Status**: Accepted
**Context**: Previous implementation used Next.js + Prisma + SQLite.
Fundamental performance bottlenecks: SQLite single-writer lock, Prisma ORM
reflection overhead on hot path, Node.js blocking during CPU-bound bot
detection, framework overhead adding 10-50ms per request.
**Decision**: Full rewrite in Go. Legacy Next.js archived in `reference/legacy-nextjs/`.
**Consequences**: 10-100x performance gain. Single binary deployment. Need Go expertise.

## ADR-002: PostgreSQL + Valkey + ClickHouse
**Date**: 2026-04-01
**Status**: Accepted
**Context**: Keitaro uses MySQL for everything (config + clicks + stats).
This is a known scalability ceiling — MySQL click tables degrade past 10M rows.
**Decision**: Split storage by access pattern:
- PostgreSQL: transactional config (campaigns, streams, offers, users)
- Valkey 8: hot-path cache, async write buffer, sessions, uniqueness, rate limiting
- ClickHouse 24: columnar click analytics (billions of rows, sub-second aggregation)
**Consequences**: Three databases to manage. Docker Compose simplifies this.
Valkey chosen over Redis for BSD open-source license (drop-in compatible).

## ADR-003: Chi v5 over Fiber
**Date**: 2026-04-01
**Status**: Accepted
**Context**: Fiber (fasthttp) leads synthetic benchmarks by ~20%.
However, real-world TDS bottleneck is GeoIP (1-5ms) + Valkey (0.5ms),
not routing (<50µs). Fiber is incompatible with Go's `net/http` ecosystem.
**Decision**: Chi v5 — fully stdlib-compatible, idiomatic, zero vendor lock-in.
**Consequences**: Every Go middleware and library works without adaptation.

## ADR-004: sqlc + pgx over GORM
**Date**: 2026-04-01
**Status**: Accepted
**Context**: Keitaro uses raw SQL with ADODB (no ORM). Our RESEARCH verified
that sqlc generates compile-time safe Go code from SQL with zero runtime
reflection. GORM uses heavy reflection and hides N+1 query patterns.
**Decision**: sqlc for 95% of queries, squirrel for dynamic admin list filters.
pgx v5 as the native PostgreSQL driver with pgxpool.
**Consequences**: Must write SQL by hand. Better performance, full query control.

## ADR-005: Valkey Session Tokens over JWT
**Date**: 2026-04-01
**Status**: Accepted
**Context**: Stateless JWTs cannot be revoked. For a team tool with multiple
media buyers, you need instant forced-logout, session listing, and
"log out all devices" after security incidents.
**Decision**: Session ID in HTTP-only cookie. Session data stored in Valkey
with 24h TTL. Admin can list/revoke sessions per user.
**Consequences**: Slightly more complexity than JWT. Full control over sessions.

## ADR-006: Vite + React over Next.js for Admin UI
**Date**: 2026-04-01
**Status**: Accepted
**Context**: Admin panel is an internal tool — no SEO, no SSR needed.
Next.js Server Components and streaming are disabled in static export mode.
Using Next.js static export = paying framework overhead for zero benefit.
**Decision**: Vite + React 19 + shadcn/ui. Compiles to static HTML/JS/CSS.
Embedded in Go binary via `//go:embed`. Single process in production.
**Consequences**: No Node.js in production. Faster build. Manual routing
with react-router-dom instead of file-system routing.

## ADR-007: Two-Level Pipeline Architecture
**Date**: 2026-04-01
**Status**: Accepted
**Context**: Source verification of `Traffic/Pipeline/Pipeline.php` revealed
Keitaro uses TWO pipeline levels, not one:
- Level 1 (23 stages): campaign click → stream selection → landing redirect
- Level 2 (13 stages): landing click → offer selection → affiliate redirect
The `visitor_code` cookie ties both levels together.
**Decision**: Implement both pipeline levels. Reuse stages where Keitaro does
(13 of Level 2's stages are shared with Level 1, just reordered).
**Consequences**: Must implement LP token system for Level 1 → Level 2 linking.

## ADR-008: Bot Detection Inline in Pipeline (Phase 1, not Phase 4)
**Date**: 2026-04-02
**Status**: Accepted
**Context**: Source audit of `BuildRawClickStage.php` revealed bot detection
(`_checkIfBot` + `_checkIfProxy`) runs INSIDE pipeline stage 3, not as a
separate system. The `is_bot` flag set here feeds the `IsBot` stream filter
in `ChooseStreamStage` (stage 9). Without bot detection in the pipeline,
the IsBot filter silently passes all traffic — no cloaking is possible.
**Decision**: Basic bot detection (IP list + UA pattern match + empty UA check +
proxy detection) moves to Phase 1 as part of `BuildRawClickStage`. Advanced
detection (datacenter IP databases, JS fingerprint challenges) stays in Phase 4.
**Consequences**: Phase 1 deliverable is a functional cloaking-capable pipeline
from day one. Phase 4 upgrades detection accuracy, not introduces it.

## ADR-009: Entity Binding Required for Production (Phase 2)
**Date**: 2026-04-02
**Status**: Accepted
**Context**: Source audit revealed `EntityBindingService` in `ChooseStreamStage`.
When `campaign.bindVisitorsEnabled` is true, returning visitors are locked to
the same stream/landing/offer via Valkey keys + cookie fallback. Three binding
types: `s` (stream), `lp` (landing), `of` (offer). Without entity binding:
- Same visitor sees different offers on different visits → triggers affiliate
  network fraud detection
- A/B test results become unreliable (no visitor consistency)
- Conversion attribution breaks across visits
**Decision**: Entity binding implemented in Phase 2 alongside stream rotation.
Uses Valkey `bind:{type}:{uniqueness_id}` keys with cookie fallback.
Campaign model gains `bind_visitors` boolean field. Data model gains
`type` field (POSITION vs WEIGHT) on campaigns.
**Consequences**: Adds Valkey key complexity. Stream/landing/offer rotation
must check for existing bindings before selecting new entities.

## ADR-010: Device Detection Library Correction
**Date**: 2026-04-02
**Status**: Proposed
**Context**: Planning docs referenced `github.com/mssola/device-detector` which
does not exist. The actual `mssola` library is `github.com/mssola/user_agent`
(simple UA parser — browser/OS/mobile only). Six Keitaro stream filters
(`DeviceType`, `DeviceModel`, `Browser`, `BrowserVersion`, `Os`, `OsVersion`)
require full device detection including model and brand.
**Decision**: Evaluate `github.com/robicode/device-detector` (Go port of Matomo's
device-detector, full field parity) during Phase 1 setup. If PCRE dependency
conflicts with single-binary CGo-free deployment, fall back to
`github.com/mileusna/useragent` plus custom device model enrichment from
Matomo's YAML regex database.
**Consequences**: If using `robicode/device-detector`, binary requires CGo (PCRE).
If using `mileusna/useragent`, DeviceModel and DeviceBrand filters may have
reduced accuracy. Decision made via benchmark during Phase 1 scaffolding.

## ADR-011: Phase 4 Architecture — Multi-Layer Cloaking from Reference Analysis
**Date**: 2026-04-02
**Status**: Proposed
**Context**: Deep analysis of 5 reference codebases revealed the production cloaking
stack used by Keitaro, YellowCloaker, and real-world TDS deployments (yljary.com).
Key findings:
1. Keitaro's `UserBotListService` uses 54 hardcoded UA signatures + user-managed IP
   CIDR/range lists with binary search over sorted int ranges.
2. YellowCloaker implements a 12-layer detection engine (IP base → VPN/Tor API →
   UA → OS → country → language → referrer → URL tokens → ISP).
3. Keitaro's `Remote` action reverse-proxies real websites with 60s TTL file cache.
4. yljary investigation proved that real operators do NOT rely on UA/referrer —
   they use infrastructure-level detection (datacenter IPs, VPN databases).
5. Keitaro `Pipeline.php` supports recursive re-entry for `ToCampaign` action
   (up to 10 recursions with reset of all payload fields).
6. KeitaroCustomScripts includes epsilon-greedy multi-armed bandit for automatic
   landing optimization (5-min Redis cache, API-driven metric selection).
**Decision**: Phase 4 will implement detection in priority order:
- P0: IP range management + datacenter/VPN detection + expanded UA signatures + safe page system
- P1: ISP blacklisting + referrer analysis + URL token blocking + rate limiting
- P2: JS fingerprint challenges + third-party API integration
- P3: Pipeline recursion (ToCampaign) + behavioral analysis
Safe page delivery modeled on Keitaro's `Remote` action (HTTP client reverse proxy with TTL cache).
Bot detection remains inline in `BuildRawClickStage` (ADR-008) with expanded check layers.
**Consequences**: P0 alone gives production-viable cloaking. P1-P3 add depth.
`Remote` action requires HTTP client with timeout + file-based TTL cache.
Pipeline recursion requires careful state management to prevent infinite loops.
