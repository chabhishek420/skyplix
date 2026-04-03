---
phase: 5
plan: 3
subsystem: analytics
tags: [reporting, api, admin]
requires: ["5.2"]
provides: ["reporting-api"]
tech-stack: [go, chi, clickhouse]
key-files:
  - internal/admin/handler/reports.go
  - internal/server/server.go
  - internal/server/routes.go
decisions:
  - Separate ReportsHandler struct to maintain clean dependency separation and avoid bloating the main admin Handler.
  - Reused chReader (ClickHouse read-optimized connection) to avoid connection overhead.
  - Flexible date preset resolution (today, yesterday, etc.) handled at the handler level to keep the analytics service focused on absolute time ranges.
metrics:
  duration: "15m"
  completed_date: "2026-04-03"
---

# Phase 5 Plan 3: Reporting API Endpoint Summary

## Summary
Implemented the HTTP reporting layer for the TDS, providing a flexible and secure API endpoint for fetching performance data. The implementation includes a dedicated `ReportsHandler` with robust query parameter parsing, validation, and integration with the ClickHouse-backed analytics service.

## Key Changes
- **Reports Handler**: Created `internal/admin/handler/reports.go` which handles `GET /api/v1/reports`.
  - Parses and validates dimensions, filters (with UUID validation), and sorting criteria.
  - Implements date range resolution from presets (today, yesterday, last_7d, last_30d, this_month).
  - Enforces pagination limits (max 1000 rows).
- **Server Wiring**: Updated `internal/server/server.go` to construct the `ReportsHandler` and inject the `analytics.Service`.
- **Route Registration**: Updated `internal/server/routes.go` to expose the `/reports` endpoint under the API key auth-protected group.

## Deviations from Plan
None - plan executed exactly as written.

## Self-Check: PASSED
- [x] `go build ./...` passes.
- [x] `go vet ./...` reports no issues.
- [x] Reports handler validates all query parameters.
- [x] Date presets resolve to correct UTC ranges.
- [x] `/api/v1/reports` registered under admin auth.
- [x] Consistent JSON error formatting.
