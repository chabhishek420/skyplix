# SPEC.md — Project Specification

> **Status**: `FINALIZED`
> **Project**: ZAI TDS (Traffic Distribution System)
> **Codename**: zai-tds

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
|-------|-----------|-----------|
| **Language** | Go 1.22+ | Single binary, goroutine-per-click, 100K+ req/s |
| **HTTP** | net/http + chi router | Zero-dependency, battle-tested, ~50μs per route match |
| **Primary DB** | PostgreSQL 16 | Campaigns, streams, offers, users — relational integrity |
| **Cache** | Redis 7 | Session cache, click dedup, hot config, rate limiting |
| **Analytics DB** | ClickHouse | Click log storage, real-time aggregation, columnar compression |
| **GeoIP** | MaxMind GeoLite2 / IP2Location | Offline geo resolution, no external API calls on hot path |
| **Device Detection** | ua-parser (Go port) | User-agent parsing without external calls |
| **Admin UI** | React + shadcn/ui (separate SPA) | Reuse existing component knowledge, served as static files by Go binary |
| **Config** | YAML + env vars | Simple deployment, 12-factor app |
| **Migrations** | golang-migrate | Database schema management |

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

## Click Pipeline (Ported from Keitaro's 22-stage Pipeline)

```
1.  BuildRawClick          — Extract IP, UA, headers, params from request
2.  FindCampaign           — Match campaign_id to campaign config
3.  CheckDefaultCampaign   — Fall back to default if no campaign found
4.  CheckDomainRedirect    — Handle domain-level redirects
5.  ResolveGeo             — GeoIP lookup (country, region, city)
6.  ResolveDevice          — Parse UA (browser, OS, device type)
7.  ResolveISP             — ISP/ASN lookup for bot detection
8.  DetectBot              — Multi-signal bot detection
9.  ChooseStream           — Evaluate stream filters, pick matching stream
10. ChooseLanding          — Rotate landing page (weighted)
11. ChooseOffer            — Rotate offer (weighted)
12. FindAffiliateNetwork   — Resolve affiliate network config
13. GenerateClickID        — Cryptographically random click token
14. ReplaceMacros          — Substitute {click_id}, {campaign_id}, etc. in URLs
15. ExecuteAction          — Determine response type (redirect/show/proxy)
16. SetCookies             — Session tracking cookies
17. UpdateUniqueness       — Track unique visitors per campaign/stream/global
18. UpdateHitLimits        — Check and update daily/total click caps
19. CalculateCosts         — Apply cost model
20. QueueClickStorage      — Async write to ClickHouse via channel
21. QueueStatsUpdate       — Async increment daily stats counters
22. BuildResponse          — Construct HTTP 302/200/404 response
```

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
