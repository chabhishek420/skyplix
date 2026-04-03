---
phase: 5
plan: 3
wave: 3
depends_on: ["5.2"]
files_modified:
  - internal/admin/handler/reports.go
  - internal/server/routes.go
  - internal/server/server.go
autonomous: true
requirements:
  - report builder
  - Postback endpoint
  - stats aggregation
  - conversion→click linking via click_token
must_haves:
  truths:
    - "GET /api/v1/reports endpoint exists and is protected by API key auth middleware."
    - "Report endpoint accepts group_by, date_from, date_to, preset, campaign_id, country, device_type, sort, limit, offset query params."
    - "Response shape is { rows: [...], summary: {...}, meta: {...} } matching the research spec."
    - "Date presets (today, yesterday, last_7d, last_30d, this_month) resolve to correct date ranges."
    - "ReportsHandler is wired in server.go with ClickHouse reader and PostgreSQL pool dependencies."
  artifacts:
    - "internal/admin/handler/reports.go"
    - "internal/server/routes.go (updated)"
    - "internal/server/server.go (updated)"
---

# Plan 5.3: Reporting API Endpoint & Server Wiring

<objective>
Implement the HTTP handler for the reporting API and wire it into the server. This is the HTTP layer that sits on top of the analytics service (Plan 5.2).

Single endpoint: `GET /api/v1/reports` with flexible query parameters for dimensions, filters, date range, and pagination.

Output:
- `internal/admin/handler/reports.go` — ReportsHandler with query param parsing and response rendering.
- Updated `internal/server/routes.go` — new route under the admin-auth-protected `/api/v1` group.
- Updated `internal/server/server.go` — ReportsHandler construction and DI wiring.
</objective>

<context>
Load for context:
- internal/analytics/service.go (analytics.Service — built in Plan 5.2)
- internal/analytics/models.go (ReportQuery, ReportResponse DTOs)
- internal/admin/handler/helpers.go (respondJSON, respondError, parsePagination patterns)
- internal/admin/handler/handler.go (handler struct DI pattern)
- internal/admin/handler/postback.go (PostbackHandler pattern — separate handler struct)
- internal/server/routes.go (existing route structure)
- internal/server/server.go (existing server construction, chReader availability)
- .planning/phases/05-conversion-tracking-analytics/05-RESEARCH.md (API contract, response shape)
</context>

<tasks>

<task type="auto">
  <name>Create Reports Handler</name>
  <files>internal/admin/handler/reports.go</files>
  <action>
    Create `internal/admin/handler/reports.go` with a `ReportsHandler` struct (following PostbackHandler pattern — separate from the main Handler):

    ```go
    type ReportsHandler struct {
      logger    *zap.Logger
      analytics *analytics.Service
    }
    ```

    **Constructor**: `NewReportsHandler(logger *zap.Logger, analytics *analytics.Service) *ReportsHandler`

    **HandleReport(w http.ResponseWriter, r *http.Request)**:
    1. Parse query params into `analytics.ReportQuery`:
       - `group_by`: comma-separated string -> []string (e.g., "campaign,country" -> ["campaign", "country"])
       - `date_from` / `date_to`: parse as "2006-01-02" date format
       - `preset`: resolve to date_from/date_to:
         - "today" -> today 00:00 to today 23:59
         - "yesterday" -> yesterday 00:00 to yesterday 23:59
         - "last_7d" -> 7 days ago to today
         - "last_30d" -> 30 days ago to today
         - "this_month" -> first of current month to today
       - If both preset and explicit dates provided, preset takes precedence
       - If neither provided, default to "today"
       - `campaign_id`: single UUID or comma-separated UUIDs -> filter
       - `country`: comma-separated country codes -> filter
       - `device_type`: single value -> filter
       - `stream_id`: single UUID -> filter
       - `offer_id`: single UUID -> filter
       - `sort`: format "field:dir" (e.g., "clicks:desc"), default "clicks:desc"
       - `limit`: integer, default 50, max 1000
       - `offset`: integer, default 0

    2. Validate parsed query:
       - Return 400 for invalid date format
       - Return 400 for unknown group_by dimensions
       - Return 400 for invalid UUID format in filters
       - Return 400 for unknown preset value

    3. Call `analytics.Service.GenerateReport(ctx, query)`

    4. Return JSON response with `respondJSON` pattern:
       - 200 with ReportResponse on success
       - 400 for validation errors (JSON error body)
       - 500 for internal errors (JSON error body, log details)

    **Helper functions**:
    - `parseGroupBy(s string) []string` — split and trim
    - `parseDate(s string) (time.Time, error)` — parse YYYY-MM-DD
    - `resolvePreset(preset string) (time.Time, time.Time, error)` — preset to date range
    - `parseSort(s string) (field, dir string)` — split "field:dir", validate field name

    Use the same `respondJSON` / `respondError` pattern as the main Handler, but as standalone functions
    (since ReportsHandler is a separate struct). Either duplicate the small helpers or extract them to
    a shared package-level function.

    AVOID:
    - Passing raw query string values to the analytics service — always parse and validate first.
    - Returning stack traces in error responses.
    - Using POST for the reports endpoint (GET is correct for read-only queries).
  </action>
  <verify>go build ./internal/admin/handler/...</verify>
  <done>Reports handler compiles and provides complete query param parsing with validation.</done>
</task>

<task type="auto">
  <name>Wire Reports Handler into Server</name>
  <files>internal/server/server.go, internal/server/routes.go</files>
  <action>
    **server.go changes**:
    1. Add `reportsHandler *handler.ReportsHandler` field to Server struct.
    2. Import `internal/analytics` package.
    3. In `New()`, after the ClickHouse reader is created and the postback handler is wired:
       - Create analytics service: `analyticsSvc := analytics.New(s.chReader, s.db, logger)`
       - Create reports handler: `s.reportsHandler = handler.NewReportsHandler(logger, analyticsSvc)`
       - Guard with `if s.chReader != nil` — if no ClickHouse reader, skip (reports will return 503).

    **routes.go changes**:
    1. Inside the `/api/v1` route group (after settings routes, before the closing brace):
       - Add: `r.Get("/reports", s.reportsHandler.HandleReport)`
       - Guard: only register if `s.reportsHandler != nil`

    AVOID:
    - Creating a new ClickHouse connection — reuse `s.chReader` (already configured with read-optimized settings).
    - Adding reports routes outside the API key auth middleware group.
    - Breaking existing route registrations.
  </action>
  <verify>go build ./cmd/zai-tds/... && go build ./...</verify>
  <done>Reports handler is wired and the full binary compiles. GET /api/v1/reports route is registered under API key auth.</done>
</task>

</tasks>

<verification>
After all tasks, verify:
- [ ] `go build ./...` passes with no errors.
- [ ] `go vet ./...` reports no issues.
- [ ] `/api/v1/reports` route is registered in the admin-auth-protected group.
- [ ] Reports handler validates all query parameters before passing to analytics service.
- [ ] Date presets resolve to correct UTC date ranges.
- [ ] Response Content-Type is application/json.
- [ ] Error responses use consistent JSON format `{"error": "message"}`.
- [ ] No new ClickHouse connections created — reuses chReader.
</verification>

<success_criteria>
- [ ] GET /api/v1/reports endpoint is functional and protected by API key middleware.
- [ ] Query params are fully parsed, validated, and passed to the analytics service.
- [ ] Response shape matches the research spec (rows + summary + meta).
- [ ] Server compiles and starts successfully with the new handler wired.
</success_criteria>
