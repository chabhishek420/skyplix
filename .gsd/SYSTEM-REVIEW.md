# SkyPlix TDS: Complete System Review

**Date:** 2026-04-03  
**Version:** 0.1.0  
**Phases Complete:** 1 (Foundation) + 2 (Pipeline) + 3 (Admin API) + 4.9.4 (Cloaking/Bot Detection)

---

## Executive Summary

SkyPlix TDS is a high-performance Go-based affiliate traffic distribution system modeled on Keitaro. The system is substantially built out through four completed phases with 23-stage click pipelines, comprehensive bot detection, click routing, admin APIs, and async ClickHouse analytics ingestion. The next critical milestone is **Phase 5: Conversion Tracking & Attribution**.

### Current State by Phase

| Phase | Status | Key Deliverables |
|-------|--------|------------------|
| **1: Foundation** | ✓ Complete | Click pipeline, Valkey caching, session management, landing page tokens |
| **2: Pipeline & Routing** | ✓ Complete | L1/L2 pipelines (23 stages), stream rotation, offer selection, action execution |
| **3: Admin API** | ✓ Complete | CRUD for campaigns, streams, offers, landings, networks, users, domains, traffic sources |
| **4.9.4: Cloaking & Bot Detection** | ✓ Complete | IP-based filtering, User-Agent patterns, datacenter detection, rate limiting, safe-page flows |
| **5: Conversion Tracking & Attribution** | ➜ Next | Postback ingestion, attribution linking, conversion reporting (in progress) |

---

## Core Architecture

### Technology Stack

**Backend:** Go 1.25  
**API Router:** `chi/v5` (RESTful routing, middleware)  
**Datastores:**
- PostgreSQL 16 (transactional entities: campaigns, streams, offers, users, domains)
- ClickHouse 24 (analytics: clicks, conversions, aggregations)
- Valkey/Redis (hot-path caching: sessions, bot IPs, stream selections, LP tokens)

**Core Libraries:**
- `pgx/v5` — PostgreSQL driver
- `clickhouse-go/v2` — ClickHouse driver
- `go-redis/v9` — Valkey client
- `zap` — Structured logging
- `google/uuid` — UUID generation
- `testify` — Test assertions

### Deployment Architecture

```
┌─────────────────────────────────────────────────────────┐
│ Incoming Click Request (HTTP GET / POST)                │
├─────────────────────────────────────────────────────────┤
│ API Router (chi/v5) with middleware                      │
│  - Request logging (zap)                                │
│  - Real IP extraction (X-Forwarded-For)                 │
│  - Panic recovery                                       │
├─────────────────────────────────────────────────────────┤
│ L1 Pipeline (23 stages) OR L2 Pipeline (8 stages)       │
│  → Data enrichment (IP geo, device detection)           │
│  → Bot/rate-limit filtering                             │
│  → Campaign/stream selection with rotation              │
│  → Landing page/offer selection                         │
│  → Click tokenization & session binding                 │
│  → Action execution (redirect, pixel, safe page)        │
├─────────────────────────────────────────────────────────┤
│ Async Processing                                         │
│  → Click record → ClickHouse queue → bulk insert        │
│  → Session cache → Valkey                               │
│  → Visitor uniqueness tracking                          │
├─────────────────────────────────────────────────────────┤
│ Admin API (Protected by API Key Auth)                   │
│  → Entity CRUD (campaigns, streams, offers, landings)   │
│  → Bot IP/UA management                                 │
│  → Settings management                                  │
│  → Reporting (Phase 5)                                  │
│  → Postback ingestion (Phase 5)                         │
└─────────────────────────────────────────────────────────┘
```

---

## HTTP API Surface

### Public Endpoints

```
GET    /api/v1/health                    Health check
GET    /postback/{key}                   Conversion postback (affiliate network)
POST   /postback/{key}                   Conversion postback (affiliate network)
GET    /{alias}                          Level 1 click (campaign → stream → redirect)
GET    /lp/{token}/click                 Level 2 click (landing page → offer)
GET    /                                 Gateway click (bare domain)
```

### Protected Admin API (API Key Auth)

**Campaigns:**
```
GET    /api/v1/campaigns                 List campaigns
POST   /api/v1/campaigns                 Create campaign
GET    /api/v1/campaigns/{id}            Get campaign
PUT    /api/v1/campaigns/{id}            Update campaign
DELETE /api/v1/campaigns/{id}            Delete campaign
POST   /api/v1/campaigns/{id}/clone      Clone campaign
GET    /api/v1/campaigns/{id}/streams    List campaign's streams
```

**Streams:**
```
POST   /api/v1/streams                   Create stream
GET    /api/v1/streams/{id}              Get stream
PUT    /api/v1/streams/{id}              Update stream
DELETE /api/v1/streams/{id}              Delete stream
POST   /api/v1/streams/{id}/clone        Clone stream
GET    /api/v1/streams/{id}/offers       Get stream's offers
POST   /api/v1/streams/{id}/offers       Sync stream offers
GET    /api/v1/streams/{id}/landings     Get stream's landings
POST   /api/v1/streams/{id}/landings     Sync stream landings
```

**Offers, Landings, Affiliate Networks, Traffic Sources, Domains, Users, Bot IPs/UAs, Settings:**
```
GET    /api/v1/{resource}                List resource
POST   /api/v1/{resource}                Create resource
GET    /api/v1/{resource}/{id}           Get resource
PUT    /api/v1/{resource}/{id}           Update resource
DELETE /api/v1/{resource}/{id}           Delete resource
```

**Bot Management:**
```
GET    /api/v1/bots/ips                  List bot IPs
POST   /api/v1/bots/ips                  Add bot IP ranges
PUT    /api/v1/bots/ips                  Replace bot IPs
DELETE /api/v1/bots/ips                  Exclude bot IPs
DELETE /api/v1/bots/ips/all              Clear all bot IPs
POST   /api/v1/bots/ips/check            Check if IP is bot
GET    /api/v1/bots/ua                   List bot User-Agents
POST   /api/v1/bots/ua                   Add bot UA patterns
DELETE /api/v1/bots/ua                   Delete bot UA pattern
```

**Settings & Postback:**
```
GET    /api/v1/settings                  Get system settings
PUT    /api/v1/settings                  Update system settings
GET    /api/v1/reports                   Get reports/analytics
GET    /api/v1/affiliate_networks/{id}/postback_url  Generate postback URL
```

---

## Data Model

### PostgreSQL Schema (Transactional)

**campaigns** — Define traffic distribution rules
- id, alias, name, type (POSITION|WEIGHT), bind_visitors, state, traffic_source_id, default_stream_id, timestamps

**streams** — Traffic rotators within campaigns
- id, campaign_id, name, state, weight, type, bind_visitors, target_country, custom_rules, limits (daily_cap, monthly_cap), api_keys, timestamps

**offers** — Conversion goals/destination URLs
- id, name, url, payout, description, state, timestamps

**landings** — Intermediate landing pages (pre-offer)
- id, stream_id, name, url, weight, state, timestamps

**affiliate_networks** — External network integrations for postback
- id, name, postback_url, token, settings, state, timestamps

**traffic_sources** — Traffic source definitions
- id, name, cost, state, timestamps

**domains** — Landing domain management
- id, name, type (L1|L2), state, timestamps

**users** — Admin users
- id, username, email, password_hash, api_key, permissions, timestamps

**settings** — Key/value system settings
- key, value (JSON), created_at, updated_at

### ClickHouse Schema (Analytics)

**clicks** — Immutable click event log
- click_id (UUID), created_at, campaign_id, campaign_alias, stream_id, offer_id, landing_id
- ip (IPv6), country_code, city, isp, device_type, device_model, os, browser, user_agent, referrer
- is_bot, is_unique_global, is_unique_campaign, is_unique_stream
- sub_id_1 through sub_id_5 (parameter binding)
- cost, payout, action_type, click_token
- Partitioned by month, ordered by (campaign_id, created_at)

**conversions** — Conversion events linked to clicks
- conversion_id (UUID), created_at, click_token, campaign_id, affiliate_network_id
- status (lead|sale|reject), payout, revenue
- Ordered by (created_at, click_token)

**stats_materialized_views** — Pre-aggregated metrics (Phase 5 add)
- Hourly/daily rollups per campaign for fast reporting

---

## Pipeline Architecture: The Click Flow

### L1 Pipeline (23 Stages) — Full Click Processing

Entry: `GET /{alias}` or `GET /`

```
1. Domain Redirect          → Normalize domain, apply redirect rules
2. Check Prefetch           → Skip if prefetch request (Lighthouse, etc.)
3. Build Raw Click          → Extract IP, UA, device, geo, bot flags
4. Find Campaign            → Look up campaign by alias
5. Check Default Campaign   → Fall back if campaign missing
6. Update Raw Click         → Enrich with geo (city, ISP), device detection
7. Check Param Aliases      → Resolve URL parameter aliases (sub_id mapping)
8. Update Global Uniqueness → Check session for global unique (repeat visitor)
9. Update Campaign Uniqueness → Check session for campaign-level repeat
10. Choose Stream           → Select stream by weight/position/filter rules
11. Update Stream Uniqueness → Check stream-level repeat visitor
12. Choose Landing          → Select landing page (if any)
13. Choose Offer            → Select offer destination
14. Generate Token          → Create click_token for L2 tracking
15. Save LP Token           → Store LP token in Valkey (session bridge)
16. Find Affiliate Network  → Look up postback endpoint
17. Update Hit Limit        → Apply daily/monthly caps
18. Update Costs            → Sum affiliate cost to campaign
19. Update Payout           → Sum affiliate payout
20. Save Uniqueness Session → Persist visitor session to Valkey
21. Set Cookie              → Send tracking cookie to visitor
22. Execute Action          → Send HTTP response (redirect, pixel, safe page)
23. Store Raw Clicks        → Queue click to ClickHouse for async insert

Exit: HTTP response to visitor (redirect URL or safe page)
```

**Key Design Points:**
- All stages operate on shared `Payload` (Golang pattern for pipeline composition)
- Early abort stops execution unless stage implements `AlwaysRun()` (stages 22-23 always fire to persist data)
- Re-dispatch mechanism supports recursive campaign entry (ToCampaign action, up to 10 hops)
- All data reads hit Valkey cache first; PostgreSQL only for cache misses

### L2 Pipeline (8 Stages) — Landing Page → Offer

Entry: `GET /lp/{token}/click`

```
1. Build Raw Click          → Same enrichment as L1
2. L2 Find Campaign         → Resolve campaign from LP token
3. Choose Offer             → Select offer for this LP session
4. Find Affiliate Network   → Get postback details
5. Update Costs             → Accumulate cost
6. Update Payout            → Accumulate payout
7. Store Raw Clicks         → Queue to ClickHouse
8. (Execute Action)         → Redirect to offer
```

**Optimization:**
- Skips stages 4-11 from L1 (stream selection, landing selection already done)
- LP token lookup in Valkey is O(1); avoids re-running stream logic

---

## Core Services

### Click Routing & Selection

**`rotator.Rotator`**  
Selects destinations (streams, landings, offers) using weight-based or positional rotation. Maintains internal counters to enforce round-robin or random selection rules.

**`filter.Engine`**  
Applies traffic filtering rules per stream (country targeting, device targeting, browser targeting, ISP targeting, custom rules). Returns allow/block/flag decision.

**`binding.Service`**  
Binds visitor parameters (sub_ids, macro values) to URLs. Substitutes macros like `{click_id}`, `{offer_id}`, `{visitor_country}` in redirect URLs.

### Session & Identity

**`session.Service`** (Valkey-backed)  
Tracks visitor sessions across requests. Records global, campaign-level, and stream-level "uniqueness" (whether visitor is a repeat). Used for hit deduplication and cost control.

**`lptoken.Service`** (Valkey-backed)  
Creates and retrieves LP tokens. Bridges L1 and L2 pipelines by storing stream/offer selection in a token, then L2 retrieves the token to know what to do.

### Bot Detection & Abuse Prevention

**`botdb.ValkeyStore`** (IP-based)  
Maintains IP ranges (CIDR) of known bot datacenter IPs. Checks incoming IP against the set; flags is_bot=1 if match.

**`botdb.UAStore`** (User-Agent-based)  
Stores regex patterns for bot User-Agents (Googlebot, Bingbot, etc.). Checks incoming UA string against patterns; flags is_bot=1 if match.

**`ratelimit.Service`** (Valkey-backed, sliding window)  
Enforces per-IP rate limiting. Blocks IPs exceeding threshold clicks per time window. Protects against click floods.

**`hitlimit.Service`** (Valkey-backed)  
Enforces daily/monthly traffic caps per stream. Blocks stream selection if cap exceeded. Critical for cost control.

### Analytics & Attribution

**`queue.Writer`** (ClickHouse writer)  
Async queue for bulk-inserting clicks and conversions into ClickHouse. Batches records into bulk inserts for performance. Exposes `ClickChan()` and `ConvChan()` for pipeline stages to enqueue events.

**`attribution.Service`** (Valkey-backed)  
Stores click-token → click_id mapping for postback attribution. When affiliate network sends postback with click_token, this service looks up the original click to link conversion.

**`analytics.Service`** (ClickHouse reader)  
Queries ClickHouse for aggregated metrics (clicks/conversions per campaign, per stream, per offer). Supports dashboards and reporting APIs.

### Configuration & Environment

**`config.Config`**  
Loads YAML configuration. Supports environment variable overrides. Configures database DSNs, ClickHouse addresses, Valkey endpoints, system settings (rate limits, caps, timeouts).

**`cache.Cache`** (Valkey-backed)  
Implements a global cache for campaigns, streams, offers, landings, networks, traffic sources, domains. Warmup on startup; cache-aside on miss (query PostgreSQL, cache result).

---

## Implementation Quality

### Testing

**7 test files** covering:
- `test/unit/queue/` — Queue writer tests (ClickHouse ingestion)
- `test/unit/worker/` — Worker tests (cache warmup, session janitor)
- `test/integration/` — Full system tests
  - `admin_test.go` — CRUD operations on all entities
  - `click_test.go` — L1/L2 click pipeline execution
  - `cloaking_test.go` — Bot detection, safe-page flows, filtering
  - `routing_test.go` — Stream/offer selection, uniqueness tracking, cost accumulation

**Run tests:**
```bash
go test ./test/unit/...                                    # Unit tests only
go test -v -tags integration ./test/integration/...        # Integration tests (requires docker-compose)
go test -cover ./...                                        # Coverage report
```

### Code Organization

**Internal packages follow clear ownership:**
- `action/` — Action handlers (redirect, pixel fire, safe page)
- `admin/` — Admin API handlers + repositories (CRUD logic)
- `pipeline/` + `pipeline/stage/` — Click processing pipeline (23 L1 + 8 L2 stages)
- `queue/` — Async ClickHouse writer
- `worker/` — Background workers (cache warmup, session cleanup)
- `botdb/`, `filter/`, `ratelimit/`, `hitlimit/` — Security/abuse prevention
- `model/` — Data structures
- `cache/`, `session/`, `binding/`, `lptoken/`, `attribution/` — Support services

**Code Style:**
- Structured logging with zap (no printf logging)
- Early return on errors; errors wrapped with context
- Small interfaces (3-5 methods); dependency injection via constructors
- No interface{} unless necessary; no type assertions without checks
- All hot-path functions receive context.Context as first parameter

### Error Handling

All packages use sentinel errors (`var ErrXXX = errors.New(...)`) for recoverable errors. Pipeline stages return errors to halt processing. Database operations wrap errors with operation context.

---

## Completed Features (Phase 1-4.9.4)

### ✓ Click Ingestion & Routing (Phases 1-2)
- [x] L1 and L2 click pipelines (23 + 8 stages)
- [x] Campaign/stream/offer/landing selection with rotation
- [x] Parameter binding and macro substitution
- [x] Session tracking (global, campaign, stream uniqueness)
- [x] Landing page tokens for L2 tracking
- [x] Visitor action execution (redirect, pixel, safe page)
- [x] Async ClickHouse click ingestion

### ✓ Admin API (Phase 3)
- [x] CRUD for campaigns, streams, offers, landings
- [x] CRUD for affiliate networks, traffic sources, domains, users
- [x] Settings management (system-wide key/value)
- [x] Bot IP and User-Agent management
- [x] API key authentication
- [x] Campaign cloning

### ✓ Cloaking & Bot Detection (Phase 4.9.4)
- [x] IP-based bot detection (datacenter IPs via CIDR)
- [x] User-Agent pattern matching (Googlebot, Bingbot, etc.)
- [x] Safe-page flows (return neutral HTML instead of redirect for bots)
- [x] Per-IP rate limiting (sliding window)
- [x] Daily/monthly traffic caps per stream
- [x] GeoIP detection (country, city, ISP)
- [x] Device detection (mobile, desktop, tablet)
- [x] Browser/OS detection

---

## Phase 5: Conversion Tracking & Attribution (In Progress)

### What's Started

**Postback Handler** (`internal/admin/handler/postback.go`)
- Accepts GET/POST to `/postback/{key}` endpoint
- Validates postback signature against affiliate network settings
- Links incoming postback (with click_token) to original click for attribution

**Attribution Service** (`internal/attribution/`)
- Stores click_token → click_id mapping in Valkey
- Queried by postback handler to find original click record
- Used to update conversion status in ClickHouse

**Conversion Table** (ClickHouse)
- `conversions` table created with click_token, conversion_id, status, payout, revenue
- Linked to clicks table via click_token for SQL joins

**Reports Handler** (`internal/admin/handler/`) — Stubbed
- Conditional routing in `routes.go` for `/api/v1/reports`
- Needs implementation: query ClickHouse for aggregations

### What's Missing (for Phase 5 Completion)

1. **Postback Signature Validation** — Verify postback authenticity using affiliate network's signing key
2. **Conversion Status Updates** — Update ClickHouse conversions table when postback arrives
3. **Attribution Queries** — Multi-table joins (clicks ↔ conversions) for revenue reports
4. **Reporting API** — Implement `/api/v1/reports` to expose metrics (ROI, cost/conversion, payout/conversion)
5. **Real-time Stats** — Materialized views in ClickHouse for fast hourly/daily aggregations
6. **Dashboard Backend** — Support multiple report filters (by campaign, stream, offer, date range)

---

## Known Issues & Constraints

### Performance Constraints
- Click latency target: <5ms p99 (hot-path critical)
- Cache warmup on startup to avoid cold-start penalties
- ClickHouse bulk inserts for efficient analytics ingestion
- All reads hit Valkey before PostgreSQL

### Architectural Constraints
- Single Go monolith (no microservices split)
- Pipeline execution is synchronous; async only for ClickHouse writes
- Worker background tasks (cache warmup, session cleanup) run at fixed intervals
- No message queue between click pipeline and ClickHouse (direct channel-based queueing)

### Test Gaps
- No load/stress tests for <5ms p99 latency verification
- No integration tests for cross-component error scenarios (e.g., PostgreSQL down, ClickHouse down)
- Limited edge-case coverage (e.g., malformed postbacks, duplicate conversions)

---

## Dependencies & Infrastructure

### Local Development Stack

```yaml
docker-compose.yml provides:
  - PostgreSQL 16 (campaigns, streams, users, domains, settings)
  - ClickHouse 24 (clicks, conversions, aggregations)
  - Valkey (hot-path caching)
```

### Go Dependencies

See `go.mod` for full list. Key runtime deps:
- `chi/v5` — HTTP router
- `pgx/v5` — PostgreSQL driver
- `clickhouse-go/v2` — ClickHouse driver
- `go-redis/v9` — Valkey/Redis client
- `zap` — Structured logging
- `google/uuid` — UUID generation

### Build Requirements

- Go 1.25+
- Docker + docker-compose (for local integration tests)
- `ripgrep` (auto-installed by GSD)

---

## What to Build Next

### Phase 5 Completion Tasks

1. **Postback Signature Validation**
   - Load network signing key from settings
   - Validate HMAC-SHA256 signature on incoming postback
   - Reject unsigned/invalid postbacks with 401

2. **Conversion Recording**
   - Parse postback parameters (click_token, status, payout, revenue)
   - Look up original click via attribution service
   - Insert conversion record into ClickHouse conversions table

3. **Reporting API**
   - Implement `/api/v1/reports?campaign_id=X&from=2026-04-01&to=2026-04-03`
   - Query ClickHouse: clicks grouped by campaign/stream/offer, joined with conversions
   - Return JSON: clicks, conversions, cost, payout, revenue, ROI

4. **Materialized Views** (Optional, for speed)
   - Pre-aggregate clicks/conversions hourly in ClickHouse
   - Use views instead of raw tables for reporting queries

### Phase 6: Admin Dashboard UI

- React frontend consuming admin API
- Pages: Campaigns, Streams, Offers, Landings, Networks, Analytics/Reports
- Real-time stats refresh
- Campaign performance charts (clicks, conversions, ROI)

### Phase 7: Production Hardening

- Load/stress testing (verify <5ms p99 latency at scale)
- Error recovery and failover testing
- Database backup/restore procedures
- Observability improvements (Prometheus metrics, structured logs)
- Deployment and scaling documentation

---

## How to Continue

This project uses **GSD v2** for milestone planning and task execution. To resume work:

```bash
# Start interactive GSD session (from terminal)
gsd

# Or continue from here
gsd auto --message "Resume Phase 5 implementation"

# Or run quick task
gsd quick "Add postback signature validation"
```

GSD maintains:
- `.gsd/PROJECT.md` — Current project state
- `.gsd/STATE.md` — Status and next steps
- `.gsd/ROADMAP.md` — Milestone breakdown
- `.gsd/milestones/M001/` — Phase 5 execution artifacts

---

## Quick Reference Commands

```bash
# Build
go build -o zai-tds cmd/zai-tds/main.go

# Run (requires postgres, valkey, clickhouse running)
go run cmd/zai-tds/main.go

# Unit tests
go test ./test/unit/...

# Integration tests
docker-compose up -d
go test -v -tags integration ./test/integration/... -timeout 30s
docker-compose down

# Lint & format
go fmt ./...
go vet ./...

# Check what's been built
git log --oneline | head -20
```

---

**Status:** System is substantially complete. Next milestone is Phase 5 conversion tracking. Plan is documented in `.gsd/milestones/M001/` awaiting execution.
