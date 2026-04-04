---
phase: 2
plan: 3
wave: 2
---

# Plan 2.3: Pipeline Stages 7-12 — Stream Selection Core

## Objective
Replace the 6 NoOp stages (7-12) with real implementations, wiring in the cache, filter engine, and rotator from Plan 2.1 and the session service from Plan 2.2. After this plan, **a click will route through the full campaign→stream→landing→offer selection path**.

## Context
- .gsd/SPEC.md — 3-tier stream selection, weighted rotation
- .gsd/ARCHITECTURE.md — Pipeline stages 7-12, stream selection logic
- internal/pipeline/stage/noop.go — Current NoOp stubs being replaced
- internal/pipeline/pipeline.go — Payload struct
- internal/server/routes.go — Pipeline construction in handleClick (lines 58-97)
- internal/cache/cache.go — Valkey entity cache (from Plan 2.1)
- internal/filter/filter.go — Stream filter engine (from Plan 2.1)
- internal/rotator/rotator.go — Weighted rotator (from Plan 2.1)
- internal/session/session.go — Uniqueness service (from Plan 2.2)
- internal/cookie/cookie.go — Visitor code (from Plan 2.2)

## Tasks

<task type="auto">
  <name>Stages 7-8: Param Aliases + Campaign Uniqueness</name>
  <files>
    internal/pipeline/stage/7_check_param_aliases.go
    internal/pipeline/stage/8_update_campaign_uniqueness.go
  </files>
  <action>
    **Stage 7 — CheckParamAliases (`7_check_param_aliases.go`):**

    Resolves traffic source parameter aliases into standard sub_id fields.

    ```go
    type CheckParamAliasesStage struct {
        Cache  *cache.Cache
        Logger *zap.Logger
    }
    ```

    Logic:
    1. If `payload.Campaign.TrafficSourceID` is set, load the traffic source from cache
    2. Traffic source has a `Params` map (JSONB) mapping custom query param names to sub_id slots:
       e.g., `{"utm_campaign": "sub_id_1", "clickid": "sub_id_2"}`
    3. For each mapping, read the query param from `payload.Request.URL.Query()` and write to the corresponding `payload.RawClick.SubIDN` field
    4. If no traffic source or no mappings, this is a no-op (don't error)

    **Add `TrafficSourceID` field to `model.Campaign`** (it already exists in the PG schema but not the Go model).

    **Stage 8 — UpdateCampaignUniqueness (`8_update_campaign_uniqueness.go`):**

    ```go
    type UpdateCampaignUniquenessStage struct {
        Session *session.Service
        Cookie  *cookie.Cookie // or just pass in GetOrCreateVisitorCode result via Payload.VisitorCode
        Logger  *zap.Logger
    }
    ```

    Logic:
    1. Get visitor code from `payload.VisitorCode` (set earlier by a cookie-reading stage or inline)
    2. If visitor code is empty, read from cookie via `cookie.GetOrCreateVisitorCode(payload.Request)` and store in `payload.VisitorCode`
    3. Call `session.CheckCampaignUniqueness(ctx, visitorCode, campaignID)`
    4. Set `payload.RawClick.IsUniqueCampaign = result`

    **Important:** This stage must populate `payload.VisitorCode` if not already set. Later stages (10, 18, 19) depend on it.
  </action>
  <verify>go build ./internal/pipeline/stage/...</verify>
  <done>Stages 7-8 compile, CheckParamAliases resolves traffic source params, UpdateCampaignUniqueness calls session service</done>
</task>

<task type="auto">
  <name>Stages 9-12: Stream/Landing/Offer Selection</name>
  <files>
    internal/pipeline/stage/9_choose_stream.go
    internal/pipeline/stage/10_update_stream_uniqueness.go
    internal/pipeline/stage/11_choose_landing.go
    internal/pipeline/stage/12_choose_offer.go
  </files>
  <action>
    **Stage 9 — ChooseStream (`9_choose_stream.go`):**

    The most complex stage in the pipeline. Implements 3-tier stream selection:

    ```go
    type ChooseStreamStage struct {
        Cache   *cache.Cache
        Filter  *filter.Engine
        Rotator *rotator.Rotator  // or use rotator.Pick() directly
        Logger  *zap.Logger
    }
    ```

    **3-tier selection algorithm (from Keitaro source):**
    1. Load all streams for campaign from cache: `cache.GetStreamsByCampaign(campaignID)`
    2. Separate into three tiers: FORCED, REGULAR, DEFAULT
    3. **Tier 1 — FORCED streams** (sorted by position):
       - Iterate by position ascending
       - For each: evaluate ALL filters via `filter.MatchAll(click, stream.Filters)`
       - First match wins → select this stream, done
    4. **Tier 2 — REGULAR streams**:
       - If `campaign.Type == POSITION`:
         - Sort by position, first filter-matching stream wins
       - If `campaign.Type == WEIGHT`:
         - Filter all REGULAR streams, collecting those that match
         - Use `rotator.Pick()` on matching streams (by weight)
    5. **Tier 3 — DEFAULT stream**:
       - If no FORCED or REGULAR matched, use the DEFAULT stream
       - Campaign should have exactly one DEFAULT stream (or `campaign.DefaultStreamID`)
       - DEFAULT stream has NO filters — always matches
    6. If NO stream selected at all → set `payload.Abort = true` with 404

    Set `payload.Stream = selectedStream`
    Set `payload.RawClick.StreamID = selectedStream.ID`

    **Make Stream implement `rotator.Item`** by adding `GetWeight() int` and `GetID() uuid.UUID` methods.

    **Stage 10 — UpdateStreamUniqueness (`10_update_stream_uniqueness.go`):**
    - Call `session.CheckStreamUniqueness(ctx, visitorCode, streamID)`
    - Set `payload.RawClick.IsUniqueStream = result`

    **Stage 11 — ChooseLanding (`11_choose_landing.go`):**
    1. Load landings for stream: `cache.GetLandingsByStream(streamID)`
    2. If no landings → skip (some streams go directly to offer)
    3. If landings exist → `rotator.Pick()` by weight
    4. Set `payload.Landing = selectedLanding`
    5. Set `payload.RawClick.LandingID = selectedLanding.ID`

    **Stage 12 — ChooseOffer (`12_choose_offer.go`):**
    1. Load offers for stream: `cache.GetOffersByStream(streamID)`
    2. If no offers → error (a stream must have at least one offer or be an action-only stream like Status404)
    3. If offers exist → `rotator.Pick()` by weight
    4. Set `payload.Offer = selectedOffer`
    5. Set `payload.RawClick.OfferID = selectedOffer.ID`
    6. Set `payload.RawClick.Payout = selectedOffer.Payout`

    **Wire into routes.go:** Replace the 6 NoOp entries (lines 70-75) with real stage instantiations. Each stage gets its dependencies injected from server.go fields.

    **Update server.go:** Add `cache *cache.Cache`, `filterEngine *filter.Engine`, `sessionSvc *session.Service` fields. Initialize them in `New()`. Pass to handleClick's pipeline construction.
  </action>
  <verify>go build ./... && go vet ./...</verify>
  <done>Stages 9-12 compile, ChooseStream implements 3-tier selection with filter engine, ChooseLanding/ChooseOffer use weighted rotation, routes.go wired with real stages, server.go initializes cache+filter+session</done>
</task>

## Success Criteria
- [ ] `go build ./...` clean
- [ ] `go vet ./...` clean
- [ ] No more NoOp stages for 7-12 in routes.go
- [ ] ChooseStream implements FORCED → REGULAR → DEFAULT with filter evaluation
- [ ] ChooseStream handles both POSITION and WEIGHT campaign types
- [ ] ChooseLanding and ChooseOffer use weighted rotation
- [ ] Server.go initializes cache, filter engine, and session service
- [ ] Pipeline Payload has VisitorCode field populated by stage 8
