## Current Position
- **Phase**: Phase 4 — Advanced Cloaking & Bot Detection
- **Task**: Phase 4 P0 execution (just started)
- **Status**: Paused at 2026-04-03 00:00 IST

## Last Session Summary
1. **Reference Analysis Complete** — Deep-dived all 5 reference codebases (Keitaro PHP, AKM Tracker, KeitaroCustomScripts, YellowCloaker, yljary-investigation). Created `reference_analysis.md` artifact.
2. **GSD Audit Complete** — Found and fixed 7 inaccuracies across STATE/ROADMAP/TODO/RESEARCH/DECISIONS. Created `gsd_audit.md` artifact.
3. **SkyPlix vs yljary Analysis** — Comprehensive comparison proving SkyPlix can handle yljary-scale (10K+ campaigns) after Phase 4 P0. Created `skyplix_vs_yljary.md` artifact.
4. **Security Fix Committed** — Replaced `FIXME_HASH_` password with bcrypt cost-12 + `crypto/rand` API key generation. Committed `3aa30399`.
5. **Phase 4 P0 Started** — Was about to begin IP range/CIDR management engine when paused.

## In-Progress Work
- Security fix committed and clean (`go build ./...` passes)
- No uncommitted changes
- Phase 4 P0 code NOT yet started (only planning done)

## Blockers
None — ready to execute.

## Context Dump

### Codebase Metrics (verified)
- 78 Go source files, 6,824 lines of code, 22 internal packages
- 28 filter types, 19 action types, 24 pipeline stage files
- 43 bot UA patterns (target: 54), 5 bot IP CIDR ranges
- 6 PG migrations (001-006), 2 CH migrations
- Go 1.25.6, 11 production dependencies

### Phase 4 P0 Attack Plan (approved by user)
```
Day 1-2:  IP range/CIDR management engine (port UserBotsService.php)
Day 3-4:  Datacenter/VPN detection (MaxMind ASN + ipinfo.app API)
Day 5-6:  Safe page system + Remote action TTL cache enhancement
Day 7-8:  UA signature expansion (43→54+) + referrer/URL token filters
Day 9-10: Integration testing + verification
```

### Key Implementation Decisions
- `RemoteProxyAction` already exists in `proxy.go` (59 lines) — needs TTL cache enhancement, NOT full rewrite
- `ToCampaignAction` exists as simple 302 redirect — needs conversion to recursive pipeline (deferred to P3)
- Bot detection runs inline in `BuildRawClickStage` (stage 3) per ADR-008
- Safe page config should be per-stream via `ActionPayload` field (already a `map[string]interface{}`)

### Key Reference Files
- `reference/Keitaro_source_php/.../UserBotListService.php` — 54 bot signatures
- `reference/Keitaro_source_php/.../UserBotsService.php` — IP range management
- `reference/Keitaro_source_php/.../Remote.php` — reverse proxy with TTL cache
- `reference/YellowCloaker/core.php` — 12-layer detection engine

### Technical Debt (remaining)
- 🟡 `strings.Title` deprecated in filter.go and action.go (cosmetic)
- 🟡 Stages 21-22 are NoOp stubs
- 🟡 Phase 3 Task 3.4 gaps (cloning, domain validation, settings bulk-upsert)
- 🟢 SessionJanitorWorker is a no-op

## Next Steps
1. **Create `internal/botdb/` package** — IP range/CIDR management (binary search over sorted int ranges, supports single/CIDR/range, merge/exclude ops, Valkey-backed hot storage)
2. **Wire into `BuildRawClickStage`** — Add check #4 after UA pattern match: `botdb.Contains(ip)` 
3. **Add admin API endpoints** — `POST/DELETE /api/bots/ips`, `GET /api/bots/ips/list`
4. **Expand UA signatures** — Port remaining 11 Keitaro patterns to `botUAPatterns` list
5. **MaxMind ASN integration** — Load ASN database, check `IpInfoType` for hosting/datacenter classification
