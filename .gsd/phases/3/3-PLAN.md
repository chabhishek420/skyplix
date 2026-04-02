---
phase: 3
plan: 3
wave: 2
---

# Plan 3.3: Offer, Landing, Affiliate Network CRUD + Stream Associations

## Objective
Implement CRUD for Offers, Landings, and Affiliate Networks — the P0 entities
that streams reference for routing. Also implement the stream↔offer and
stream↔landing association endpoints (adding/removing offers/landings from
a stream with weights).

## Context
- .gsd/phases/3/2-PLAN.md — Campaign and Stream CRUD (must be complete)
- internal/model/models.go — Offer, Landing, AffiliateNetwork, WeightedOffer, WeightedLanding structs
- db/postgres/migrations/003_create_offers_landings.up.sql — offer/landing/network tables
- db/postgres/migrations/002_create_streams.up.sql — stream_offers, stream_landings join tables
- reference/Keitaro_source_php/application/Component/Offers/Initializer.php

## Tasks

<task type="auto">
  <name>Offer + Landing + AffiliateNetwork repositories and handlers</name>
  <files>
    internal/admin/repository/offers.go (NEW)
    internal/admin/repository/landings.go (NEW)
    internal/admin/repository/networks.go (NEW)
    internal/admin/handler/offers.go (NEW)
    internal/admin/handler/landings.go (NEW)
    internal/admin/handler/networks.go (NEW)
    internal/server/routes.go (MODIFY — wire routes)
  </files>
  <action>
    1. Register repositories:
       - Update `internal/admin/handler/handler.go` struct to include `offers`, `landings`, `networks`
       - Update `NewHandler` to initialize them.

    2. Offers CRUD (6 endpoints):
       - GET /offers — list with pagination
       - GET /offers/:id — show
       - POST /offers — create (name, url, affiliate_network_id, payout)
       - PUT /offers/:id — update
       - DELETE /offers/:id — archive
       - POST /offers/:id/clone — duplicate

       Validation: name required, url required (valid URL format), payout >= 0

    2. Landings CRUD (6 endpoints):
       - GET /landing_pages — list (Keitaro uses /landing_pages, not /landings)
       - GET /landing_pages/:id — show
       - POST /landing_pages — create (name, url)
       - PUT /landing_pages/:id — update
       - DELETE /landing_pages/:id — archive
       - PUT /landing_pages/:id/clone — clone

       Validation: name required, url required

    3. Affiliate Networks CRUD (6 endpoints):
       - GET /affiliate_networks — list
       - GET /affiliate_networks/:id — show
       - POST /affiliate_networks — create (name, postback_url)
       - PUT /affiliate_networks/:id — update
       - DELETE /affiliate_networks/:id — archive
       - POST /affiliate_networks/:id/clone — clone

       Validation: name required

    Each follows the same repository pattern as campaigns.
    All mutations call ScheduleWarmup().

    IMPORTANT: Use `/landing_pages` not `/landings` in routes (matches Keitaro API).
    IMPORTANT: Clone endpoint should copy all fields except id, created_at, updated_at, and append " (copy)" to name.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - 18 new endpoints across 3 entities
    - Repository + Handler files for each entity
    - All routes wired in routes.go
    - Clone endpoints work correctly
    - All mutations trigger cache warmup
  </done>
</task>

<task type="auto">
  <name>Stream↔Offer and Stream↔Landing association endpoints</name>
  <files>
    internal/admin/repository/associations.go (NEW)
    internal/admin/handler/associations.go (NEW)
    internal/server/routes.go (MODIFY — wire association routes)
  </files>
  <action>
    1. Register repository:
       - Update `internal/admin/handler/handler.go` struct to include `associations *repository.AssociationRepo`
       - Update `NewHandler` to initialize it.

    2. Create `internal/admin/repository/associations.go`:
       - `AddOfferToStream(ctx, streamID, offerID, weight) error`
         — INSERT INTO stream_offers (stream_id, offer_id, weight) VALUES (...)
         — ON CONFLICT (stream_id, offer_id) DO UPDATE SET weight = EXCLUDED.weight
       - `RemoveOfferFromStream(ctx, streamID, offerID) error`
         — DELETE FROM stream_offers WHERE stream_id = $1 AND offer_id = $2
       - `ListStreamOffers(ctx, streamID) ([]model.WeightedOffer, error)`
         — SELECT o.*, so.weight FROM offers o JOIN stream_offers so ...
       - `AddLandingToStream(ctx, streamID, landingID, weight) error`
       - `RemoveLandingFromStream(ctx, streamID, landingID) error`
       - `ListStreamLandings(ctx, streamID) ([]model.WeightedLanding, error)`

    2. Create `internal/admin/handler/associations.go`:
       - `handleListStreamOffers` — GET /streams/:id/offers
       - `handleAddStreamOffer` — POST /streams/:id/offers
         Body: `{"offer_id": "uuid", "weight": 100}`
       - `handleRemoveStreamOffer` — DELETE /streams/:id/offers/:offer_id
       - `handleListStreamLandings` — GET /streams/:id/landings
       - `handleAddStreamLanding` — POST /streams/:id/landings
         Body: `{"landing_id": "uuid", "weight": 100}`
       - `handleRemoveStreamLanding` — DELETE /streams/:id/landings/:landing_id

    3. Wire routes:
       ```go
       r.Route("/streams/{id}", func(r chi.Router) {
           // ... existing stream routes ...
           r.Get("/offers", h.handleListStreamOffers)
           r.Post("/offers", h.handleAddStreamOffer)
           r.Delete("/offers/{offer_id}", h.handleRemoveStreamOffer)
           r.Get("/landings", h.handleListStreamLandings)
           r.Post("/landings", h.handleAddStreamLanding)
           r.Delete("/landings/{landing_id}", h.handleRemoveStreamLanding)
       })
       ```

    IMPORTANT: Weight defaults to 100 if not provided.
    IMPORTANT: UPSERT semantics for add (update weight if already linked).
    IMPORTANT: All mutations trigger ScheduleWarmup.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - 6 association endpoints compile
    - Offers and landings can be linked/unlinked from streams with weights
    - UPSERT handles re-adding with new weight
    - Cache warmup triggered on all association changes
  </done>
</task>

## Success Criteria
- [ ] `go build ./...` succeeds with all entity + association routes
- [ ] Offers: 6 endpoints
- [ ] Landings: 6 endpoints (under /landing_pages)
- [ ] Affiliate Networks: 6 endpoints
- [ ] Stream associations: 6 endpoints (offer + landing linking with weights)
- [ ] Total: 24 new endpoints
