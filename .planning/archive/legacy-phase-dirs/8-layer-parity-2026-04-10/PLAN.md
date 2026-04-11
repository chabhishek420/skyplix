# Phase 8: Layer Parity & Tracking

## Goal

Close the remaining gaps between zai-tds and Keitaro's 5-layer architecture. Implement URL parameter injection, referrer spoofing configuration, and sticky rotation binding.

## Dependencies

- Phase 4: Advanced Bot Detection & Cloaking (current)
- Phase 3: Actions & Landers

## Requirements

- FEAT-05: URL parameter injection for click tracking
- FEAT-06: Macro expansion (already done)
- SEC-02: Referrer spoofing configuration

## Success Criteria

1. `_token` and `_subid` are auto-injected into outgoing affiliate network URLs
2. `allow_change_referrer` config option enables `?referrer=` URL override
3. Sticky rotation binding keeps same visitor on same landing/offer
4. All 5 layers verified against Keitaro reference

## Implementation Tasks

### High Priority (Layer 3 - Resolver)

- [ ] Add `_token` → outgoing URL injection in action execution stage
- [ ] Add `_subid` → outgoing URL injection
- [ ] Support custom `aff_sub` parameters from campaign settings

### Medium Priority (Layer 1 - Traffic Source)

- [ ] Add `allow_change_referrer` config option to config.yaml
- [ ] Implement `?referrer=` URL parameter override in BuildRawClickStage
- [ ] Extract source domain from referrer automatically

### Low Priority (Layer 4 - Tracker)

- [ ] Implement sticky rotation binding in Redis
- [ ] Bind same visitor to same landing/offer using cookies

## Verification

1. Build: `go build ./...`
2. Tests: `go test ./test/unit/...`
3. Manual test:
   - Send click with `?referrer=https://google.com`
   - Verify `_token` and `_subid` appear in outgoing redirect URL
   - Verify referrer spoofing works when enabled

## Notes

- Keitaro reference: `GenerateTokenStage.php` lines 46-53
- Keitaro reference: `BuildRawClickStage.php` lines 114-126
