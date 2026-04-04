---
phase: 2
plan: 5
wave: 3
---

# Plan 2.5: Entity Binding + Level 2 Pipeline + Gateway Context

## Objective
Implement the three remaining Phase 2 features:
1. **Entity binding** — bind returning visitors to the same stream/landing/offer via Valkey + cookie persistence (Keitaro's `EntityBindingService` pattern).
2. **Level 2 pipeline** — the 13-stage landing→offer click pipeline triggered when visitors click through from landing pages.
3. **Gateway context** — handle bare domain hits (no campaign alias) via domain→campaign mapping.

## Context
- .gsd/SPEC.md — Entity binding, Level 2 pipeline, gateway context
- .gsd/ARCHITECTURE.md — Level 2 pipeline stages, Valkey bind keys, domain→campaign mapping
- internal/pipeline/pipeline.go — Pipeline framework (reuse for Level 2)
- internal/server/routes.go — Need to add Level 2 route (/lp/{token}/click) + update gateway handler
- internal/pipeline/stage/ — All Level 1 stages (many reused in Level 2)
- internal/cache/cache.go — Entity cache (from Plan 2.1)
- db/postgres/migrations/004_create_domains_users.up.sql — domains table with campaign_id FK

## Tasks

<task type="auto">
  <name>Entity Binding Service</name>
  <files>
    internal/binding/binding.go
    internal/pipeline/stage/9_choose_stream.go (modify to use binding)
    internal/pipeline/stage/11_choose_landing.go (modify to use binding)
    internal/pipeline/stage/12_choose_offer.go (modify to use binding)
  </files>
  <action>
    Create `internal/binding/binding.go` — visitor entity binding service.

    **Purpose:** When `campaign.BindVisitors == true`, once a visitor is assigned to a stream/landing/offer, they should ALWAYS get the same one on return visits. This prevents A/B test contamination and provides consistent user experience.

    ```go
    type Service struct {
        vk     *redis.Client
        logger *zap.Logger
    }

    func New(vk *redis.Client, logger *zap.Logger) *Service

    // GetBinding checks if a visitor already has a binding for this entity type + scope.
    // Returns the bound entity UUID, or uuid.Nil if no binding exists.
    // BindType: "stream", "landing", "offer"
    // ScopeID: campaignID for stream binding, streamID for landing/offer binding
    func (s *Service) GetBinding(ctx context.Context, visitorCode string, bindType string, scopeID uuid.UUID) (uuid.UUID, error)

    // SetBinding creates a binding for a visitor to an entity.
    // TTL: 30 days (matching Keitaro's default)
    func (s *Service) SetBinding(ctx context.Context, visitorCode string, bindType string, scopeID uuid.UUID, entityID uuid.UUID) error
    ```

    **Valkey key pattern:** `bind:{bindType}:{scopeID}:{visitorCode}` → entity UUID string, TTL 30 days

    **Integration into ChooseStream (stage 9):**
    - If `campaign.BindVisitors == true`:
      - Before filter evaluation, check `binding.GetBinding(visitor, "stream", campaignID)`
      - If binding exists AND the bound stream is still active → use it, skip filter evaluation
      - If binding doesn't exist → normal 3-tier selection, then `binding.SetBinding()`

    **Integration into ChooseLanding (stage 11):**
    - If `campaign.BindVisitors == true`:
      - Check `binding.GetBinding(visitor, "landing", streamID)`
      - If bound → use, else → rotate + bind

    **Integration into ChooseOffer (stage 12):**
    - If `campaign.BindVisitors == true`:
      - Check `binding.GetBinding(visitor, "offer", streamID)`
      - If bound → use, else → rotate + bind

    **Add `bindingSvc *binding.Service` to server.go and pass to stages.**
  </action>
  <verify>go build ./internal/binding/... && go build ./internal/pipeline/stage/...</verify>
  <done>binding.go compiles, stages 9/11/12 check for existing bindings before rotation, bindings stored in Valkey with 30d TTL</done>
</task>

<task type="auto">
  <name>Level 2 Pipeline + Gateway Context</name>
  <files>
    internal/pipeline/stage/l2_find_campaign.go
    internal/pipeline/stage/l2_update_params.go
    internal/lptoken/lptoken.go
    internal/server/routes.go (add Level 2 route + gateway handler)
    internal/server/server.go (add Level 2 handler method)
  </files>
  <action>
    **LP Token Service (`internal/lptoken/lptoken.go`):**

    Manages the landing page → offer click linking token.

    ```go
    type Service struct {
        vk     *redis.Client
        logger *zap.Logger
    }

    func New(vk *redis.Client, logger *zap.Logger) *Service

    // Create generates an LP token and stores the Level 1 click context in Valkey.
    // Called when a landing page is selected (after ChooseLanding).
    // The LP token is embedded in the landing page's CTA link.
    func (s *Service) Create(ctx context.Context, click *model.RawClick) (string, error)

    // Resolve retrieves the Level 1 click context from an LP token.
    // Called at the start of the Level 2 pipeline.
    func (s *Service) Resolve(ctx context.Context, token string) (*LPContext, error)

    type LPContext struct {
        CampaignID  uuid.UUID
        StreamID    uuid.UUID
        VisitorCode string
        SubIDs      [5]string
        // ... other fields needed by Level 2
    }
    ```

    **Valkey key:** `lp:{token}` → JSON LPContext, TTL 1h

    **Level 2 Pipeline construction:**

    Add `handleLandingClick` method to server.go:

    ```go
    func (s *Server) handleLandingClick(w http.ResponseWriter, r *http.Request) {
        token := chi.URLParam(r, "token")
        // Build Level 2 pipeline (13 stages — reuse existing stage implementations)
        p := pipeline.New(
            &stage.L2FindCampaignStage{LPToken: s.lpTokenSvc, Cache: s.cache},
            &stage.L2UpdateParamsStage{},
            &stage.CheckDefaultCampaignStage{},
            &stage.CheckParamAliasesStage{Cache: s.cache},
            &stage.ChooseStreamStage{Cache: s.cache, Filter: s.filterEngine, ...},
            &stage.ChooseOfferStage{Cache: s.cache, ...},
            &stage.FindAffiliateNetworkStage{Cache: s.cache},
            &stage.UpdateCostsStage{},
            &stage.UpdatePayoutStage{},
            &stage.SetCookieStage{},
            &stage.ExecuteActionStage{ActionEngine: s.actionEngine, Macro: s.macro},
            &stage.CheckSendingToAnotherCampaignStage{},
            &stage.StoreRawClicksStage{ClickChan: s.chWriter.Chan()},
        )
        // ... run pipeline
    }
    ```

    **Level 2-specific stages:**
    - `L2FindCampaignStage` — resolves campaign from LP token context instead of URL alias
    - `L2UpdateParamsStage` — merges landing-level params into click (e.g., landing page may add sub_id values)

    **Note:** Most stages are reused from Level 1 (ChooseStream, ChooseOffer, ExecuteAction, etc.). Level 2 does NOT re-run ChooseLanding (the landing was already chosen in Level 1).

    **Route registration in routes.go:**
    ```go
    r.Get("/lp/{token}/click", s.handleLandingClick)
    ```

    **Gateway Context:**
    Update `handleClick` to handle bare domain (no alias):
    1. When `alias == ""`, look up domain→campaign mapping from cache
    2. `cache.GetCampaignByDomain(ctx, r.Host)` → Campaign
    3. If found, set campaign and continue pipeline
    4. If not found, return 404 (current behavior)

    Add `GetCampaignByDomain` method to cache.go.
  </action>
  <verify>go build ./... && go vet ./...</verify>
  <done>Level 2 route exists at /lp/{token}/click, LP token creates/resolves click context, gateway context resolves domain→campaign, full project builds clean</done>
</task>

## Success Criteria
- [ ] `go build ./...` clean
- [ ] `go vet ./...` clean
- [ ] Entity binding service stores/retrieves visitor→entity bindings in Valkey
- [ ] ChooseStream/ChooseLanding/ChooseOffer respect binding when campaign.BindVisitors=true
- [ ] Level 2 pipeline registered at `/lp/{token}/click`
- [ ] LP token service creates and resolves click context
- [ ] Gateway context resolves bare domain to campaign via domain table
