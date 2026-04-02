---
phase: 2
plan: 1
wave: 1
---

# Plan 2.1: Valkey Cache + Stream Filter Engine + Weighted Rotator

## Objective
Build the three foundational packages that every subsequent Phase 2 plan depends on:
1. **Valkey entity cache** — preload campaigns, streams (with filters), offers, and landings from PostgreSQL into Valkey so the hot path never touches PG.
2. **Stream filter engine** — evaluate 27 filter types against a click to determine which streams match.
3. **Weighted rotator** — generic weighted random selection used for streams, landings, and offers.

## Context
- .gsd/SPEC.md — Requirements (3-tier selection, 27 filters, weighted rotation)
- .gsd/ARCHITECTURE.md — Valkey key namespaces, filter categories, data flow
- internal/model/models.go — Existing domain models (Campaign, Stream, Offer, Landing)
- internal/server/server.go — Server struct (needs cache injected)
- internal/pipeline/stage/4_find_campaign.go — Currently queries PG directly (must switch to cache)
- db/postgres/migrations/002_create_streams.up.sql — Stream schema (filters JSONB, stream_landings, stream_offers)

## Tasks

<task type="auto">
  <name>Valkey Entity Cache</name>
  <files>
    internal/cache/cache.go
    internal/model/models.go (extend with StreamFilter, AffiliateNetwork, TrafficSource models)
  </files>
  <action>
    Create `internal/cache/cache.go` implementing the entity cache service.

    **Cache struct:**
    ```go
    type Cache struct {
        vk     *redis.Client
        db     *pgxpool.Pool
        logger *zap.Logger
    }
    ```

    **Methods to implement:**
    - `New(vk *redis.Client, db *pgxpool.Pool, logger *zap.Logger) *Cache`
    - `Warmup(ctx context.Context) error` — full reload from PG into Valkey
    - `GetCampaignByAlias(ctx context.Context, alias string) (*model.Campaign, error)` — Valkey first, PG fallback
    - `GetStreamsByCampaign(ctx context.Context, campaignID uuid.UUID) ([]model.Stream, error)`
    - `GetOffersByStream(ctx context.Context, streamID uuid.UUID) ([]model.WeightedOffer, error)`
    - `GetLandingsByStream(ctx context.Context, streamID uuid.UUID) ([]model.WeightedLanding, error)`
    - `GetAffiliateNetwork(ctx context.Context, id uuid.UUID) (*model.AffiliateNetwork, error)`
    - `InvalidateCampaign(ctx context.Context, campaignID uuid.UUID) error` — for admin save hooks

    **Valkey key patterns** (from ARCHITECTURE.md):
    - `campaign:{id}` → JSON-serialized Campaign (TTL 1h)
    - `campaign_alias:{alias}` → campaign UUID string
    - `streams:{campaign_id}` → JSON array of streams with parsed filters
    - `stream_offers:{stream_id}` → JSON array of {offer_id, weight}
    - `stream_landings:{stream_id}` → JSON array of {landing_id, weight}

    **Model extensions needed in models.go:**
    - Add `WeightedOffer` struct: `{Offer model.Offer, Weight int}`
    - Add `WeightedLanding` struct: `{Landing model.Landing, Weight int}`
    - Add `AffiliateNetwork` struct: `{ID uuid.UUID, Name string, PostbackURL string, State string}`
    - Add `TrafficSource` struct: `{ID uuid.UUID, Name string, PostbackURL string, Params map[string]string, State string}`
    - Extend `Stream.Filters` from `[]interface{}` to `[]StreamFilter`
    - Add `StreamFilter` struct: `{Type string, Payload map[string]interface{}}`

    **Serialization:** Use `encoding/json` for Valkey values. Keep it simple — no protobuf.

    **Important:** The `Warmup()` method must load ALL active campaigns, their streams (with filters deserialized from JSONB), and the stream↔offer/landing associations. This runs at startup and on admin entity save.

    Do NOT add any Valkey connection logic — the `*redis.Client` is passed in from server.go.
  </action>
  <verify>go build ./internal/cache/...</verify>
  <done>cache.go compiles, all methods exist with correct signatures, models.go has WeightedOffer/WeightedLanding/AffiliateNetwork/StreamFilter types</done>
</task>

<task type="auto">
  <name>Stream Filter Engine</name>
  <files>
    internal/filter/filter.go
    internal/filter/geo.go
    internal/filter/device.go
    internal/filter/network.go
    internal/filter/traffic.go
    internal/filter/tracking.go
    internal/filter/params.go
    internal/filter/schedule.go
    internal/filter/detection.go
  </files>
  <action>
    Create `internal/filter/` package implementing the 27 stream filter types.

    **Core interface in filter.go:**
    ```go
    // Filter evaluates whether a click matches a single filter condition.
    type Filter interface {
        Type() string
        Match(click *model.RawClick, payload map[string]interface{}) bool
    }

    // Engine evaluates all filters for a stream against a click.
    type Engine struct {
        filters map[string]Filter
    }

    func NewEngine() *Engine // registers all 27 filter types
    func (e *Engine) MatchAll(click *model.RawClick, filters []model.StreamFilter) bool
    ```

    **`MatchAll` logic:** ALL filters must match (AND logic). If a stream has zero filters, it matches everything (default stream behavior).

    **Filter payload convention:** Each filter's `payload` map has:
    - `"include"` → []string of values to match (whitelist)
    - `"exclude"` → []string of values to reject (blacklist)
    - Some filters have custom keys (e.g., Schedule has `"days"`, `"hours"`)

    **27 filter implementations by file:**

    `geo.go` — Country, Region, City:
    - Match against `click.CountryCode`, `click.Region`, `click.City`
    - Support include/exclude lists

    `device.go` — DeviceType, DeviceModel, Browser, BrowserVersion, Os, OsVersion:
    - Match against corresponding RawClick fields
    - BrowserVersion and OsVersion support semver-style comparison (>=, <=)

    `network.go` — Ip, Ipv6, Isp, Operator, ConnectionType, Proxy:
    - Ip/Ipv6: CIDR range matching via `net.Contains()`
    - Isp/Operator: string match against click.ISP
    - ConnectionType: match connection type string
    - Proxy: match `click.IsProxy` bool

    `traffic.go` — Referrer, EmptyReferrer, Language, UserAgent:
    - Referrer: substring/regex match against click.Referrer
    - EmptyReferrer: `click.Referrer == ""`
    - Language: match Accept-Language header
    - UserAgent: substring match against click.UserAgent

    `tracking.go` — Uniqueness, Limit, Interval:
    - Uniqueness: check `click.IsUniqueCampaign` or `click.IsUniqueStream`
    - Limit: stub — actual enforcement in UpdateHitLimit stage
    - Interval: time-of-day range check

    `params.go` — AnyParam, Parameter:
    - AnyParam: check if any sub_id parameter is non-empty
    - Parameter: match specific sub_id value (e.g., sub_id_1 == "google")

    `schedule.go` — Schedule:
    - Day-of-week + hour-of-day ranges
    - Payload: `{"days": [1,2,3,4,5], "hours": {"from": 8, "to": 22}}`

    `detection.go` — IsBot, HideClickDetect, ImkloDetect:
    - IsBot: match `click.IsBot` flag (set in Stage 3)
    - HideClickDetect: stub for Phase 4 JS fingerprint
    - ImkloDetect: stub for Phase 4 external detection

    **Add `Region` and `Language` fields to RawClick in models.go** — these are needed by filters but missing from Phase 1.

    **Important:** Keep filters pure and stateless. They receive a RawClick and return bool. No side effects, no Valkey calls.
  </action>
  <verify>go build ./internal/filter/...</verify>
  <done>All 27 filters registered in NewEngine(), filter.go + 8 category files compile, Engine.MatchAll works with StreamFilter model</done>
</task>

<task type="auto">
  <name>Weighted Rotator</name>
  <files>
    internal/rotator/rotator.go
  </files>
  <action>
    Create `internal/rotator/rotator.go` — a generic weighted random selector.

    **Interface:**
    ```go
    // Item is anything with a weight (stream, landing, offer).
    type Item interface {
        GetWeight() int
        GetID() uuid.UUID
    }

    // Pick selects one item from a weighted list using crypto/rand.
    // Returns nil if items is empty.
    func Pick[T Item](items []T) T

    // PickIndex returns the index of the selected item.
    func PickIndex(weights []int) int
    ```

    **Algorithm:** Weighted random selection:
    1. Sum all weights
    2. Generate random number in [0, totalWeight)
    3. Walk items, subtracting each weight until we find the bucket

    **Use `crypto/rand`** for cryptographic randomness (matching Keitaro's approach — no `math/rand` seeding issues).

    **Edge cases:**
    - Empty items → return zero value
    - All weights == 0 → equal probability (treat as weight 1 each)
    - Single item → return it immediately (no RNG needed)

    **Why generic:** The same rotation logic applies to streams (WEIGHT mode), landings, and offers. A generic function avoids code duplication.
  </action>
  <verify>go build ./internal/rotator/...</verify>
  <done>rotator.go compiles, Pick and PickIndex functions exist with crypto/rand weighted selection</done>
</task>

## Success Criteria
- [ ] `go build ./internal/cache/...` clean
- [ ] `go build ./internal/filter/...` clean
- [ ] `go build ./internal/rotator/...` clean
- [ ] `go build ./...` clean (full project)
- [ ] `go vet ./...` clean
- [ ] Cache has Warmup + GetCampaignByAlias + GetStreamsByCampaign + GetOffersByStream + GetLandingsByStream
- [ ] Filter engine has 27 registered types with MatchAll evaluator
- [ ] Rotator supports weighted random selection with crypto/rand
