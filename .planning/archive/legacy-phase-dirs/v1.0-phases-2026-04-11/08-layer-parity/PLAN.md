# Phase 8: Layer Parity & Tracking - Implementation Plan

## Goal

Verify 5-layer click pipeline implementation against Keitaro reference. Close gaps: add GET /click endpoint, aff_sub2 generation, inline referrer spoofing, and force safe page for bad traffic.

## Dependencies

- Phase 4: Advanced Bot Detection & Cloaking
- Phase 3: Actions & Landers

## Requirements

- FEAT-05: URL parameter injection for click tracking
- SEC-02: Referrer spoofing configuration

## Success Criteria

1. GET /click endpoint processes standard click flow
2. aff_sub2=NEW_UNIQUE_ID generated on every click
3. Inline referrer spoofing via blank_referrer action
4. Bad traffic → safe page/404 globally enforced
5. All 5 layers verified against reference
6. Unit tests pass for modified components

---

## Implementation Tasks

### Task 1: Add GET /click Endpoint

**Priority:** P0 (Required)
**Component:** Server routes

- [ ] Add GET /click route handler in internal/server/routes.go
- [ ] Reuse existing click pipeline flow (same as /{alias})
- [ ] Return standard redirect response

**Verification:**
- `curl http://localhost:8080/click?campaign=test&subid=abc` returns redirect

### Task 2: Implement aff_sub2 Generation

**Priority:** P0 (Required)
**Component:** Token generation

- [ ] Modify token generation to include aff_sub2 parameter
- [ ] Generate unique aff_sub2 on every click
- [ ] Inject into outgoing URLs

**Verification:**
- Outgoing URL contains `aff_sub2=NEW_UNIQUE_ID`

### Task 3: Inline Referrer Spoofing (No New Module)

**Priority:** P1 (Recommended by reference)
**Component:** Traffic source layer

- [ ] Use existing referrer macro (already exists)
- [ ] Add blank_referrer action type if not present
- [ ] No new standalone module needed

**Reference:** reference/legacy-nextjs/src/lib/tds/actions/predefined/blank-referrer.ts

### Task 4: Force Safe Page for Bad Traffic

**Priority:** P1 (Required)
**Component:** Bot detection / Cloak decision

- [ ] Add global config for bad traffic handling
- [ ] Default to safe page/404 for flagged traffic
- [ ] Override per-stream still allowed

**Verification:**
- Flagged bot traffic receives safe page, not redirect

### Task 5: Layer Verification Tests

**Priority:** P2 (Verification)
**Component:** Test suite

- [ ] Run existing unit tests
- [ ] Verify all 5 layers execute correctly
- [ ] Compare output to reference flow

---

## Parallel Execution Opportunities

Independent tasks that can run parallel:
- Task 1 (GET /click) 
- Task 2 (aff_sub2)
- Task 3 (referrer spoof)

These share the token/injection pipeline, so should be sequenced:
1. First: Token generation changes (Task 2)
2. Then: Endpoint that uses it (Task 1)
3. Referrer spoof is independent (Task 3)
4. Safe page changes (Task 4) - depends on existing bot detection

---

## Technical Notes

- Token generation: internal/pipeline/stage/13_generate_token.go
- Bot detection: internal/filter/detection.go, internal/pipeline/stage/cloak_decision.go
- Referrer macro: reference/legacy-nextjs/src/lib/tds/macros/predefined/referrer.ts
- Blank referrer: reference/legacy-nextjs/src/lib/tds/actions/predefined/blank-referrer.ts