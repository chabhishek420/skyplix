---
phase: 7
plan: 1
subsystem: postback
tags: [security, hmac]
tech-stack: [go]
key-files:
  - internal/admin/handler/postback.go
  - internal/admin/handler/postback_test.go
metrics:
  completed_date: "2026-04-06"
---

# Phase 7 Plan 1: Postback Signature Hardening Summary

## Summary
- Postback signature validation now fails closed when `sig` is provided but the salt is missing or cannot be fetched.
- Added tests to ensure missing-salt and salt-lookup-error cases return 500 instead of silently bypassing signature checks.

## Verification Result
- `go test ./...` passes.
