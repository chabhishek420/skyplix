---
phase: 3
plan: 2
wave: 1
---

# Plan 3.2: Campaign & Stream CRUD (Core P0 Entities)

## Objective
Implement full RESTful CRUD for Campaigns and Streams — the two core entities
that the click pipeline depends on. Includes list, show, create, update, archive,
restore, enable/disable, and cache warmup trigger on every mutation.

## Context
- .gsd/phases/3/1-PLAN.md — Admin foundation (must be complete first)
- .gsd/ARCHITECTURE.md — Campaign/Stream model, cache warmup
- internal/model/models.go — Campaign and Stream structs
- internal/cache/cache.go — InvalidateCampaign, Warmup methods
- db/postgres/migrations/001_create_campaigns.up.sql — Campaign schema
- db/postgres/migrations/002_create_streams.up.sql — Stream schema
- reference/Keitaro_source_php/application/Component/Campaigns/Initializer.php — API routes

## Tasks

<task type="auto">
  <name>Campaign repository + handler with full CRUD</name>
  <files>
    internal/admin/repository/campaigns.go (NEW)
    internal/admin/handler/campaigns.go (NEW)
    internal/server/routes.go (MODIFY — wire campaign routes)
  </files>
  <action>
    1. Create `internal/admin/repository/campaigns.go`:
       - `CampaignRepo` struct with `*pgxpool.Pool`
       - `List(ctx, limit, offset) ([]model.Campaign, int, error)` — SELECT with COUNT
       - `GetByID(ctx, uuid) (*model.Campaign, error)` — SELECT by id
       - `Create(ctx, CreateCampaignInput) (*model.Campaign, error)` — INSERT RETURNING *
       - `Update(ctx, uuid, UpdateCampaignInput) (*model.Campaign, error)` — UPDATE SET ... RETURNING *
       - `Archive(ctx, uuid) error` — UPDATE state = 'archived'
       - `Restore(ctx, uuid) error` — UPDATE state = 'active'
       - `SetState(ctx, uuid, state) error` — UPDATE state = $2
       - `ListDeleted(ctx) ([]model.Campaign, error)` — WHERE state = 'archived'

       Input structs:
       ```go
       type CreateCampaignInput struct {
           Alias         string              `json:"alias"`
           Name          string              `json:"name"`
           Type          model.CampaignType  `json:"type"`
           BindVisitors  bool                `json:"bind_visitors"`
           TrafficSourceID *uuid.UUID        `json:"traffic_source_id"`
       }
       type UpdateCampaignInput struct {
           Alias         *string             `json:"alias"`
           Name          *string             `json:"name"`
           Type          *model.CampaignType `json:"type"`
           BindVisitors  *bool               `json:"bind_visitors"`
           TrafficSourceID *uuid.UUID        `json:"traffic_source_id"`
       }
       ```
       UpdateCampaignInput uses pointer fields for PATCH semantics (nil = don't update).

    2. Create `internal/admin/handler/campaigns.go`:
       - `handleListCampaigns` — GET /campaigns → list with pagination
       - `handleGetCampaign` — GET /campaigns/{id}
       - `handleCreateCampaign` — POST /campaigns → validate + create + ScheduleWarmup
       - `handleUpdateCampaign` — PUT /campaigns/{id} → validate + update + ScheduleWarmup
       - `handleArchiveCampaign` — DELETE /campaigns/{id} → archive + ScheduleWarmup
       - `handleRestoreCampaign` — POST /campaigns/{id}/restore
       - `handleEnableCampaign` — POST /campaigns/{id}/enable → state='active'
       - `handleDisableCampaign` — POST /campaigns/{id}/disable → state='disabled'
       - `handleListDeletedCampaigns` — GET /campaigns/deleted

       All mutations call `h.cache.ScheduleWarmup()` after successful DB write.

       Validation rules:
       - `alias`: required, alphanumeric + hyphens, min 1 char
       - `name`: required, min 1 char
       - `type`: must be POSITION or WEIGHT (default POSITION)

    3. Wire routes in `routes.go`:
       ```go
       r.Route("/campaigns", func(r chi.Router) {
           r.Get("/", h.handleListCampaigns)
           r.Post("/", h.handleCreateCampaign)
           r.Get("/deleted", h.handleListDeletedCampaigns)
           r.Route("/{id}", func(r chi.Router) {
               r.Get("/", h.handleGetCampaign)
               r.Put("/", h.handleUpdateCampaign)
               r.Delete("/", h.handleArchiveCampaign)
               r.Post("/restore", h.handleRestoreCampaign)
               r.Post("/enable", h.handleEnableCampaign)
               r.Post("/disable", h.handleDisableCampaign)
           })
       })
       ```

    IMPORTANT: `/deleted` route must come BEFORE `/{id}` in Chi to avoid being caught by the wildcard.
    IMPORTANT: Always use `context.Context` from `r.Context()` for pgx queries.
    IMPORTANT: updated_at must be set to NOW() on every update.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - 9 campaign endpoints compile and are wired
    - Repository handles all CRUD operations with proper SQL
    - Cache warmup triggered on mutations
    - Input validation on create/update
  </done>
</task>

<task type="auto">
  <name>Stream repository + handler with full CRUD</name>
  <files>
    internal/admin/repository/streams.go (NEW)
    internal/admin/handler/streams.go (NEW)
    internal/server/routes.go (MODIFY — wire stream routes)
  </files>
  <action>
    1. Create `internal/admin/repository/streams.go`:
       - `StreamRepo` struct with `*pgxpool.Pool`
       - `ListByCampaign(ctx, campaignID, limit, offset) ([]model.Stream, int, error)`
       - `GetByID(ctx, uuid) (*model.Stream, error)`
       - `Create(ctx, CreateStreamInput) (*model.Stream, error)`
       - `Update(ctx, uuid, UpdateStreamInput) (*model.Stream, error)`
       - `Archive(ctx, uuid) error`
       - `Restore(ctx, uuid) error`
       - `SetState(ctx, uuid, state) error`
       - `ListDeleted(ctx) ([]model.Stream, error)`

       Input structs:
       ```go
       type CreateStreamInput struct {
           CampaignID    uuid.UUID              `json:"campaign_id"`
           Name          string                 `json:"name"`
           Type          model.StreamType       `json:"type"`
           Position      int                    `json:"position"`
           Weight        int                    `json:"weight"`
           ActionType    string                 `json:"action_type"`
           ActionPayload map[string]interface{} `json:"action_payload"`
           Filters       []model.StreamFilter   `json:"filters"`
           DailyLimit    int64                  `json:"daily_limit"`
           TotalLimit    int64                  `json:"total_limit"`
       }
       ```

    2. Create `internal/admin/handler/streams.go`:
       - `handleListStreamsByCampaign` — GET /campaigns/{campaign_id}/streams
       - `handleGetStream` — GET /streams/{id}
       - `handleCreateStream` — POST /streams → validate + create + ScheduleWarmup
       - `handleUpdateStream` — PUT /streams/{id} → update + ScheduleWarmup
       - `handleArchiveStream` — DELETE /streams/{id} → archive + ScheduleWarmup
       - `handleRestoreStream` — POST /streams/{id}/restore
       - `handleEnableStream` — POST /streams/{id}/enable
       - `handleDisableStream` — POST /streams/{id}/disable
       - `handleListDeletedStreams` — GET /streams/deleted

       Validation rules:
       - `campaign_id`: required, must exist
       - `name`: required
       - `type`: REGULAR, FORCED, or DEFAULT
       - `action_type`: must match a known action type name

    3. Wire stream routes in `routes.go`:
       ```go
       // Nested under campaigns
       r.Get("/campaigns/{campaign_id}/streams", h.handleListStreamsByCampaign)

       r.Route("/streams", func(r chi.Router) {
           r.Post("/", h.handleCreateStream)
           r.Get("/deleted", h.handleListDeletedStreams)
           r.Route("/{id}", func(r chi.Router) {
               r.Get("/", h.handleGetStream)
               r.Put("/", h.handleUpdateStream)
               r.Delete("/", h.handleArchiveStream)
               r.Post("/restore", h.handleRestoreStream)
               r.Post("/enable", h.handleEnableStream)
               r.Post("/disable", h.handleDisableStream)
           })
       })
       ```

    IMPORTANT: Stream filters and action_payload are stored as JSONB columns.
    Use `pgx` JSONB scanning: `pgtype.JSONB` or scan directly into `map[string]interface{}`.
    IMPORTANT: ScheduleWarmup on stream mutation to refresh cached stream lists.
  </action>
  <verify>go build ./... && echo "BUILD OK"</verify>
  <done>
    - 10 stream endpoints compile and are wired
    - Streams linked to campaigns via campaign_id
    - JSONB filters/action_payload properly serialized/deserialized
    - Cache warmup triggered on mutations
  </done>
</task>

## Success Criteria
- [ ] `go build ./...` succeeds with all campaign + stream routes wired
- [ ] Campaigns: 9 endpoints (list, show, create, update, archive, restore, enable, disable, list-deleted)
- [ ] Streams: 10 endpoints (list-by-campaign, show, create, update, archive, restore, enable, disable, list-deleted, search)
- [ ] All mutations trigger cache warmup
