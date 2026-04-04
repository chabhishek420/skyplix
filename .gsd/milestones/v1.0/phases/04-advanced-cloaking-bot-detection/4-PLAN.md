---
phase: 4
plan: 4
wave: 2
---

# Plan 4.4: Datacenter/VPN Detection + ISP Blacklisting + P1 Filters

## Objective
Implement infrastructure-level bot detection: MaxMind ASN database for datacenter/hosting classification, ISP name blacklisting, referrer analysis filters, and URL token blocking. These are the P0 (datacenter/VPN) and P1 (ISP, referrer, URL tokens) requirements from the ROADMAP.

## Context
- `internal/geo/geo.go` — Existing MaxMind GeoIP resolver (needs ASN database support)
- `internal/pipeline/stage/3_build_raw_click.go` — Bot detection entry point
- `internal/model/models.go` — RawClick has ISP field (already defined, not populated)
- `internal/filter/detection.go` — HideClickDetect/ImkloDetect stubs
- `internal/filter/network.go` — Existing network filter types
- `reference/YellowCloaker/core.php` — VPN/referrer/URL token detection patterns

## Tasks

<task type="auto">
  <name>MaxMind ASN integration for datacenter/hosting detection</name>
  <files>internal/geo/geo.go, internal/pipeline/stage/3_build_raw_click.go, internal/pipeline/stage/6_update_raw_click.go, internal/model/models.go</files>
  <action>
    1. **Enhance `internal/geo/geo.go`:**
       - Add `asnDB *geoip2.Reader` field to `Resolver`
       - Modify `New()` to accept optional ASN database path (3rd param)
       - Add `LookupASN(ip net.IP) (asn uint, org string, err error)` method
       - Add `IsDatacenter(ip net.IP) bool` method — Lookup ASN org name, check against known datacenter/hosting keywords:
         ```
         "amazon", "aws", "google cloud", "microsoft azure", "digitalocean",
         "linode", "vultr", "ovh", "hetzner", "contabo", "scaleway",
         "hosting", "datacenter", "data center", "cloud", "server",
         "colocation", "dedicated", "vps"
         ```
       - This is a heuristic match (case-insensitive substring on org name)

    2. **Enhance `internal/model/models.go`:**
       - Add `ASN uint` and `ASNOrg string` fields to `RawClick`
       - Add `IsDatacenter bool` field to `RawClick`

    3. **Enhance `internal/pipeline/stage/6_update_raw_click.go`:**
       - After existing GeoIP enrichment, call `geo.LookupASN(ip)` to populate `rc.ASN`, `rc.ASNOrg`, `rc.ISP`
       - Call `geo.IsDatacenter(ip)` to set `rc.IsDatacenter`

    4. **Enhance `internal/pipeline/stage/3_build_raw_click.go`:**
       - Add `GeoResolver` interface field: `Geo interface{ IsDatacenter(net.IP) bool }` (optional, nil-safe)
       - Add check #5 in `detectBot()`: if `Geo != nil && Geo.IsDatacenter(ip)` return true
       - This runs AFTER UA checks but BEFORE pipeline enrichment (stage 3 vs stage 6)
       - **Note:** Stage 3 gets a lightweight datacenter check; stage 6 does the full ASN enrichment. This is intentional — we want bot classification as early as possible.

    **Config change:** Add `geo.asn_db` to `config.yaml` (path to GeoLite2-ASN.mmdb).
  </action>
  <verify>go build ./internal/geo/... && go build ./internal/pipeline/...</verify>
  <done>ASN lookup works, datacenter IPs flagged as bots in stage 3, full ASN data populated in stage 6</done>
</task>

<task type="auto">
  <name>P1 filters: ISP blacklist, referrer analysis, URL token blocking</name>
  <files>internal/filter/network.go, internal/filter/traffic.go, internal/filter/filter.go</files>
  <action>
    1. **Add ISP blacklist filter** to `internal/filter/network.go`:
       - `IspBlacklistFilter struct{}`
       - `Type() = "IspBlacklist"`
       - `Match()`: payload contains `{"isps": ["hosting company X", ...]}`. Check if `rc.ISP` or `rc.ASNOrg` contains any entry (case-insensitive substring match, like YellowCloaker's ISP check).

    2. **Add referrer filters** to `internal/filter/traffic.go`:
       - `ReferrerEmptyFilter struct{}` — `Type() = "ReferrerEmpty"`. Matches when `rc.Referrer == ""`. Payload: `{"is_empty": true/false}`.
       - `ReferrerStopwordFilter struct{}` — `Type() = "ReferrerStopword"`. Payload: `{"stopwords": ["facebook", "google", ...]}`. Returns true if referrer contains none of the stopwords (i.e., block if referrer contains a stopword).

    3. **Add URL token filter** to `internal/filter/traffic.go`:
       - `UrlTokenFilter struct{}` — `Type() = "UrlToken"`. Payload: `{"blocked_params": ["fbclid", "debug", "test", "scan", "preview"]}`. Check if request URL query params contain any of the blocked tokens. Returns false (block) if found.
       - Needs access to query params — use `rc.SubID1` through `rc.SubID5` or add a `QueryParams map[string]string` field to RawClick.

    4. **Register all new filters** in `internal/filter/filter.go` `NewEngine()`.

    **Design note:** These filters work at the STREAM level (not pipeline stage 3). They let operators configure per-stream rules like "block traffic from hosting ISPs" or "block clicks with debug params". This is different from the global bot detection in stage 3.
  </action>
  <verify>go build ./internal/filter/...</verify>
  <done>4 new filter types registered, ISP/referrer/URL token matching implemented</done>
</task>

## Success Criteria
- [ ] MaxMind ASN database loads alongside country/city databases
- [ ] `RawClick` gains ASN, ASNOrg, IsDatacenter fields
- [ ] Datacenter IPs flagged as bots in stage 3
- [ ] ISP blacklist filter matches on ASN org name
- [ ] Empty referrer and stopword referrer filters work
- [ ] URL token blocking filter catches debug/scanner params
- [ ] `go build ./...` compiles cleanly
