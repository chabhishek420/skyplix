---
phase: 6
plan: 1
subsystem: admin-ui
tags: [auth, jwt, dashboard]
provides: [jwt-login-ui, logs-pages]
tech-stack: [react, vite, go]
key-files:
  - admin-ui/src/components/auth/login-guard.tsx
  - admin-ui/src/pages/logs/clicks.tsx
  - admin-ui/src/pages/logs/conversions.tsx
  - internal/admin/handler/reports.go
metrics:
  completed_date: "2026-04-06"
---

# Phase 6 Plan 1: Admin Dashboard — JWT Login Flow Summary

## Summary
- Updated the Admin UI login flow to use the backend JWT login endpoint (`POST /api/v1/auth/login`) and persist `auth_token` in localStorage.
- Added missing Clicks/Conversions log pages to match existing sidebar/routes and call the existing backend endpoints.
- Fixed a compile-breaking bug in the reports query parsing logic.

## Verification Result
- `go test ./...` passes.
- `npm -C admin-ui run build` passes.
