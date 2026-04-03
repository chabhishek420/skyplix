---
phase: 5
plan: 4
wave: 2
depends_on: []
files_modified:
  - internal/macro/postback.go
  - internal/admin/handler/networks.go
  - test/unit/macro/postback_test.go
autonomous: true
requirements:
  - Postback endpoint
  - conversion→click linking via click_token
must_haves:
  truths:
    - "Postback URL template renderer expands Keitaro-compatible macros ({click_id}, {subid}, {payout}, {status}, {external_id}, {campaign_id}, {offer_id}, {sub_id_1} through {sub_id_5})."
    - "Template generation endpoint returns a ready-to-paste URL with macro placeholders for affiliate networks."
    - "Macro expansion does not modify the existing macro.Replace function — uses a new ReplacePostback function."
  artifacts:
    - "internal/macro/postback.go"
    - "test/unit/macro/postback_test.go"
---

# Plan 5.4: Postback URL Template Generation

<objective>
Extend the macro package with postback URL template rendering and add an admin API endpoint for generating postback URLs that operators paste into affiliate networks.

This is the operator-facing convenience feature: given a base URL template with Keitaro-compatible macros (e.g., `https://tracker.example.com/postback/KEY?sub_id={click_id}&payout={payout}&status={status}`), the system can:
1. Generate a template URL with the operator's actual postback key pre-filled
2. Render a concrete URL given specific conversion data (for preview/testing)

Output:
- `internal/macro/postback.go` — postback-specific macro expansion and template generation.
- Updated `internal/admin/handler/networks.go` — endpoint to generate postback URL for a network.
- Unit tests for macro expansion.
</objective>

<context>
Load for context:
- internal/macro/macro.go (existing Replace function, replacement pair pattern)
- internal/admin/handler/networks.go (existing affiliate network CRUD handlers)
- internal/admin/handler/postback.go (postback key setting name, key cache pattern)
- internal/admin/repository/settings.go (settings repository for fetching postback key)
- internal/model/conversion.go (Conversion, AttributionData structs)
- .planning/phases/05-conversion-tracking-analytics/05-RESEARCH.md (postback macro set, template storage)
- .planning/phases/05-conversion-tracking-analytics/05-CONTEXT.md (Keitaro-compatible macro decisions)
</context>

<tasks>

<task type="auto">
  <name>Create Postback Macro Functions</name>
  <files>internal/macro/postback.go, test/unit/macro/postback_test.go</files>
  <action>
    Create `internal/macro/postback.go` with:

    **PostbackMacros** — constant list of supported macros with descriptions:
    ```go
    var PostbackMacros = []MacroDef{
      {Name: "{click_id}", Description: "Click token / sub ID"},
      {Name: "{subid}", Description: "Alias for click_id"},
      {Name: "{payout}", Description: "Conversion payout amount"},
      {Name: "{status}", Description: "Conversion status (lead/sale/rejected/hold)"},
      {Name: "{external_id}", Description: "External transaction ID"},
      {Name: "{campaign_id}", Description: "Campaign UUID"},
      {Name: "{offer_id}", Description: "Offer UUID"},
      {Name: "{sub_id_1}", Description: "Traffic source sub ID 1"},
      {Name: "{sub_id_2}", Description: "Traffic source sub ID 2"},
      {Name: "{sub_id_3}", Description: "Traffic source sub ID 3"},
      {Name: "{sub_id_4}", Description: "Traffic source sub ID 4"},
      {Name: "{sub_id_5}", Description: "Traffic source sub ID 5"},
    }
    ```

    **MacroDef** struct: `{Name string, Description string}`

    **GeneratePostbackURL(baseURL string, postbackKey string) string**:
    - Takes the operator's tracker domain/base URL and postback key
    - Returns a template URL like:
      `{baseURL}/postback/{postbackKey}?sub_id={click_id}&payout={payout}&status={status}`
    - This is the URL operators paste into affiliate networks
    - If baseURL is empty, use a placeholder like `https://your-tracker.com`

    **ReplacePostback(template string, data *PostbackData) string**:
    - Takes a URL template string with macro placeholders
    - Replaces macros with actual values from PostbackData
    - Uses the same `strings.ReplaceAll` pattern as the existing `Replace()` function
    - URL-encodes values that might contain special characters

    **PostbackData** struct:
    ```go
    type PostbackData struct {
      ClickToken string
      Payout     float64
      Status     string
      ExternalID string
      CampaignID string
      OfferID    string
      SubID1     string
      SubID2     string
      SubID3     string
      SubID4     string
      SubID5     string
    }
    ```

    Create `test/unit/macro/postback_test.go` with table-driven tests:
    - GeneratePostbackURL produces correct URL structure
    - ReplacePostback replaces all macros correctly
    - ReplacePostback handles empty/missing values gracefully (empty string, not placeholder)
    - {click_id} and {subid} both resolve to ClickToken
    - URL encoding of special characters in values
    - Empty template returns empty string
    - Template with no macros returns unchanged

    AVOID:
    - Modifying the existing `macro.Replace` function.
    - Using regex for replacement (strings.ReplaceAll is faster and sufficient).
    - Including macros that don't have a data source in the postback flow.
  </action>
  <verify>go test -v ./test/unit/macro/...</verify>
  <done>Postback macro functions compile and all unit tests pass.</done>
</task>

<task type="auto">
  <name>Add Postback URL Generation to Networks Handler</name>
  <files>internal/admin/handler/networks.go</files>
  <action>
    Add a new endpoint to the networks handler for generating postback URLs:

    **HandleGeneratePostbackURL(w http.ResponseWriter, r *http.Request)**:
    1. Get the affiliate network ID from URL param
    2. Load the network from the repository (to get its PostbackURL template if set)
    3. Load the global postback key from settings (reuse the settings repository already available on Handler)
    4. If the network has a custom PostbackURL template, use it
    5. Otherwise, generate a default template using `macro.GeneratePostbackURL()`
    6. Return JSON response:
       ```json
       {
         "postback_url": "https://tracker.com/postback/KEY?sub_id={click_id}&payout={payout}&status={status}",
         "macros": [
           {"name": "{click_id}", "description": "Click token / sub ID"},
           ...
         ]
       }
       ```

    Also add the route in routes.go:
    - `r.Get("/affiliate_networks/{id}/postback_url", s.adminHandler.HandleGeneratePostbackURL)`
    - Place inside the existing `/affiliate_networks/{id}` route group

    The Handler struct already has `settings *repository.SettingsRepository` so no new dependency injection needed.

    AVOID:
    - Creating a separate handler struct for this — it fits naturally on the existing Handler.
    - Hardcoding the tracker domain — use the request's Host header or a setting.
    - Exposing the postback key in logs.
  </action>
  <verify>go build ./internal/admin/handler/... && go build ./...</verify>
  <done>Postback URL generation endpoint compiles and is wired into routes. Full binary builds.</done>
</task>

</tasks>

<verification>
After all tasks, verify:
- [ ] `go build ./...` passes.
- [ ] `go test ./test/unit/macro/...` passes.
- [ ] `internal/macro/postback.go` exists with GeneratePostbackURL and ReplacePostback functions.
- [ ] GET `/api/v1/affiliate_networks/{id}/postback_url` route is registered.
- [ ] Postback URL contains the actual postback key from settings.
- [ ] All Keitaro-compatible macros are supported: {click_id}, {subid}, {payout}, {status}, {external_id}, {campaign_id}, {offer_id}, {sub_id_1} through {sub_id_5}.
- [ ] Existing macro.Replace function is unmodified.
</verification>

<success_criteria>
- [ ] Postback URL templates use Keitaro-compatible macro syntax.
- [ ] Generated URLs are ready-to-paste into affiliate network postback configuration.
- [ ] Macro expansion correctly substitutes all supported placeholders.
- [ ] Unit tests validate macro expansion with edge cases.
</success_criteria>
