---
phase: 5
plan: 3
wave: 3
depends_on: ["5.2"]
files_modified:
  - internal/analytics/service.go
  - internal/admin/handler/stats.go
  - internal/server/routes.go
autonomous: true
must_haves:
  truths:
    - "System provides real-time campaign performance aggregated by date/hour/geo/etc."
    - "Stats include hits, clicks, conversions, revenue, CR, and EPC."
    - "Reporting API supports filters by campaign, date range, and group_by."
  artifacts:
    - "internal/analytics/service.go"
    - "internal/admin/handler/stats.go"
---

# Plan 5.3: Analytics Reporting Service & Stats API

<objective>
Implement the analytics engine for SkyPlix.
Generate real-time reports directly from ClickHouse `clicks` and `conversions` tables.
The reporting service will build dynamic grouping queries for campaign performance analysis.

Output:
- Reporting query builder for high-performance ClickHouse stats.
- Stats API endpoints (Campaign, Stream, Geo, Content, Time groupings).
- Derived metrics (CR, EPC, ROI) calculation.
</objective>

<context>
Load for context:
- internal/queue/writer.go (for CH connection pattern)
- internal/admin/handler/campaigns.go (for response DTO pattern)
- .gsd/SPEC.md
</context>

<tasks>

<task type="auto">
  <name>Implement Reporting Service</name>
  <files>internal/analytics/service.go, internal/analytics/models.go</files>
  <action>
    Create AnalyticsService with GetCampaignStats, GetGeoStats, GetTimeStats methods.
    Query pattern: Join `clicks` and `conversions` by campaign_id/click_token or use two separate queries and merge in Go.
    Recommended: Use subqueries or JOIN in ClickHouse for CR/EPC.
    AVOID: Complex ORM-based queries; stick to Raw SQL for ClickHouse precision.
  </action>
  <verify>go build ./internal/analytics/...</verify>
  <done>Reporting engine can produce aggregated JSON stats.</done>
</task>

<task type="auto">
  <name>Implement Stats API Endpoints</name>
  <files>internal/admin/handler/stats.go, internal/server/routes.go</files>
  <action>
    Create HandleCampaignStats, HandleGeoStats, HandleTimeStats handlers.
    Route: `/api/v1/stats/campaigns`, `/api/v1/stats/geo`, `/api/v1/stats/time`.
    Support query params: `from`, `to`, `campaign_id`.
    Wrap stats in a standard JSON response with total summary.
  </action>
  <verify>curl "http://localhost:8080/api/v1/stats/campaigns?from=2024-04-01&to=2024-04-03"</verify>
  <done>Admin API can return real-time performance reports.</done>
</task>

</tasks>

<verification>
After all tasks, verify:
- [ ] Stats match ClickHouse manually tallied counts.
- [ ] API performance is < 100ms for standard date ranges.
</verification>

<success_criteria>
- [ ] Real-time reporting on hits, clicks, and conversions works.
- [ ] EPC and CR are calculated correctly.
</success_criteria>
