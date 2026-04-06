---
phase: 7
plan: 1
wave: 1
autonomous: true
---

# Phase 7 Plan 1: Postback Signature Hardening

<objective>
Fail closed when a postback signature (`sig`) is provided but cannot be validated (missing salt or lookup error).
</objective>

## Context
- Postbacks can include an optional `sig` (HMAC-SHA256) intended to protect attribution.
- Current behavior ignores salt lookup errors and treats missing salt as "skip validation", which can silently weaken security.

## Tasks
1. In `internal/admin/handler/postback.go`, when `sig` is present:
   - If salt lookup fails → return `500 error: postback_salt_lookup_failed`.
   - If salt is empty → return `500 error: postback_salt_missing`.
   - If signature mismatch → return `401 error: invalid_signature`.
2. Extend `internal/admin/handler/postback_test.go` to cover salt-missing and salt-lookup-error cases.

## Success Criteria
- Signed postbacks cannot bypass signature validation due to missing/failed salt lookup.
- `go test ./...` passes.
