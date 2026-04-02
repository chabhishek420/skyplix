# Phase 4 Research — Advanced Cloaking & Bot Detection

> **Discovery Level**: 0 (Skip) — All work follows established codebase patterns using reference PHP source.
> **Date**: 2026-04-03

## Research Summary

Phase 4 research was completed in the previous session (Session 2026-04-02 23:15). All 5 reference codebases were analyzed and findings documented in the journal. Key reference files:

### Keitaro PHP Source (primary reference)
- `UserBotListService.php` — 54 hardcoded bot UA signatures + `stristr()` match
- `UserBotsService.php` — IP range management (CIDR/range/single → sorted int arrays, binary search, merge/exclude ops)
- `Remote.php` — Reverse proxy action with 60s file-based TTL cache (curl fetch → filesystem cache → serve)
- `Pipeline.php` — ToCampaign recursive pipeline re-entry (up to 10 levels, payload field reset)

### YellowCloaker (secondary reference)
- 12-layer detection: IP base → custom blacklist → VPN/Tor API → UA → OS → country → language → referrer → URL tokens → URL patterns → ISP
- Safe page delivery in 4 modes: folder/curl/redirect/error

### yljary Investigation (real-world lesson)
- Operators do NOT rely on UA/referrer alone
- Infrastructure-level detection (datacenter IPs, VPN databases) is the production standard

## Current State (what exists)

| Component | Status | Location |
|-----------|--------|----------|
| Basic bot detection (43 UA patterns + 5 CIDR + empty UA) | ✅ Working | `3_build_raw_click.go` |
| `RemoteProxyAction` (basic reverse proxy) | ✅ Working (no cache) | `action/proxy.go` (59 lines) |
| `IsBotFilter` stream filter | ✅ Working | `filter/detection.go` |
| `HideClickDetectFilter` stub | 🟡 Returns true (pass-through) | `filter/detection.go` |
| `ImkloDetectFilter` stub | 🟡 Returns true (pass-through) | `filter/detection.go` |
| `LocalFileAction` content action | ✅ Working | `action/content.go` |
| `Status404Action` / `DoNothingAction` | ✅ Working | `action/content.go` |
| `ShowHtmlAction` | ✅ Working | `action/content.go` |
| `ToCampaignAction` (simple 302) | ✅ Working (not recursive) | `action/special.go` |
| NoOp stage 22 (CheckSendingToAnotherCampaign) | 🔴 Stub | `noop.go` |
| GeoIP resolver | ✅ Working (when .mmdb present) | `geo/geo.go` |
| MaxMind ASN/ISP lookup | 🔴 Not implemented | — |
| Admin bot IP CRUD | 🔴 Not implemented | — |
| Valkey-backed IP store | 🔴 Not implemented | — |

## No External Research Needed

All patterns are already documented from the reference analysis session. The implementation maps directly to Go idioms:
- Keitaro's sorted int arrays → Go `sort.Search()` over `[]uint32`
- Keitaro's file-based TTL cache → Go `sync.Map` with expiry timestamps (in-memory is faster, no filesystem overhead)
- Keitaro's `UserBotsService` merge/exclude → Go sort + merge algorithm

## Key Dependencies

- `encoding/binary` — IP to uint32 conversion (standard library)
- `crypto/sha256` — Cache key hashing for Remote TTL cache (standard library)
- No new go.mod dependencies required for P0
