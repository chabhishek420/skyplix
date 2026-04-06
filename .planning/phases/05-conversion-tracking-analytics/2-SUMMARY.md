---
phase: 5
plan: 2
subsystem: analytics
tags: [reporting, clickhouse, query-builder]
depends_on: ["5.1"]
provides: [analytics-service]
tech-stack: [go, clickhouse]
key-files:
  - internal/analytics/models.go
  - internal/analytics/query_builder.go
  - internal/analytics/service.go
  - test/unit/analytics/query_builder_test.go
  - test/unit/analytics/service_test.go
one_liner: "Implemented the analytics reporting engine (query builder + Go-side merge + derived metrics)."
metrics:
  completed_date: "2026-04-03"
---

# Phase 5 Plan 2: Analytics Reporting Service Summary

## Summary
Implemented the `internal/analytics` reporting engine that queries ClickHouse stats tables/materialized views and returns merged report rows with derived metrics.

## Key Changes
- Added report DTOs (`ReportQuery`, `ReportRow`, `ReportResponse`) plus derived metric calculation utilities.
- Implemented a whitelist-based SQL query builder for group-by dimensions and filters to prevent injection.
- Queried click stats and conversion stats independently, then merged in Go by grouping key (no ClickHouse JOIN).
- Chose hourly vs daily stats tables based on date range to keep query latency stable.

## Verification
- `go test ./...`
- Unit tests for the query builder and analytics service.

