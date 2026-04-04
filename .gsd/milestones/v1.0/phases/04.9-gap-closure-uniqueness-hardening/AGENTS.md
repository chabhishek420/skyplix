<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-03 -->

# .gsd/phases/4.9

## Phase 4.9: Gap Closure & Infrastructure Hardening
**Status**: ⬜ In Progress (2026-04-03)

## Remaining Gaps (from VERIFICATION.md)
| Gap | Status |
|-----|--------|
| Admin API Hardening (cloning, bulk-upsert) | ❌ Missing |
| TODO.md synchronization | ❌ Out of sync |
| `strings.Title` → `cases.Title` | ✅ Fixed |

## Next Tasks
1. Implement `Clone()` in CampaignRepository
2. Implement `Clone()` in StreamRepository
3. Implement `BulkUpsert()` in SettingsRepository
4. Add `POST /admin/campaigns/{id}/clone` endpoint
5. Add `PUT /admin/settings/bulk` endpoint
6. Sync TODO.md

## Files
- `1-PLAN.md`, `2-PLAN.md`, `3-PLAN.md` — Execution plans
- `VERIFICATION.md` — Gap audit

## For AI Agents
Phase 4.9 addresses the final hardening gaps before Phase 5. Run `/execute 4.9` to continue.

<!-- MANUAL: -->
