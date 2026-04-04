<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-03 -->

# .gsd/phases/2

## Phase 2: Campaign Engine — Streams, Filters, Rotators, Entity Binding
**Status**: ✅ VERIFIED PASS (2026-04-02)

## Key Deliverables
| # | Deliverable |
|---|-------------|
| 1 | 3-tier stream selection (FORCED → REGULAR → DEFAULT) |
| 2 | Stream filter matching (27 filter types) |
| 3 | Position-based AND weight-based rotation |
| 4 | Offer/landing weighted rotation |
| 5 | Affiliate network resolution |
| 6 | Entity binding (Valkey + cookies) |
| 7 | LP token system for L1 → L2 linking |
| 8 | Case-insensitive filter normalization |
| 9 | Cache fallbacks in cache.go |
| 10 | Level 2 landing-to-offer redirect |
| 11 | Integration test suite (8/8 cases) |

## Files
- `1-PLAN.md` through `6-PLAN.md` — Execution plans
- `1-SUMMARY.md`, `2-SUMMARY.md` — Session summaries
- `VERIFICATION.md` — 11/11 must-haves verified

## For AI Agents
Phase 2 implements the full routing engine. Key insight: all stream selection goes through `rotator.Rotator` which uses a `Weightable` interface. Entity binding stores in Valkey with cookie fallback for returning visitors.

<!-- MANUAL: -->
