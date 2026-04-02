## Last Session Summary
Reference source code deep-dive complete (2026-04-02).
Analyzed all 5 reference codebases + verified GSD accuracy against codebase.

### Codebase Metrics (verified)
- 78 Go source files, 6,824 lines of code
- 22 internal packages
- 24 pipeline stage files (23 L1-mapped + l2_find_campaign + noop stubs)
- 28 filter types registered (filter.go L31-48)
- 19 action types registered (action.go L43-53)
- 43 bot UA patterns (3_build_raw_click.go L146-158) — target: 54 (Keitaro full list)
- 6 PostgreSQL migration pairs (001-006) + 2 ClickHouse migrations
- 10 production dependencies in go.mod (Go 1.25.6)

### Reference Analysis
1. **Keitaro PHP Source** — 54 bot UA signatures, IP CIDR management, Remote proxy (60s TTL), pipeline recursion (ToCampaign up to 10 levels), 28 stream filters, 19 action types
2. **AKM Traffic Tracker** — ClickHouse daily aggregation pattern
3. **KeitaroCustomScripts** — Epsilon-greedy MAB for landing optimization
4. **YellowCloaker** — 12-layer cloaking engine
5. **yljary-investigation** — Real-world: operator used infrastructure-level detection, NOT UA/referrer

## Current Phase
Phase 4 — Advanced Cloaking & Bot Detection (Not Started)

## Phase 4 Requirements (from reference analysis)
- P0: IP range/CIDR management (Keitaro UserBotsService pattern)
- P0: Datacenter/VPN/Tor IP detection (YellowCloaker ipinfo.app pattern)
- P0: **Enhance** existing Remote action with TTL cache (currently basic, no cache)
- P0: Safe page configuration schema (per-stream)
- P0: Expand UA signatures from 43 → 54+ (Keitaro full list)
- P1: ISP blacklisting, referrer analysis, URL token blocking
- P2: JS fingerprint challenges, third-party API integration
- P3: Convert ToCampaign from 302 redirect → pipeline recursion with state reset

## Completed Phases
- Phase 1: Foundation ✅
- Phase 1.5: Reliability Hardening ✅
- Phase 2: Campaign Engine ✅
- Phase 3: Admin API ✅ (core CRUD for 9 entity types; advanced ops from Task 3.4 pending: cloning, domain state management, settings bulk-upsert)

## Known Technical Debt
- 🔴 Password hashing: `FIXME_HASH_` stub in `users.go:65`
- 🔴 API key generation: placeholder in `users.go:69`
- 🟡 Deprecated `strings.Title` in filter.go and action.go
- 🟡 Stages 21-22 are NoOp stubs (PrepareRawClickToStore, CheckSendingToAnotherCampaign)
- 🟡 Domain DNS validation stubbed in domains.go:176
- 🟢 SessionJanitorWorker is a no-op
