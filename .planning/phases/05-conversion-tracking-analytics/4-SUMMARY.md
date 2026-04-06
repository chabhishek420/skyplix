---
phase: 5
plan: 4
subsystem: macro
tags: [postback, macros, admin-api]
provides: [postback-url-templates]
tech-stack: [go]
key-files:
  - internal/macro/postback.go
  - internal/admin/handler/networks.go
  - test/unit/macro/postback_test.go
one_liner: "Added postback URL template generation + postback macro expansion with tests."
metrics:
  completed_date: "2026-04-03"
---

# Phase 5 Plan 4: Postback URL Template Generation Summary

## Summary
Added postback-specific macro support and an admin API endpoint to generate operator-ready postback templates for affiliate networks.

## Key Changes
- Implemented `internal/macro/postback.go` with `GeneratePostbackURL` + `ReplacePostback` and an explicit supported macro set.
- Updated `internal/admin/handler/networks.go` to expose a postback-template generation flow for network configuration.
- Added table-driven macro tests covering aliases, missing values, and URL encoding.

## Verification
- `go test ./...`

