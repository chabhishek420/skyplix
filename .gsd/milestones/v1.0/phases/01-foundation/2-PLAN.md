---
phase: 1
plan: 2
wave: 1
---

# Plan 1.2: Core Click Pipeline + HTTP Server + GeoIP + Device Detection + Bot Detection

## Objective
Implement the working click request handler: Chi HTTP server, the pipeline framework (ordered stage slice + Payload struct), and the first 6 pipeline stages with real GeoIP resolution and device/UA parsing. Wire basic inline bot detection inside `BuildRawClickStage` (IP list check + UA pattern match + empty UA check) so that the `is_bot` flag is populated on every click. The server must start cleanly, connect to all three databases, and return a 302 redirect for a hardcoded test campaign.

## Context
- .gsd/SPEC.md — Pipeline stages (lines 82-108)
- .gsd/ARCHITECTURE.md — Pipeline design, stage list, hot path data flow (lines 165-310)
- .gsd/STACK.md — Chi v5, geoip2-golang, robicode/device-detector, uber/zap (lines 22-85)
- .gsd/DECISIONS.md — ADR-003 (Chi), ADR-007 (two-level pipeline), ADR-008 (bot detection inline)

## Tasks

<task type="auto">
  <name>HTTP server + Chi router + Zap logger + config-driven startup</name>
  <files>
    internal/server/server.go
    internal/server/routes.go
    internal/valkey/client.go
    cmd/zai-tds/main.go
    go.mod (updated with new deps)
  </files>
  <action>
    1. Add dependencies:
       ```
       go get github.com/go-chi/chi/v5
       go get go.uber.org/zap
       go get github.com/jackc/pgx/v5
       go get github.com/redis/go-redis/v9
       go get github.com/golang-migrate/migrate/v4
       ```

    2. `internal/server/server.go`:
       - `Server` struct holds: `*chi.Mux`, `*pgxpool.Pool`, `*redis.Client`, `*zap.Logger`, `*config.Config`
       - `New(cfg *config.Config, logger *zap.Logger) (*Server, error)`:
         - Connect pgxpool: `pgxpool.New(ctx, cfg.Postgres.DSN)` — ping required before returning
         - Connect Valkey: `redis.NewClient(&redis.Options{Addr: cfg.Valkey.Addr})` — ping required
         - Return error if either connection fails
       - `Run(ctx context.Context) error`: http.ListenAndServe, honors ctx cancellation
       - Server must handle OS signals (SIGINT, SIGTERM) for graceful shutdown with 30s timeout

    3. `internal/server/routes.go`:
       - Mount routes on chi.Mux
       - Traffic routes: `GET /{alias}` → clickHandler (Level 1 pipeline)
       - Admin routes: `GET /api/v1/health` → JSON `{"status":"ok","version":"0.1.0"}`
       - Middleware: zap request logger, recovery middleware (chi/middleware.Recoverer)

    4. `internal/valkey/client.go`:
       - Thin wrapper: `New(addr string) (*redis.Client, error)` — returns connected client
       - Helper: `Ping(ctx, client)` returns error

    5. Update `cmd/zai-tds/main.go`:
       - Load config → init zap logger → call server.New() → server.Run()
       - Logger: production JSON logger if !debug, dev logger if debug
       - Fail fast with os.Exit(1) on any startup error, log reason

    DO NOT use global redis/pg clients — pass via Server struct.
    DO NOT add ClickHouse connection yet — belongs in Plan 1.3 (async writer).
    DO NOT implement the click handler body yet — return 501 stub for now.
    Run golang-migrate at startup BEFORE server listens: apply UP migrations from db/postgres/migrations/.
  </action>
  <verify>
    go build -o /tmp/zai-tds ./cmd/zai-tds && \
    docker compose up -d && sleep 2 && \
    DATABASE_URL="postgres://zai:zai_dev_pass@localhost:5432/zai_tds" \
    VALKEY_URL="localhost:6379" \
    /tmp/zai-tds &
    sleep 1 && \
    curl -s http://localhost:8080/api/v1/health | grep '"status":"ok"' && echo "HEALTH OK" && \
    kill %1 2>/dev/null; echo "SERVER OK"
  </verify>
  <done>
    - `go build ./...` exits 0 with no errors
    - `/api/v1/health` returns `{"status":"ok","version":"0.1.0"}`
    - Server logs startup message in JSON: `{"msg":"ZAI TDS listening","addr":":8080"}`
    - Server connects to PostgreSQL and Valkey on startup (check logs)
    - Migrations applied automatically at startup (check postgres `\dt`)
    - SIGINT shuts down gracefully within 30 seconds
  </done>
</task>

<task type="auto">
  <name>Pipeline framework + Payload + Stages 1-6 + GeoIP + Device detection + Bot detection</name>
  <files>
    internal/pipeline/pipeline.go
    internal/pipeline/payload.go
    internal/pipeline/stage/1_domain_redirect.go
    internal/pipeline/stage/2_check_prefetch.go
    internal/pipeline/stage/3_build_raw_click.go
    internal/pipeline/stage/4_find_campaign.go
    internal/pipeline/stage/5_check_default_campaign.go
    internal/pipeline/stage/6_update_raw_click.go
    internal/model/click.go
    internal/model/campaign.go
    internal/geo/geo.go
    internal/device/detector.go
    internal/server/routes.go (update click handler)
    go.mod (add geoip2, device-detector deps)
  </files>
  <action>
    **ARCHITECTURE CRITICAL — read ARCHITECTURE.md lines 165-198 before writing any code.**

    1. `internal/pipeline/payload.go` — Payload struct threaded through all stages:
       ```go
       type Payload struct {
         // Request context
         Ctx        context.Context
         Request    *http.Request
         Writer     http.ResponseWriter

         // Click data (populated progressively)
         RawClick   *model.RawClick

         // Resolved entities (from Valkey cache)
         Campaign   *model.Campaign
         Stream     *model.Stream
         Offer      *model.Offer
         Landing    *model.Landing

         // Response (set by ExecuteAction stage)
         Response   *Response

         // Pipeline control
         Abort      bool   // true = stop pipeline, send response as-is
         AbortCode  int    // HTTP status code if aborting
       }
       ```

    2. `internal/pipeline/pipeline.go`:
       - `Stage` interface: `Process(payload *Payload) error`
       - `Pipeline` struct: ordered `[]Stage`
       - `Run(payload *Payload) error`: iterate stages, stop if payload.Abort == true
       - On error: log + set Abort=true + AbortCode=500, return error

    3. `internal/model/click.go` — RawClick struct (~40 fields for Phase 1, expand later):
       IP, UserAgent, Referrer, IsBot bool, CountryCode, City, ISP,
       DeviceType, DeviceModel, Browser, BrowserVersion, OS, OSVersion,
       SubID1..SubID5, Cost, ClickToken, CampaignID, StreamID, OfferID, LandingID,
       IsUniqueGlobal, IsUniqueCampaign, IsUniqueStream, CreatedAt

    4. `internal/geo/geo.go`:
       - Load GeoLite2-Country.mmdb and GeoLite2-City.mmdb into memory at startup
         using `github.com/oschwald/geoip2-golang`
       - `Lookup(ip net.IP) GeoResult` — returns CountryCode, City, ASN (sub-ms)
       - If mmdb path is empty/missing → log warning, return empty GeoResult (graceful degradation)

    5. `internal/device/detector.go`:
       - Evaluate `github.com/mileusna/useragent` first (pure Go, no CGo)
         as it avoids CGo binary complexity for Phase 1
       - `Parse(ua string) DeviceResult` — returns DeviceType, Browser, BrowserVersion, OS, OSVersion
       - DeviceModel/Brand: set to empty string in Phase 1 (acceptable — Phase 2 can upgrade)
       - Log warning once if UA string is empty

    6. Stage implementations (mirroring ARCHITECTURE.md stage list):

       **Stage 1 — DomainRedirectStage** (1_domain_redirect.go):
       - Extract alias from URL path `/{alias}`
       - If path is "/" (bare domain): set payload.RawClick.Alias = "" (handled by stage 4)
       - Store alias in payload for stage 4

       **Stage 2 — CheckPrefetchStage** (2_check_prefetch.go):
       - Check `Purpose: prefetch` header or `X-Moz: prefetch`
       - If prefetch detected: payload.Abort = true, payload.AbortCode = 200, return nil
       - (Prefetch requests must silently succeed but NOT count as clicks)

       **Stage 3 — BuildRawClickStage** (3_build_raw_click.go):
       - Extract real IP (check X-Forwarded-For, X-Real-IP, then RemoteAddr)
       - Populate RawClick.UserAgent, Referrer, SubID1-5 from query params
       - **INLINE BOT DETECTION** (per ADR-008 — this is the core of the stage):
         a. Empty UA check: if ua == "" → IsBot = true
         b. UA pattern match: check against list of 50+ known bot UAs
            (Googlebot, Bingbot, facebookexternalhit, AhrefsBot, SemrushBot, etc.)
         c. IP blocklist check: maintain in-memory slice of known bot IP ranges
            (Use a small hardcoded starter list — Phase 4 will replace with full DB)
         Patterns source: reference/YellowCloaker/ for initial bot UA list

       **Stage 4 — FindCampaignStage** (4_find_campaign.go):
       - For Phase 1: look up campaign from PostgreSQL directly (Valkey cache comes in Phase 2)
       - If no campaign found: payload.Abort = true, AbortCode = 404
       - Set payload.Campaign

       **Stage 5 — CheckDefaultCampaignStage** (5_check_default_campaign.go):
       - If payload.Campaign is nil AND campaign has default_stream_id:
         fall back to default campaign
       - For Phase 1: if no campaign at all → abort 404

       **Stage 6 — UpdateRawClickStage** (6_update_raw_click.go):
       - Call geo.Lookup(rawClick.IP) → populate CountryCode, City
       - Call device.Parse(rawClick.UserAgent) → populate DeviceType, Browser, OS, etc.
       - Set rawClick.CreatedAt = time.Now().UTC()

    7. Wire the pipeline in click handler (routes.go):
       - Create Level1Pipeline with stages 1-6 (stages 7-23 are stubs for now)
       - After stage 6: return 302 to a hardcoded URL for testing ("https://example.com")
       - Log the populated RawClick as JSON for verification

    ADD deps: `go get github.com/oschwald/geoip2-golang github.com/mileusna/useragent`

    DO NOT use global pipeline instance — create per-request (stages are stateless).
    DO NOT implement Valkey cache lookup in stage 4 yet — use direct Postgres query.
    The bot UA pattern list should be a package-level var []string, not a file read at runtime.
  </action>
  <verify>
    # Start server
    DATABASE_URL="postgres://zai:zai_dev_pass@localhost:5432/zai_tds" \
    VALKEY_URL="localhost:6379" \
    /tmp/zai-tds &
    sleep 1

    # Insert test campaign
    docker compose exec -T postgres psql -U zai -d zai_tds -c \
      "INSERT INTO campaigns (alias, name, type) VALUES ('test', 'Test Campaign', 'POSITION');"

    # Test click request
    curl -v -L http://localhost:8080/test 2>&1 | grep "302\|Location\|example.com" && echo "PIPELINE OK"

    # Test bot detection
    curl -s -H "User-Agent: Googlebot/2.1" http://localhost:8080/test | head && echo "BOT DETECTED (check logs)"

    kill %1
  </verify>
  <done>
    - `GET /test` returns HTTP 302 redirect
    - Server logs show populated RawClick JSON (IP, country, device, is_bot fields)
    - Googlebot UA → is_bot=true in logs
    - Empty UA → is_bot=true in logs
    - Normal browser UA → is_bot=false
    - GeoIP: logs show country_code populated for real IPs (or empty for 127.0.0.1 — acceptable)
  </done>
</task>

## Success Criteria
- [ ] Chi HTTP server starts, connects to PostgreSQL + Valkey, runs migrations
- [ ] `GET /api/v1/health` returns `{"status":"ok","version":"0.1.0"}`
- [ ] `GET /{alias}` runs 6-stage pipeline and returns 302
- [ ] RawClick struct is populated (IP, UA, country, device, is_bot) — visible in structured logs
- [ ] Bot detection: Googlebot and empty UA → is_bot=true
- [ ] Prefetch requests → silently 200, not logged as clicks
- [ ] Server handles SIGINT with graceful shutdown
