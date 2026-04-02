# TODO.md — Pending Items

## Before Phase 1
- [x] Run `/plan 1` to create Phase 1 execution plan
- [x] Download MaxMind GeoLite2 databases (.mmdb files)
- [x] Set up local Docker services (Postgres, Valkey, ClickHouse)

## Security Debt (CRITICAL — before production)
- [ ] 🔴 Replace `FIXME_HASH_` password stub with bcrypt (`golang.org/x/crypto/bcrypt`) in `users.go:65`
- [ ] 🔴 Generate real API keys with `crypto/rand` instead of placeholder in `users.go:69`
- [ ] 🟡 Replace deprecated `strings.Title` with `golang.org/x/text/cases.Title` in `filter.go` and `action.go`

## Phase 4 Pre-Work (from Reference Analysis)
- [ ] Download MaxMind ASN database for datacenter/ISP detection
- [ ] Expand bot UA signatures from 43 → 54+ (port remaining Keitaro `UserBotListService.php` list)
- [ ] Port Keitaro's IP range management (CIDR/range/single with merge/exclude) from `UserBotsService.php`
- [ ] **Enhance** existing `RemoteProxyAction` with: TTL file-based cache (60s), URL rewriting, header passthrough (from `Remote.php`)
- [ ] **Convert** `ToCampaignAction` from simple 302 redirect → recursive pipeline re-entry with state reset (from `Pipeline.php` L60-73)
- [ ] Design safe page configuration schema (per-stream: Remote/LocalFile/Status404/ShowHtml)
- [ ] Evaluate ipinfo.app API for VPN/Tor detection (YellowCloaker uses this)
- [ ] Implement stages 21-22 (PrepareRawClickToStore, CheckSendingToAnotherCampaign) — currently NoOp

## Research Still Needed
- [x] ~~Evaluate `mssola/device-detector` vs `mileusna/useragent`~~ → Resolved: `mileusna/useragent v1.3.5` chosen and in use
- [ ] Research FingerprintJS open-source alternatives for JS-based bot detection (Phase 4)
- [ ] Decide on ClickHouse partitioning strategy for click tables
- [ ] Evaluate IP2Location PROXY database (paid) for VPN/proxy detection accuracy
- [ ] Research JA3/JA4 TLS fingerprinting for Go (Phase 4 P2)
- [ ] Research epsilon-greedy MAB implementation in Go for auto-optimization (Phase 5+, from `ywbegfilter.php`)

## Phase 3 Remaining (Task 3.4 gaps)
- [ ] Campaign/stream cloning endpoints
- [ ] Domain state management (activate/deactivate/DNS verify)
- [ ] Settings bulk-upsert endpoint
- [ ] Implement domain DNS validation (currently stubbed in `domains.go:176`)

## Deferred to Later Phases
- [ ] Define postback URL template macros (Phase 5)
- [ ] Research Grafana dashboard templates for TDS metrics (Phase 7)
- [ ] Evaluate Kubernetes Helm chart for production deployment (Phase 7)
- [ ] Design data migration script from existing Keitaro MySQL → ZAI PostgreSQL
- [ ] Implement epsilon-greedy multi-armed bandit for landing/offer auto-optimization (Phase 5+, from KeitaroCustomScripts)
