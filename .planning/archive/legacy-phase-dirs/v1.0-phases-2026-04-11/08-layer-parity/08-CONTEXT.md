# Phase 8: Layer Parity & Tracking - Context

**Gathered:** 2026-04-08
**Status:** ✅ Implemented - All 4 gaps closed

<domain>
## Phase Boundary

Verify 5-layer click pipeline implementation against Keitaro reference. Confirm each layer is implemented and identify gaps. The phase scope from ROADMAP.md:

- `_token` and `_subid` auto-injected into outgoing affiliate network URLs
- `allow_change_referrer` config enables `?referrer=` URL override
- Sticky rotation binding keeps same visitor on same landing/offer
- All 5 layers verified against Keitaro reference

This is a verification phase that MAY include implementation fixes for identified gaps.

</domain>

<decisions>
## Implementation Decisions

### Gap Prioritization
- **Address all gaps now** — Fix /click endpoint, add aff_sub2 support, add spoof referrer, force safe page globally

### GET /click Endpoint
- **Standard click flow** — Process click, apply layers, redirect to landing (same as /{alias})

### aff_sub2 Behavior
- **Always generate new** — Generate fresh aff_sub2 on every click

### Spoof Referrer
- **Inline support only** — Keep as part of existing traffic-source layer (per reference pattern)
  - Reference: `blank_referrer` action type in reference/legacy-nextjs
  - No dedicated spoof module needed - use referrer macro + blank_referrer action

### Bad Traffic → Safe Page/404
- **Force globally** — Default to safe page/404 for flagged traffic

### Verification Criteria
- **Unit tests pass** — All layer unit tests pass as baseline verification

### Testing Strategy
- **Compare to reference** — Test outputs match reference Keitaro flow

</decisions>

<specifics>
## Specific Ideas

Reference patterns to follow:
- Token injection: `reference/Keitaro_source_php/application/Traffic/Pipeline/Stage/GenerateTokenStage.php:46-55`
- Bot detection: `reference/Keitaro_source_php/application/Traffic/Pipeline/Stage/BuildRawClickStage.php:113-149`
- Blank referrer: `reference/legacy-nextjs/src/lib/tds/actions/predefined/blank-referrer.ts`

No specific requirements — open to standard approaches matching existing codebase patterns.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 08-layer-parity*
*Context gathered: 2026-04-08*