# TODO.md — Pending Items

## Phase 4.9 Infrastructure Hardening
- [x] Implement recursive re-dispatch for `ToCampaignAction` (max 10 hops)
- [x] Implement Stage 21 (Final Click Preparation)
- [x] Implement Stage 22 (Post-Action Cross-Checks)
- [x] Implement Campaign/Stream cloning endpoints (Admin API)
- [x] Implement Settings bulk-upsert endpoint (Admin API)

### Phase 4.9.4: Gap Closure & Uniqueness Hardening
**Status**: ✅ Complete (Latency: 2.06ms p99)
- [x] Implement `UpdateGlobalUniquenessStage` (Global TDS uniqueness check)
- [x] Implement `IsUniqueGlobal` check in `session.Service`
- [x] Establish p99 latency baseline under 1k RPS load (Result: 2.06ms)
- [x] Research ClickHouse partitioning for Phase 5 (Deferred to 5.1 task)

## Advanced Research & P2 Prep (Phase 4.9.3)
- [ ] Decide on ClickHouse partitioning and indexing strategy for attribution Performance
- [ ] Research JA3/JA4 TLS fingerprinting for Go (P2 browser verification)
- [ ] Research FingerprintJS open-source alternatives for JS-based challenges

## Phase 5: Conversion Tracking & Analytics ✅ Complete (verified 2026-04-03)
- [x] Implement Attribution Service (Valkey + ClickHouse)
- [x] Implement Postback (S2S) listener endpoint
- [x] Implement Reporting Query Builder (Campaign, Geo, Device drilldowns)
- [x] Implement ClickHouse Materialized Views for real-time stats aggregation
- [x] Define postback URL template macros (12 Keitaro-compatible macros)

## Deferred to Later Phases
- [ ] Research Grafana dashboard templates for TDS metrics (Phase 7)
- [ ] Evaluate Kubernetes Helm chart for production deployment (Phase 7)
- [ ] Design data migration script from existing Keitaro MySQL → ZAI PostgreSQL
- [ ] Implement epsilon-greedy multi-armed bandit for optimization (from Keitaro reference)

## Documentation Hygiene
- [ ] Keep hierarchical `CLAUDE.md` files in sync after directory/file structure changes (regenerate outside `reference/` and preserve manual sections)
