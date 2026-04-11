---
phase: 08-layer-parity
plan: PLAN
subsystem: pipeline
tags: [tracking, referrer, bot-filtering, actions]
requires:
  - phase: 03-actions-and-landers
    provides: redirect and action execution pipeline
  - phase: 04-bot-detection
    provides: bot detection signals on RawClick
provides:
  - GET /click route parity with existing L1 click path
  - aff_sub2 generation and outbound URL injection
  - canonical action alias resolution for blank_referrer-style names
  - global bad-traffic fallback action with per-stream override support
affects: [pipeline, action-engine, server-config]
tech-stack:
  added: []
  patterns: [canonical action key normalization, stream-level bad-traffic policy]
key-files:
  created:
    - internal/action/action_test.go
    - internal/pipeline/stage/9_choose_stream_test.go
  modified:
    - internal/action/action.go
    - internal/pipeline/stage/13_generate_token.go
    - internal/pipeline/stage/13_generate_token_test.go
    - internal/pipeline/stage/9_choose_stream.go
    - internal/config/config.go
    - internal/server/server.go
    - config.yaml
key-decisions:
  - "Normalize action keys so snake_case and kebab-case action names resolve reliably."
  - "Enforce bad-traffic action in stream selection to preserve per-stream override capability."
patterns-established:
  - "Use action_payload.bad_traffic_action for stream-specific bot handling override."
  - "Inject _token, _subid, and aff_sub2 together in stage 13 for outbound parity."
requirements-completed: [FEAT-05, SEC-02]
duration: 47m
completed: 2026-04-10
---

# Phase 8: Layer Parity Summary

**Phase 8 parity hardening now guarantees tracked outbound params, alias-safe action lookup, and bot-safe global routing defaults without breaking stream-level overrides.**

## Performance

- **Duration:** 47 min
- **Completed:** 2026-04-10
- **Tasks:** 5/5 completed
- **Files modified:** 10

## Accomplishments
- Verified and kept the `/click` endpoint wired to the standard L1 click pipeline.
- Completed outbound tracking parity by injecting `aff_sub2` alongside `_token` and `_subid`.
- Added canonical action key normalization so aliases like `blank_referrer` resolve to `BlankReferrer`.
- Enforced a global bad-traffic fallback action (`Status404` by default), while allowing stream-level override via `bad_traffic_action`.
- Added unit coverage for the new action alias and bad-traffic policy behavior.

## Validation

- `go test ./internal/action ./internal/pipeline/stage` ✅
- `go test ./test/unit/...` ✅
- `go build ./...` ✅

## Deviations from Plan

- No structural scope expansion; implementation focused on parity gaps identified in the current code.
- Atomic task commits were not created because this worktree contains a large pre-existing in-progress change set; verification was completed directly in-tree.

## Next Phase Readiness

- Phase 8 implementation criteria are satisfied and documented.
- Residual non-blocking risk: dual phase path ambiguity has been cleaned up; `08-layer-parity` is now canonical.

