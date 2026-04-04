---
phase: 2
plan: 4
wave: 2
---

# Plan 2.4: Pipeline Stages 14-19 + Action Engine

## Objective
Replace the remaining 6 NoOp stages (14-19) with real implementations, and expand the ExecuteAction stage (20) from a single HttpRedirect to the full 19 action types. After this plan, the **complete Level 1 pipeline (stages 1-23) is fully operational**.

## Context
- .gsd/SPEC.md — 19 action types, affiliate network resolution, cost/payout models
- .gsd/ARCHITECTURE.md — Action types listing, stage 14-19 descriptions
- internal/pipeline/stage/20_execute_action.go — Current HttpRedirect-only implementation
- internal/server/routes.go — NoOp stages 14-19 (lines 80-86)
- internal/cache/cache.go — GetAffiliateNetwork (from Plan 2.1)
- internal/session/session.go — SaveSession (from Plan 2.2)
- internal/hitlimit/hitlimit.go — Check/Increment (from Plan 2.2)
- internal/cookie/cookie.go — SetVisitorCodeCookie, SetSessionCookie (from Plan 2.2)
- internal/macro/macro.go — Macro engine (built in this plan)

## Tasks

<task type="auto">
  <name>Stages 14-19 + Macro Engine</name>
  <files>
    internal/pipeline/stage/14_find_affiliate_network.go
    internal/pipeline/stage/15_update_hit_limit.go
    internal/pipeline/stage/16_update_costs.go
    internal/pipeline/stage/17_update_payout.go
    internal/pipeline/stage/18_save_uniqueness_session.go
    internal/pipeline/stage/19_set_cookie.go
    internal/macro/macro.go
  </files>
  <action>
    **Stage 14 — FindAffiliateNetwork:**
    - If `payload.Offer.AffiliateNetworkID` is set, load network from cache
    - Store in `payload.AffiliateNetwork` (add field to Payload)
    - If not set, skip (not all offers belong to a network)

    **Stage 15 — UpdateHitLimit:**
    - Get stream's daily limit from `payload.Stream.DailyLimit`
    - If limit > 0: call `hitlimit.Check(ctx, streamID, limit)`
    - If NOT allowed (cap exceeded): set `payload.Abort = true`, select DEFAULT stream as fallback
    - If allowed: call `hitlimit.Increment(ctx, streamID)`

    **Stage 16 — UpdateCosts:**
    - Read cost from query params: `?cost=X` or `?cpc=X`
    - If traffic source has cost param mapping, use that
    - Set `payload.RawClick.Cost = parsedCost`
    - Cost model: CPC (cost per click) is the default

    **Stage 17 — UpdatePayout:**
    - If offer has payout: `payload.RawClick.Payout = offer.Payout`
    - If affiliate network has payout override, use that
    - Already partially set in ChooseOffer (stage 12) — this stage handles overrides

    **Stage 18 — SaveUniquenessSession:**
    - Call `session.SaveSession(ctx, visitorCode, sessionData)`
    - Session data includes campaign_id, stream_id, uniqueness flags
    - This persists the session state so it survives across requests

    **Stage 19 — SetCookie:**
    - Call `cookie.SetVisitorCodeCookie(w, visitorCode)`
    - Call `cookie.SetSessionCookie(w, sessionToken)` if session was created
    - Write binding cookies if entity binding is active (Plan 2.5)

    **Macro Engine (`internal/macro/macro.go`):**

    URL macro replacement for offer/landing URLs. Used by ExecuteAction.

    ```go
    func Replace(url string, click *model.RawClick, campaign *model.Campaign) string
    ```

    **Supported macros:**
    - `{click_id}` → click token
    - `{campaign_id}` → campaign UUID
    - `{campaign_name}` → campaign name
    - `{stream_id}` → stream UUID
    - `{country}` → country code
    - `{city}` → city name
    - `{region}` → region
    - `{device}` → device type
    - `{os}` → OS name
    - `{os_version}` → OS version
    - `{browser}` → browser name
    - `{browser_version}` → browser version
    - `{ip}` → IP address
    - `{isp}` → ISP name
    - `{user_agent}` → user agent (URL-encoded)
    - `{referrer}` → referrer (URL-encoded)
    - `{sub_id_1}` through `{sub_id_5}` → sub IDs
    - `{cost}` → cost value
    - `{payout}` → payout value
    - `{timestamp}` → Unix timestamp
    - `{random}` → random 8-char hex string

    **Implementation:** Use `strings.NewReplacer` built once per call. URL-encode values that may contain special characters.

    **Wire into routes.go:** Replace NoOp 14-19 with real stages. Pass dependencies from server.go.
  </action>
  <verify>go build ./internal/pipeline/stage/... && go build ./internal/macro/...</verify>
  <done>Stages 14-19 compile, macro engine supports 20+ macros, routes.go updated</done>
</task>

<task type="auto">
  <name>Full Action Engine (19 action types)</name>
  <files>
    internal/action/action.go
    internal/action/redirect.go
    internal/action/content.go
    internal/action/proxy.go
    internal/action/special.go
    internal/pipeline/stage/20_execute_action.go (rewrite)
  </files>
  <action>
    Create `internal/action/` package with the full 19 action types.

    **Core interface (`action.go`):**
    ```go
    type Action interface {
        Type() string
        Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error
    }

    type ActionContext struct {
        RedirectURL string   // The target URL (offer or landing)
        Click       *model.RawClick
        Campaign    *model.Campaign
        Stream      *model.Stream
        MacroReplace func(string) string
    }

    type Engine struct {
        actions map[string]Action
    }

    func NewEngine() *Engine
    func (e *Engine) Execute(actionType string, w, r, ctx) error
    ```

    **`redirect.go` — Redirect-based actions:**
    - `HttpRedirect` — 302 redirect (current behavior)
    - `Meta` — HTML page with `<meta http-equiv="refresh" content="0;url=...">`
    - `DoubleMeta` — Two meta redirects (first to intermediate, then to offer)
    - `BlankReferrer` — Meta redirect via `data:text/html` URL to strip referrer
    - `Js` — HTML with `<script>window.location.href="..."</script>`
    - `JsForIframe` — JS redirect targeting parent frame
    - `JsForScript` — Returns JS snippet (for `<script src="...">` embedding)
    - `FormSubmit` — Hidden form auto-submitted via JS

    **`content.go` — Content-serving actions:**
    - `Frame` / `Iframe` — HTML page with `<iframe src="...">` (full page)
    - `ShowHtml` — Serve raw HTML from stream's action_payload
    - `ShowText` — Serve plain text
    - `LocalFile` — Serve a file from disk (path in action_payload)
    - `Status404` — Return 404 response
    - `DoNothing` — Return 200 with empty body (tracking pixel behavior)
    - `Curl` — Fetch remote URL server-side and return its content

    **`proxy.go` — Reverse proxy action (critical for cloaking):**
    - `Remote` — Full reverse proxy: fetch the target URL server-side, rewrite relative URLs, return the content as if served locally. Used for safe pages.
    - Use `net/http` client with 10s timeout
    - Rewrite relative URLs in HTML to absolute
    - Copy response headers (Content-Type, etc.)

    **`special.go` — Special routing actions:**
    - `SubId` — Route based on sub_id value (lookup different offer per sub_id)
    - `ToCampaign` — Redirect to another campaign (inter-campaign routing, max 10 hops)

    **Rewrite stage 20 (`20_execute_action.go`):**
    - Replace the current hardcoded HttpRedirect with `action.Engine.Execute()`
    - Action type comes from `payload.Stream.ActionType`
    - Apply macro replacement to redirect URL before executing
    - Pass `ActionContext` with all click data for macros

    **Important design note:** `Remote` and `Curl` are the only actions that make external HTTP requests. They MUST have timeouts (10s max) and MUST NOT be called on the hot path for regular traffic — they're for cloaking/safe-page scenarios only.
  </action>
  <verify>go build ./internal/action/... && go build ./internal/pipeline/stage/...</verify>
  <done>19 action types implemented, ExecuteAction uses action.Engine, macro replacement applied to URLs, Remote reverse proxy works</done>
</task>

## Success Criteria
- [ ] `go build ./...` clean
- [ ] `go vet ./...` clean
- [ ] No more NoOp stages in routes.go (all 23 stages are real)
- [ ] Macro engine replaces 20+ URL tokens
- [ ] Action engine handles all 19 action types
- [ ] Stages 14-19 are fully wired with cache, session, hitlimit, cookie dependencies
