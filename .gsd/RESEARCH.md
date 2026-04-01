# RESEARCH.md — Full Stack Technology Decisions
> **Date**: 2026-04-01
> **Method**: Web research + deep Keitaro source code verification
> **Status**: FINALIZED

---

## How This Research Was Done

Every decision below is backed by **two sources**:
1. External web research (benchmarks, community consensus, 2025/2026 data)
2. Keitaro PHP source code verification (`reference/Keitaro_source_php/`) to
   understand what we are actually porting and what the real constraints are.

---

## LAYER 1 — HTTP Engine (The Hot Path)

### Decision: Go 1.23+ + Fiber

**Source verification:**
Keitaro uses **RoadRunner** (a Go binary from Spiral) as its HTTP server.
PHP workers connect to RoadRunner over stdio. Keitaro is already partially Go.
`Traffic/RoadRunner/Server.php` shows the while-loop worker pattern:
```php
while ($psr7 = $this->_psrClient->acceptRequest()) {
    $response = Kernel::run($request, $context);
    $this->_psrClient->respond($response);
}
```
Our Go rewrite is the **completion** of this direction — removing PHP entirely.

**Research findings:**
- Fiber (fasthttp) leads raw benchmarks. Consistently 20-30% higher RPS than
  Gin/Echo on synthetic tests. The gap narrows in DB-bound workloads.
- Real-world bottleneck is NOT the framework — it's geo resolution + DB writes.
- Fiber's fasthttp incompatibility with net/http ecosystem is a real trade-off.

**Final decision: Go 1.23+ with Chi router**
- **Why Chi over Fiber:** Chi is fully `net/http` compatible. This means every
  Go middleware, library, and tool works without adaptation. Fiber's 20% speed
  advantage disappears the moment you add a geo lookup (1-5ms) or Redis read
  (0.5ms). The synthetic benchmark advantage is irrelevant on DB-bound routes.
- **Why not Gin:** Chi is more idiomatic and has zero magic. Gin's extra
  features (binding, rendering) are unnecessary — we write raw JSON.
- **Concurrency model:** One goroutine per request (Go's default). No worker
  pool needed at this scale. Go's scheduler handles 100K+ concurrent goroutines.
- **Object pooling:** `sync.Pool` for `ClickContext` structs to eliminate GC
  pressure on the hot path. Directly mirrors Keitaro's RoadRunner worker reuse.

---

## LAYER 2 — Hot Path Data Storage (Redis)

### Decision: Valkey 8 (Redis-compatible, truly open-source)

**Source verification:**
Keitaro uses Redis for **five distinct purposes** — found across the codebase:
1. `CommandQueue/QueueStorage/RedisStorage.php` — **Async write buffer**
   (click commands queued with RPUSH, popped in batches of 1000 via LRANGE+LTRIM pipeline)
2. `CachedData/Storage/RedisStorage.php` — **Campaign/stream config cache**
   (all hot-path entity data pre-loaded into Redis on save, never read from MySQL during clicks)
3. `HitLimit/Storage/RedisStorage.php` — **Daily click cap counters**
4. `Session/Storage/RedisStorage.php` — **Uniqueness session tracking**
5. `LpToken/Storage/RedisStorage.php` — **Landing page click tokens**

This is the most important architectural insight: **Redis is Keitaro's primary
performance secret, not MySQL.** MySQL is never touched on the click hot path.
Every hot-path read goes to Redis (pre-warmed cache). Every hot-path write goes
to Redis (async command queue), then flushed to MySQL by a background cron.

**Research findings:**
- Redis changed license to dual-source-available/AGPLv3 in 2024.
- Valkey is the Linux Foundation fork (BSD 3-Clause) — true open-source.
- Drop-in compatible: same protocol, same client libraries (`go-redis` works).
- Valkey 8 outperforms Redis 7 in high-concurrency throughput benchmarks.
- Dragonfly is faster on multi-core but less mature and has module gaps.

**Final decision: Valkey 8**
- Open-source license (BSD 3-Clause) — aligns with our MIT/Apache project goal
- Drop-in replacement, go-redis client works unchanged
- Superior p99 latency over Redis 7 in our use case (high-concurrency RPUSH/LRANGE)
- Data structures needed: Lists (click queue), Strings (counters), Hashes
  (entity cache), Sets (uniqueness tracking)

**Critical implementation note:** Campaign configs, stream data, offers, and
landings MUST be pre-written to Valkey on every admin save operation.
The click pipeline reads ONLY from Valkey. Postgres is never queried during
click processing. This architecture must be enforced in Phase 1.

---

## LAYER 3 — Transactional Database (Admin/Config)

### Decision: PostgreSQL 16 + pgx v5 + sqlc

**Source verification:**
Keitaro uses MySQL/MariaDB via ADODB. `Core/Db/Db.php` shows raw SQL strings:
```php
$this->_db->connect("mysql:host=" . $server, $user, $password, $name);
```
Table prefix: `keitaro_`. No ORM — raw SQL with a thin `Db::quote()` wrapper.
This confirms: **no ORM abstraction on any DB path**.

Keitaro's config schema (from `config/config.ini.php`):
- `[db]`: host, user, password, name, prefix, port, optional slave
- `[redis]`: uri (host:port/dbid)
- `[system]`: debug, salt, log_max_size, postback_key, max_auth_tries

**Research findings:**
- `sqlc` + `pgx`: near-native performance, compile-time type safety, zero reflection
- GORM: heavy reflection overhead, N+1 query risks, fails under load
- pgx v5: native PostgreSQL driver, supports `pgxpool` for connection pooling,
  handles COPY protocol for bulk inserts, JSONB, arrays
- `squirrel` query builder: for dynamic admin list/filter queries where sqlc is
  too rigid (sqlc for 95% of queries, squirrel for complex filter builders)

**Final decision: PostgreSQL 16 + pgxpool + sqlc + squirrel (dynamic only)**
- PostgreSQL over MySQL: better JSONB support (stream filter rules as JSON),
  row-level locking, EXPLAIN ANALYZE, partitioning for large click tables
- pgxpool: connection pool sized to CPU cores × 4
- sqlc: generates all typed query functions from `.sql` files
- squirrel: only for admin list endpoints with dynamic WHERE clauses
- golang-migrate: SQL-based migrations, simple up/down files, mature and stable

**Schema approach:** Mirror Keitaro's table structure where possible to enable
data migration from existing Keitaro installations (competitive advantage).

---

## LAYER 4 — Analytics Database (Click Storage)

### Decision: ClickHouse 24 (clickhouse-go v2 driver)

**Source verification:**
Keitaro v9 does NOT use ClickHouse. All click storage goes to MySQL via the
`AddClickCommand` delayed write pattern. This is a known scalability ceiling
for Keitaro — one of the core reasons to build ZAI TDS.

The source shows click storage is the LAST stage of the pipeline:
```php
// StoreRawClicksStage.php
AddClickCommand::saveClick($rawClick);  // queued via Redis, flushed by cron
```

Our architecture: clicks go to **ClickHouse** via the same async pattern
(buffered Go channels → batch insert). Postgres for no-click data.

**Research findings:**
- ClickHouse: purpose-built columnar OLAP. Sub-second aggregation over billions
  of rows. 10-100x compression vs row-oriented DBs. Industry standard for ad-tech.
- TimescaleDB: good for PostgreSQL shops but not columnar — struggles above
  100M rows for real-time aggregation
- DuckDB: analytics-only, not for streaming ingestion (wrong tool here)
- `clickhouse-go` v2 driver: uses `ch-go` internally, high-level API,
  `database/sql` compatible. Correct for our use case.
- `ch-go` direct: only use if profiling shows `clickhouse-go` is a bottleneck
  (unlikely — our bottleneck will be network/query complexity, not driver overhead)

**Final decision: ClickHouse 24 + clickhouse-go v2**

**Click schema (mirrored from Keitaro's RawClick serialization):**
```sql
CREATE TABLE clicks (
    datetime     DateTime,
    campaign_id  UInt32,
    stream_id    UInt32,
    offer_id     UInt32,
    landing_id   UInt32,
    visitor_code String,
    ip           IPv4,
    country      LowCardinality(String),
    region       String,
    city         String,
    browser      LowCardinality(String),
    os           LowCardinality(String),
    device_type  LowCardinality(String),
    is_bot       UInt8,
    is_mobile    UInt8,
    is_unique    UInt8,
    cost         Float64,
    revenue      Float64,
    -- sub_id_1..10, extra_param_1..5
) ENGINE = MergeTree()
PARTITION BY toYYYYMM(datetime)
ORDER BY (campaign_id, datetime);
```

**Async write pattern (Go equivalent of Keitaro's Redis command queue):**
```go
// Hot path: non-blocking channel push
select {
case clickCh <- click:
default:
    // channel full — log metric, drop or use fallback
}
// Background worker: batch flush every 500ms or 5000 clicks
```

---

## LAYER 5 — GeoIP Resolution

### Decision: MaxMind GeoLite2 (primary) + IP2Location LITE (ISP/proxy)

**Source verification:**
`BuildRawClickStage.php` calls geo resolution inline during click processing.
The source shows ISP resolution is separate from geo (`is_geo_resolved`,
`is_isp_resolved`, `is_device_resolved` flags on RawClick).
Two separate blocking steps: geo lookup + ISP lookup.

**Research findings:**
- All offline `.mmdb` lookups are sub-millisecond (memory-mapped file).
  Network latency is irrelevant — this is purely local disk/memory speed.
- MaxMind GeoLite2: free, `.mmdb` format, best ecosystem support, updated weekly.
  Weakness: city-level accuracy is lower than paid tiers.
- IP2Location LITE: free BIN format, better ISP/ASN data in free tier than MaxMind.
- For bot/proxy detection: IP2Location PROXY database (paid, but worth it for
  affiliate fraud prevention)

**Final decision:**
- `oschwald/geoip2-golang` for MaxMind `.mmdb` reads (country/city/ASN)
- IP2Location LITE BIN for ISP/ASN supplementation
- Both databases loaded into memory on startup
- Weekly auto-update via cron job (separate from app)

---

## LAYER 6 — Device Detection

### Decision: mssola/device-detector (Go port)

**Source verification:**
`BuildRawClickStage.php` calls device parsing for Browser, OS, DeviceType,
DeviceModel, DeviceBrand. Keitaro uses a PHP user-agent parser.

**Final decision:** `mssola/device-detector` (Go port of Matomo's device-detector).
- Same UA pattern database as Matomo (well-maintained, production-proven).
- Alternative: `mileusna/useragent` (simpler, faster, less accurate).
- Decision: use `mssola/device-detector` for Keitaro parity on device fields.
- Cache parsed UA strings in Valkey (UA string → DeviceInfo, TTL 1 hour).
  Keitaro shows the same UA repeated thousands of times from bot farms.

---

## LAYER 7 — Pipeline Architecture

### Decision: Explicit Stage Slice (not middleware chain)

**Source verification:**
`Pipeline.php` `firstLevelStages()` defines an ORDERED SLICE of stage objects.
Each stage implements `StageInterface::process(Payload, LogEntry) Payload`.
The pipeline stops early if `payload.isAborted()`.
There are **TWO pipeline levels**:

**Level 1 (23 stages):** Campaign click → stream selection → landing redirect
```
DomainRedirect → CheckPrefetch → BuildRawClick → FindCampaign →
CheckDefaultCampaign → UpdateRawClick → CheckParamAliases →
UpdateCampaignUniqueness → ChooseStream → UpdateStreamUniqueness →
ChooseLanding → ChooseOffer → GenerateToken → FindAffiliateNetwork →
UpdateHitLimit → UpdateCosts → UpdatePayout → SaveUniquenessSession →
SetCookie → ExecuteAction → PrepareRawClickToStore →
CheckSendingToAnotherCampaign → StoreRawClicks
```

**Level 2 (13 stages):** Landing click → offer redirect (visitor_code cookie ties them)
```
FindCampaign → UpdateParamsFromLanding → CheckDefaultCampaign →
CheckParamAliases → ChooseStream → ChooseOffer → FindAffiliateNetwork →
UpdateCosts → UpdatePayout → SetCookie → ExecuteAction →
CheckSendingToAnotherCampaign → StoreRawClicks
```

**Action types (from `Traffic/Actions/Predefined/`):**
The pipeline can produce 15 response types:
`HttpRedirect`, `Meta`, `DoubleMeta`, `BlankReferrer`, `Frame`, `Iframe`,
`Js`, `JsForIframe`, `JsForScript`, `FormSubmit`, `Curl` (proxy fetch),
`LocalFile`, `ShowHtml`, `ShowText`, `Status404`, `DoNothing`, `ToCampaign`

**Go implementation:**
```go
type Stage interface {
    Process(ctx context.Context, p *Payload) (*Payload, error)
}
type Pipeline struct {
    stages []Stage
}
func (p *Pipeline) Run(ctx context.Context, payload *Payload) (*Payload, error) {
    for _, stage := range p.stages {
        var err error
        payload, err = stage.Process(ctx, payload)
        if err != nil { return payload, err }
        if payload.Aborted { return payload, nil } // early exit
    }
    return payload, nil
}
```

---

## LAYER 8 — Admin Frontend

### Decision: Vite + React 19 + TypeScript + shadcn/ui + TanStack Query

**Research findings:**
- Vite vs Next.js static export for internal SPA: both work with shadcn/ui.
- Vite: faster HMR, simpler config, zero framework overhead, compiles to
  pure static HTML/JS/CSS — embed directly in Go binary via `//go:embed`.
- Next.js static export: same output but 2x more config, file-system routing
  (unnecessary complexity for <30 routes), larger bundle baseline.
- shadcn/ui works identically in both.
- TanStack Query v5: best-in-class server state management. Handles polling,
  caching, background refresh — no WebSockets needed.

**Final decision: Vite + React 19 + TypeScript + shadcn/ui + TanStack Query**

**Why not Next.js:** For a private internal admin panel embedded in a Go binary,
Next.js server features (SSR, Server Components, API routes) are all disabled
when using static export. You pay the framework overhead for zero benefit.

**Build pipeline:**
```
cd admin && npm run build → dist/
go:embed dist/* → served by Go at /admin/*
```
Single binary. Zero Node.js process in production.

**State management:** Zustand (lightweight, no boilerplate) for local UI state.
TanStack Query for all server data. No Redux.

**Charts:** Recharts (Tremor is heavier and more opinionated). Recharts gives
full control over chart appearance for a custom TDS dashboard.

---

## LAYER 9 — Authentication

### Decision: Session tokens in Valkey + HTTP-only cookies

**Source verification:**
Keitaro config shows: `salt`, `max_auth_tries = 5`.
Keitaro uses PHP sessions with a custom auth system. No JWT in the source.

**Why NOT stateless JWT:**
Stateless JWTs cannot be revoked. For a team tool with multiple media buyers:
- You need to force-logout a departing team member instantly.
- You need to see active sessions in the admin.
- You need a "log out all devices" button after a security incident.

**Final decision:**
- Login → generate `session_id` (32-byte cryptographically random hex)
- Store in Valkey: `session:{id}` → `{user_id, role, created_at, last_seen}`, TTL 24h
- Set `session_id` in HTTP-only, Secure, SameSite=Strict cookie
- Each API request: read cookie → Valkey lookup → authorize
- Logout: `DEL session:{id}` in Valkey
- Admin can list/revoke sessions per user

---

## LAYER 10 — Database Migrations

### Decision: golang-migrate

**Research findings:**
- golang-migrate: most mature, widest DB support, SQL up/down files,
  both CLI and Go library, tracks state in `schema_migrations` table.
- goose: good but less ecosystem support, Go-based migrations are rarely needed.
- Atlas: powerful but adds declarative complexity we don't need for V1.

**Final decision: golang-migrate with SQL files**
- Matches Keitaro's own migration approach (they have `/migrations/` and
  `/migrations2/` directories with numbered SQL files)
- Migrations embedded in binary via `//go:embed migrations/*.sql`
- Applied on startup if `--auto-migrate` flag is set

---

## LAYER 11 — Observability

### Decision: Prometheus + Grafana (self-hosted, optional)

**Research findings:**
- Prometheus: standard Go instrumentation via `prometheus/client_golang`.
- VictoriaMetrics: better at scale but adds operational complexity.
- For a team TDS, Prometheus is sufficient for months.
- The `promhttp` handler adds <0.1ms overhead per scrape interval.

**Final decision: Prometheus client + structured logging with zap**
- `uber-go/zap`: structured JSON logging at ~10ns per log call (no reflection)
- Prometheus client: expose `/metrics` endpoint
- Grafana: optional Docker Compose service for teams who want dashboards
- Key metrics to track:
  - `click_pipeline_duration_ms` (histogram, p50/p95/p99)
  - `click_valkey_cache_hit_ratio`
  - `click_queue_depth` (async write buffer backpressure)
  - `bot_detection_rate` (by campaign)
  - `pipeline_stage_errors_total` (by stage name)

---

## LAYER 12 — Deployment

### Decision: Docker Compose (primary) + single binary (advanced)

**Config schema (derived from Keitaro's config.ini.php):**
```yaml
# config.yaml
server:
  host: "0.0.0.0"
  port: 8080
  click_port: 8081      # separate port for click traffic

postgres:
  dsn: "postgres://zai:pass@localhost:5432/zai_tds"

valkey:
  addr: "localhost:6379"
  db: 1

clickhouse:
  addr: "localhost:9000"
  database: "zai_tds"

geoip:
  country_db: "/data/GeoLite2-Country.mmdb"
  city_db: "/data/GeoLite2-City.mmdb"
  asn_db: "/data/GeoLite2-ASN.mmdb"

system:
  debug: false
  salt: "generate-on-install"
  max_auth_tries: 5
  log_level: "info"
```

**Docker Compose services:**
```
zai-tds (Go binary)
postgres:16-alpine
valkey/valkey:8-alpine
clickhouse/clickhouse-server:24
nginx:alpine (TLS termination)
grafana/grafana (optional)
```

**Single binary mode:** All static assets (admin UI) embedded via `//go:embed`.
Only external dependencies are the three databases.

---

## FINAL STACK SUMMARY

| Layer | Technology | Version | Reason |
|-------|-----------|---------|--------|
| Language | Go | 1.23+ | Single binary, goroutines, ad-tech standard |
| HTTP Router | Chi | v5 | net/http compatible, idiomatic, no magic |
| Click Queue | Valkey | 8 | Open-source Redis fork, async write buffer |
| Config Cache | Valkey | 8 | Entity pre-loading (campaigns/streams/offers) |
| Transactional DB | PostgreSQL | 16 | ACID, JSONB, row locking |
| DB Driver | pgx | v5 | Native PostgreSQL, pgxpool, zero overhead |
| SQL Layer | sqlc | latest | Compile-time type-safe SQL, zero ORM |
| Dynamic Queries | squirrel | v2 | Admin list filters only |
| Migrations | golang-migrate | latest | SQL files, embedded in binary |
| Analytics DB | ClickHouse | 24 | Columnar, billions of rows, sub-second agg |
| CH Driver | clickhouse-go | v2 | High-level, production-ready |
| GeoIP | MaxMind GeoLite2 | current | Free, mmdb format, memory-mapped |
| ISP/Proxy | IP2Location LITE | current | Better ASN/ISP than GeoLite2 free |
| UA Parser | mssola/device-detector | latest | Matomo-quality, Go port |
| Admin UI | Vite + React 19 | latest | Static SPA, embedded in Go binary |
| UI Components | shadcn/ui | latest | Accessible, composable, customizable |
| Server State | TanStack Query | v5 | Caching, polling, background refresh |
| UI State | Zustand | v5 | Minimal, no boilerplate |
| Charts | Recharts | v2 | Flexible, customizable |
| Auth | Session tokens in Valkey | — | Revocable, team-safe |
| Logging | uber-go/zap | v1 | Structured JSON, 10ns/call |
| Metrics | Prometheus client | v2 | Standard Go instrumentation |
| Deployment | Docker Compose | v2 | Single `docker compose up` |
| License | MIT | — | No restrictions, no telemetry |
