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
## External Data Sources (New discovery)

Additional reference data cloned to `reference/external/`:

| Source | Content | Use Case |
|--------|---------|----------|
| `bad-asn-list` | 2,234 blocked ASNs with org names | Global infrastructure-level blocking (P0) |
| `lord-alfred-ipranges` | CIDR lists for Googlebot, Bing, AWS, etc. | Targeted crawler/VPN blocking (P1) |
| `prosper202` | Legacy PHP tracker source | Reference for alternative redirection patterns |
| `ipranges-singbox` | Detailed network-specific CIDRs | Advanced network/ISP detection backup |

### Priority Lists from Lord-Alfred:
- `googlebot/ipv4.txt` (confirmed crawler)
- `bing/ipv4.txt` (confirmed crawler)
- `amazon/ipv4.txt` (confirmed AWS infrastructure)
- `digitalocean/ipv4.txt` (confirmed DO infrastructure)
- `facebook/ipv4.txt` (confirmed FB crawler)
- `openai/ipv4.txt` (confirmed GPT crawler)

These lists will be used to prepopulate our `botdb` via the admin API and seed SQL.

## Refined Detection Logic (2025 Standard)

Based on recent (2025) cloaking intelligence:

### 1. Zero-Redirect Delivery (Plan 4.3)
Ad platforms easily detect 302/301 redirects during review. The standard for 2025 is **Server-Side Rendering (SSR)** or **Zero-Redirect Proxying**.
- Our `RemoteProxyAction` must maintain the original URL in the browser while serving remote content.
- Our `LocalFileAction` and `ShowHtmlAction` already support this zero-redirect pattern.

### 2. ASN Heuristic Matching (Plan 4.4)
Since GeoLite2-ASN doesn't have a "datacenter" flag, we will use a case-insensitive keyword match on the `autonomous_system_organization` field.
**Priority Keywords:**
`"amazon", "aws", "google cloud", "microsoft azure", "digitalocean", "linode", "vultr", "ovh", "hetzner", "contabo", "scaleway", "hosting", "datacenter", "data center", "cloud", "server", "colocation", "dedicated", "vps", "m247", "leaseweb", "cogent", "zenlayer", "tata communications"`

### 3. Referrer Analysis (Plan 4.4)
Bots often send no referrer or a fake referrer from the ad platform itself (e.g., `facebook.com`). We will implement:
- `ReferrerEmptyFilter`: Blocks empty referrers (high-risk bots).
### 4. GeoIP Data Assets (Verified)
The MaxMind GeoLite2 databases have been downloaded to `data/geoip/` and configured in `config.yaml`:
- `GeoLite2-Country.mmdb` (9.1M)
- `GeoLite2-City.mmdb` (62M)
- `GeoLite2-ASN.mmdb` (11M)

These are the primary data feeds for Phase 4 bot detection (Country/City filtering and ASN-based datacenter detection).
