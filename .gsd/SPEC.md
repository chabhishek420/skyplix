# SPEC.md — Project Specification

> **Status**: `FINALIZED`
> **Project**: SkyPlix TDS (Traffic Distribution System)
> **Domain**: SKYPLIX.COM
> **Codename**: skyplix

## Vision

Build a production-grade, open-source Traffic Distribution System in Go that replaces Keitaro TDS for in-house affiliate marketing operations. Single binary deployment, sub-5ms click processing, full campaign management with cloaking, bot detection, stream routing, and real-time analytics — designed to handle 100K+ clicks/day and scale to millions.

## Goals

1. **Click Engine** — Process every click in <5ms: receive request → detect bot → match stream filters → rotate offer/landing → 302 redirect → log click asynchronously
2. **Campaign Management** — Full CRUD admin API for campaigns, streams, offers, landings, affiliate networks, traffic sources, domains — feature parity with Keitaro's 51 component modules
3. **Bot Detection & Cloaking** — Multi-layer detection (IP databases, device fingerprint, ISP blacklists, geo-filtering, JS challenges) with configurable safe page strategies
4. **Conversion Tracking** — Postback (S2S) and pixel-based conversion tracking with revenue/cost attribution
5. **Analytics & Reporting** — Real-time dashboards, campaign reports, drilldowns by geo/device/source/time — powered by ClickHouse for sub-second aggregation over billions of rows
6. **Open Source** — No licensing checks, no telemetry, no IonCube encryption, no vendor lock-in. MIT or Apache 2.0 licensed.

## Non-Goals (Out of Scope for v1)

- Multi-tenancy / SaaS mode (this is single-team, self-hosted)
- Built-in ad platform integrations (Facebook CAPI, Google Ads) — handle via postbacks
- Landing page builder / editor (serve external landings, not host them)
- Mobile SDK
- Billing / payment processing

## Users

- **Media buyers** — Create campaigns, set up streams with filters, monitor performance
- **Team leads** — View aggregate reports, manage offers and affiliate networks
- **DevOps** — Deploy single binary, configure via YAML/env, monitor health

## Technical Stack

| Layer | Technology | Rationale |
|-------|-----------|--------|
| **Language** | Go 1.23+ | Single binary, goroutine-per-click, 100K+ req/s |
| **HTTP** | Chi v5 (net/http) | Fully stdlib-compatible, idiomatic, no magic, same perf as Fiber on DB-bound routes |
| **Primary DB** | PostgreSQL 16 | Campaigns, streams, offers, users — JSONB for filter rules, row locking |
| **DB Driver** | pgx v5 + sqlc | Near-native perf, compile-time type safety, zero reflection — no ORM |
| **Cache / Queue** | Valkey 8 (Redis fork) | Open-source BSD license, async click write buffer + entity cache + uniqueness tracking |
| **Analytics DB** | ClickHouse 24 | Columnar click storage, sub-second aggregation over billions of rows |
| **CH Driver** | clickhouse-go v2 | High-level, production-ready, backed by ch-go internally |
| **GeoIP** | MaxMind GeoLite2 (country/city) + IP2Location LITE (ISP/ASN) | Both loaded into memory, sub-millisecond lookups |
| **Device Detection** | robicode/device-detector (evaluate vs mileusna/useragent in Phase 1) | Matomo-quality UA parsing, same accuracy as Keitaro's parser — see ADR-010 |
| **Admin UI** | Vite + React 19 + shadcn/ui | Compiles to static files, embedded in Go binary via go:embed |
| **Server State** | TanStack Query v5 | Polling-based live data, no WebSockets needed in V1 |
| **Auth** | Session tokens in Valkey | Revocable sessions, forced logout, team-safe |
| **Logging** | uber-go/zap | Structured JSON logging, ~10ns per call |
| **Metrics** | prometheus/client_golang | Standard Go instrumentation, /metrics endpoint |
| **Config** | YAML + env vars | Simple deployment, matches Keitaro's config.ini.php schema |
| **Migrations** | golang-migrate | SQL up/down files, embedded in binary, matches Keitaro's migration approach |

## Architecture

```
                    ┌─────────────────────────────────────────┐
                    │         ZAI TDS (Single Go Binary)      │
                    │                                         │
   Clicks ────────► │  /click   ── Pipeline (22 stages) ──►  │ ──► 302 Redirect
   Postbacks ─────► │  /postback                              │
   API calls ─────► │  /api/v1/admin/*                        │
   Admin UI ──────► │  /admin   (static React SPA)            │
                    │                                         │
                    └──────┬──────────┬──────────┬────────────┘
                           │          │          │
                     ┌─────┴──┐  ┌────┴───┐  ┌──┴────────┐
                     │ Redis  │  │ Postgres│  │ ClickHouse│
                     │ cache  │  │ state   │  │ analytics │
                     └────────┘  └────────┘  └───────────┘
```

## Click Pipeline (Source-Verified from Keitaro's Two-Level Pipeline)

> Verified directly from `Traffic/Pipeline/Pipeline.php`.
> There are TWO pipeline levels, not one. Previously stated as 22 stages — CORRECTED.

### Level 1 — Campaign Click (23 stages)
Triggered when a visitor hits `/CAMPAIGN_ALIAS`.
```
1.  DomainRedirect              — Handle domain-level campaign redirects
2.  CheckPrefetch               — Detect and handle browser prefetch requests
3.  BuildRawClick               — Extract IP, UA, referrer, sub_ids, costs from request
                                    **Also runs inline bot detection** (_checkIfBot + _checkIfProxy)
                                    Bot status feeds into IsBot stream filter in stage 9
4.  FindCampaign                — Lookup campaign from Valkey cache by alias/id
5.  CheckDefaultCampaign        — Fall back to default campaign if none found
6.  UpdateRawClick              — Enrich click with geo (GeoIP), device (UA parser)
7.  CheckParamAliases           — Resolve traffic source parameter aliases
8.  UpdateCampaignUniqueness    — Check/set unique visitor flag (campaign-level)
9.  ChooseStream                — Filter streams, pick matching by position or weight
10. UpdateStreamUniqueness      — Check/set unique visitor flag (stream-level)
11. ChooseLanding               — Rotate landing page (weighted round-robin)
12. ChooseOffer                 — Rotate offer (weighted round-robin)
13. GenerateToken               — Generate cryptographically random click token
14. FindAffiliateNetwork        — Resolve affiliate network postback config
15. UpdateHitLimit              — Check daily/total click caps (Valkey counters)
16. UpdateCosts                 — Apply cost model from traffic source params
17. UpdatePayout                — Calculate expected payout
18. SaveUniquenessSession       — Persist uniqueness flags to Valkey session
19. SetCookie                   — Write visitor_code + session cookies
20. ExecuteAction               — Build response (302, meta-redirect, proxy, HTML, 404)
21. PrepareRawClickToStore      — Serialize click data for async storage
22. CheckSendingToAnotherCampaign — Handle ToCampaign action recursion (limit 10)
23. StoreRawClicks              — Push to async channel → ClickHouse batch writer
```

### Level 2 — Landing Click (13 stages)
Triggered when visitor clicks through from landing page to offer.
The `visitor_code` cookie ties Level 1 and Level 2 clicks together.
```
1.  FindCampaign                — Re-resolve campaign from landing token
2.  UpdateParamsFromLanding     — Merge landing-level params into click
3.  CheckDefaultCampaign
4.  CheckParamAliases
5.  ChooseStream                — Re-evaluate stream (may differ from Level 1)
6.  ChooseOffer                 — Pick final offer URL
7.  FindAffiliateNetwork
8.  UpdateCosts
9.  UpdatePayout
10. SetCookie
11. ExecuteAction               — 302 redirect to affiliate offer URL
12. CheckSendingToAnotherCampaign
13. StoreRawClicks              — Store landing click to ClickHouse
```

### Action Types (19 response formats, from `Traffic/Actions/Predefined/`)
`HttpRedirect`, `Meta`, `DoubleMeta`, `BlankReferrer`, `Frame`, `Iframe`,
`Js`, `JsForIframe`, `JsForScript`, `FormSubmit`, `Curl`,
`Remote` (reverse proxy — fetches remote content, critical for cloaking safe pages),
`LocalFile`, `ShowHtml`, `ShowText`, `Status404`, `DoNothing`,
`SubId` (sub_id-based routing), `ToCampaign` (inter-campaign redirect)

## Constraints

- **Single binary** — `go build` produces one executable. No PHP, no Node, no Docker required for basic deployment
- **<5ms p99 click latency** — The pipeline must complete in under 5ms at the 99th percentile
- **Zero external API calls on hot path** — All geo/device/bot data resolved from local databases
- **Horizontal read scaling** — Multiple instances behind a load balancer, PostgreSQL as source of truth
- **Graceful degradation** — If ClickHouse is down, buffer clicks in Redis; if Redis is down, use in-memory cache

## Success Criteria

- [ ] Click endpoint processes 10,000 req/s on a single core with <5ms p99 latency
- [ ] Full Keitaro pipeline parity (22 stages)
- [ ] Admin API covers all 51 component modules (campaigns, streams, offers, etc.)
- [ ] Bot detection catches known bot IPs, datacenter ranges, and headless browsers
- [ ] Cloaking works: bots see safe page, real users see offers
- [ ] Postback conversion tracking with revenue attribution
- [ ] Real-time dashboard with click/conversion/revenue stats
- [ ] Single `./zai-tds` binary runs the entire system
- [ ] Deployed and processing live affiliate traffic

## Keitaro Parity Map

### Component Modules (51 total in Keitaro)

| Priority | Module | Keitaro Component | Status |
|----------|--------|--------------------|--------|
| P0 | Campaigns | `Component/Campaigns` | Required for v1 |
| P0 | Streams | `Component/Streams` | Required for v1 |
| P0 | Stream Filters | `Component/StreamFilters` | Required for v1 |
| P0 | Stream Actions | `Component/StreamActions` | Required for v1 |
| P0 | Offers | `Component/Offers` | Required for v1 |
| P0 | Landings | `Component/Landings` | Required for v1 |
| P0 | Clicks | `Component/Clicks` | Required for v1 |
| P0 | Bot Detection | `Component/BotDetection` | Required for v1 |
| P0 | Domains | `Component/Domains` | Required for v1 |
| P1 | Conversions | `Component/Conversions` | Required for v1 |
| P1 | Postback | `Component/Postback` | Required for v1 |
| P1 | Affiliate Networks | `Component/AffiliateNetworks` | Required for v1 |
| P1 | Traffic Sources | `Component/TrafficSources` | Required for v1 |
| P1 | Stats | `Component/Stats` | Required for v1 |
| P1 | Reports | `Component/Reports` | Required for v1 |
| P1 | Users | `Component/Users` | Required for v1 |
| P1 | Settings | `Component/Settings` | Required for v1 |
| P2 | Groups | `Component/Groups` | v1.1 |
| P2 | Templates | `Component/Templates` | v1.1 |
| P2 | Triggers | `Component/Triggers` | v1.1 |
| P2 | Stream Events | `Component/StreamEvents` | v1.1 |
| P2 | Macros | `Component/Macros` | v1.1 |
| P2 | Trends | `Component/Trends` | v1.1 |
| P2 | GeoProfiles | `Component/GeoProfiles` | v1.1 |
| P3 | Campaign Integration | `Component/CampaignIntegration` | v2 |
| P3 | ThirdPartyIntegration | `Component/ThirdPartyIntegration` | v2 |
| P3 | Simulation | `Component/Simulation` | v2 |
| P3 | Editor | `Component/Editor` | v2 |
| — | SelfUpdate | `Component/SelfUpdate` | Never (open source) |
| — | Branding | `Component/Branding` | Never (open source) |
| — | Av | `Component/Av` | Never (license check) |
