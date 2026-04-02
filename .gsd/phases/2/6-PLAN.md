---
phase: 2
plan: 6
wave: 3
---

# Plan 2.6: Integration Testing + Phase 2 Verification

## Objective
Prove the entire Phase 2 Campaign Engine works end-to-end with empirical evidence:
1. **Seed test data** — campaigns with streams, filters, offers, landings in PostgreSQL
2. **Integration test** — verify click routing through filters → stream selection → offer rotation → redirect
3. **Level 2 test** — verify landing → offer click linking via LP tokens
4. **Verification report** — document all Phase 2 spec requirements as proven or not

## Context
- .gsd/SPEC.md — Phase 2 requirements (27 filters, 3-tier selection, binding, Level 2, etc.)
- test/integration/click_test.go — Existing Phase 1 E2E test (extend for Phase 2)
- All internal/ packages built in Plans 2.1-2.5

## Tasks

<task type="auto">
  <name>Integration Test Suite</name>
  <files>
    test/integration/routing_test.go
    test/integration/testdata/seed.sql
  </files>
  <action>
    **Test data seed (`test/integration/testdata/seed.sql`):**

    Create SQL that sets up a complete routing scenario:

    1. **Campaign "testcamp"** (type=POSITION, bind_visitors=false):
       - Stream 1 (FORCED, position=1): filter=[IsBot=true], action=Status404
         → No offers (bot gets 404)
       - Stream 2 (REGULAR, position=1): filter=[Country IN (US,GB)], action=HttpRedirect
         → Offer A (weight=70, url="https://offer-a.example.com/{click_id}")
         → Offer B (weight=30, url="https://offer-b.example.com/{click_id}")
         → Landing X (weight=100, url="https://landing.example.com/?token={click_id}")
       - Stream 3 (DEFAULT): no filters, action=HttpRedirect
         → Offer C (weight=100, url="https://default-offer.example.com")

    2. **Campaign "weightcamp"** (type=WEIGHT, bind_visitors=true):
       - Stream 1 (REGULAR, weight=80): filter=[DeviceType IN (desktop)]
         → Offer D (weight=100)
       - Stream 2 (REGULAR, weight=20): filter=[DeviceType IN (mobile)]
         → Offer E (weight=100)
       - Stream 3 (DEFAULT):
         → Offer F (weight=100)

    3. **Domain mapping:** domain "test.local" → campaign "testcamp"

    4. **Affiliate network "TestNetwork"** with postback URL

    **Integration tests (`test/integration/routing_test.go`):**

    ```go
    //go:build integration
    ```

    **Test cases:**

    1. `TestBotGetsBlocked`:
       - Send click with bot UA (e.g., "Googlebot/2.1") to `/testcamp`
       - Assert: response is 404 (FORCED stream matched IsBot filter)
       - Assert: ClickHouse row has is_bot=1, stream_id=stream1

    2. `TestGeoFilterRouting`:
       - Send click with non-bot UA + mock GeoIP returning "US"
       - Assert: 302 redirect to offer-a.example.com or offer-b.example.com
       - Assert: URL contains click token (macro replacement worked)
       - Assert: stream_id=stream2 in ClickHouse

    3. `TestDefaultStreamFallback`:
       - Send click from country "JP" (not in US/GB filter)
       - Assert: 302 redirect to default-offer.example.com
       - Assert: stream_id=stream3 in ClickHouse

    4. `TestWeightedStreamSelection`:
       - Send 100 clicks to `/weightcamp` with desktop UA
       - Assert: most clicks go to stream1 (weight=80) but some to stream2
       - Statistical check: stream1 should get 60-95% (wide range for random)

    5. `TestEntityBindingPersistence`:
       - Send first click to `/weightcamp` with a specific visitor cookie
       - Record which stream/offer was selected
       - Send second click with SAME visitor cookie
       - Assert: same stream and offer selected (binding worked)

    6. `TestLevel2LandingClick`:
       - Send Level 1 click to `/testcamp` (gets landing X)
       - Extract LP token from redirect URL
       - Send Level 2 click to `/lp/{token}/click`
       - Assert: 302 to offer URL, ClickHouse has both Level 1 and Level 2 rows

    7. `TestGatewayContext`:
       - Send request to bare domain (Host: test.local, path: /)
       - Assert: routes to "testcamp" campaign

    8. `TestMacroReplacement`:
       - Send click, capture redirect URL
       - Assert: `{click_id}` is replaced with actual click token in URL

    **Test infrastructure:**
    - Use `httptest.Server` wrapping the real server
    - Seed DB before tests, truncate after
    - For GeoIP mocking: set up a test geo resolver that returns fixed countries
    - For visitor cookies: use `http.CookieJar` to persist cookies across requests
  </action>
  <verify>go test -v -tags integration ./test/integration/ -run TestBotGetsBlocked -timeout 60s</verify>
  <done>All 8 test cases pass, proving routing, filtering, rotation, binding, Level 2, gateway, and macros work end-to-end</done>
</task>

<task type="auto">
  <name>Verification Report + State Update</name>
  <files>
    .gsd/phases/2/VERIFICATION.md
    .gsd/STATE.md
    .gsd/ROADMAP.md
    .gsd/JOURNAL.md
  </files>
  <action>
    **Create VERIFICATION.md** mapping each Phase 2 requirement to empirical proof:

    | Requirement | Proof | Status |
    |-------------|-------|--------|
    | 27 stream filter types | `go build ./internal/filter/...` + 8 source files | ✅/❌ |
    | 3-tier stream selection (FORCED→REGULAR→DEFAULT) | TestBotGetsBlocked + TestGeoFilterRouting + TestDefaultStreamFallback | ✅/❌ |
    | POSITION and WEIGHT campaign types | TestWeightedStreamSelection | ✅/❌ |
    | Weighted rotation (streams, landings, offers) | TestWeightedStreamSelection | ✅/❌ |
    | Entity binding (visitor→stream/landing/offer) | TestEntityBindingPersistence | ✅/❌ |
    | Macro replacement engine | TestMacroReplacement | ✅/❌ |
    | 19 action types | `go build ./internal/action/...` + 4 source files | ✅/❌ |
    | Level 2 pipeline (13 stages) | TestLevel2LandingClick | ✅/❌ |
    | Uniqueness tracking | Session service unit test | ✅/❌ |
    | Hit limit enforcement | HitLimit service unit test | ✅/❌ |
    | Gateway context | TestGatewayContext | ✅/❌ |
    | Valkey entity cache | Cache.Warmup + GetCampaignByAlias verified | ✅/❌ |

    **Update ROADMAP.md:** Set Phase 2 status to ✅ Complete.

    **Update STATE.md:** Set current position to Phase 3, task "Between phases".

    **Update JOURNAL.md:** Add session entry with accomplishments list.

    **Git commit:**
    ```bash
    git add -A && git commit -m "feat(phase-2): campaign engine complete — routing, filters, actions, binding, Level 2"
    ```
  </action>
  <verify>cat .gsd/phases/2/VERIFICATION.md</verify>
  <done>VERIFICATION.md created with all 12 requirements mapped, ROADMAP shows Phase 2 complete, journal updated</done>
</task>

## Success Criteria
- [ ] All 8 integration tests pass
- [ ] VERIFICATION.md covers all 12 Phase 2 requirements
- [ ] `go build ./...` and `go vet ./...` clean
- [ ] ROADMAP.md shows Phase 2 as ✅ Complete
- [ ] Final git commit captures all Phase 2 work
