# ROADMAP.md

> **Current Milestone**: v1.0 — Keitaro Parity
> **Goal**: Reach full behavioral compatibility with the original Keitaro PHP TDS for the traffic engine and admin APIs. Close the last ~2% gaps so the system is a reliable modern replacement.

---

## Must-Haves

- [ ] Clean, compiling action system with all 19 action types and `LocalFileAction`
- [ ] 100% macro coverage — remove duplicates, add missing macro fields
- [ ] Full pipeline delegation in all live traffic endpoints (`/api/click`, `/api/click/json`, `/api/lp/offer`)
- [ ] Final GeoIP, bot detection, and service wiring into the pipeline
- [ ] Behavioral verification against PHP reference flows
- [ ] Model & security polish (auth hardening, env docs, schema validation)

---

## Phases

---

### Phase 1: Fix Action System & Broken Imports
**Status**: ✅ Complete
**Objective**: Eliminate all broken imports in the action layer and complete missing action implementations so the system compiles clean.

**Tasks**:
- [x] Implement `src/lib/tds/actions/predefined/local-file.ts` (mirrors `LocalFile.php`)
- [x] Fix the broken import in `src/lib/tds/actions/repository.ts`
- [x] Implement `JsForIframe` and `JsForScript` as `src/lib/tds/actions/predefined/js-for-iframe.ts`
- [x] Register `js_for_iframe` and `js_for_script` in `ActionRepository`
- [x] Extend `ActionType` union in `pipeline/types.ts` to include all 20 action types
- [x] Sync `REDIRECT_TYPES` and `parseActionType` in `actions/types.ts`
- [x] Install `@types/node` for `fs`/`path`/`process` in `local-file.ts`
- [x] Verified `src/app/api/click/json/route.ts` loads without import errors
- [x] `bun run lint` passes with zero action-layer errors

**Reference**: `reference/Keitaro_source_php/application/Traffic/Actions/Predefined/LocalFile.php`


---

### Phase 2: Complete Macro Coverage
**Status**: ✅ Complete
**Objective**: Bring macro count to 100% parity with the PHP reference, removing duplicates and adding all missing fields from `Traffic/Macros/Predefined/`.

**Tasks**:
- [x] Audit `src/lib/tds/macros/predefined/` vs `reference/application/Traffic/Macros/Predefined/` — 33 PHP files vs 26 TS files, gap of 7 net new implementations
- [x] Create `network.ts` — ConnectionType, Operator, XRequestedWith, CurrentDomain, TrafficSourceName, Debug (6 macros)
- [x] Create `conversion-ext.ts` — OriginalStatus, ConversionCost, ConversionProfit, ConversionRevenue, ConversionTime, AnyClick, AnyConversion (7 macros)
- [x] Register all 13 new macros + aliases in `MacroRegistry`
- [x] Fix type errors (AnyClick/AnyConversion `unknown` double-cast)
- [x] `bun run lint` passes; tsc macro layer clean
- [x] 84 macro registrations covering all 33 PHP predefined macro classes

**Reference**: `reference/Keitaro_source_php/application/Traffic/Macros/Predefined/`


---

### Phase 3: Pipeline Delegation — Wire Live Endpoints
**Status**: ✅ Complete
**Objective**: Replace bespoke inline click-processing logic with full Pipeline engine delegation in all three live traffic endpoints.

**Tasks**:
- [x] Create `src/lib/tds/pipeline/runner.ts` — shared adapter: `runPipeline()`, `runSecondLevelPipeline()`, `pipelinePayloadToResponse()`, `pipelinePayloadToJsonResponse()`
- [x] Refactor `src/app/api/click/route.ts` → 2-line delegation to `runPipeline()` + `pipelinePayloadToResponse()`
- [x] Refactor `src/app/api/click/json/route.ts` → 5-line delegation (eliminates ~230 lines of duplicated inline logic)
- [x] Refactor `src/app/api/lp/offer/route.ts` GET → `runSecondLevelPipeline()` (13-stage LP→Offer flow); POST remains lightweight direct tracker
- [x] tsc pipeline layer: zero errors; `bun run lint`: PASS
- [x] Three parallel traffic-processing implementations reduced to one unified pipeline

**Reference**: `reference/Keitaro_source_php/application/Traffic/Pipeline/Pipeline.php`


---

### Phase 4: GeoIP, Bot Detection & Service Wiring
**Status**: ✅ Complete
**Objective**: Fully wire the existing GeoIP, bot detection, and service layer modules into the live pipeline so every request gets proper geo-enrichment and bot checking.

**Tasks**:
- [x] Verify `src/lib/tds/services/geo-db-service.ts` can load a MaxMind / IP2Location database at startup
- [x] Confirm `src/lib/tds/services/ip-info-service.ts` is called for every click in the pipeline
- [x] Wire `src/lib/tds/bot-detection.ts` as the primary bot-check service inside `check-bot.ts` pipeline stage
- [x] Create the missing `/app/safe/` route directory with placeholder pages: `debug`, `bot`, `security`, `error`, `verify` (resolves 404 targets in `bot-detection.ts`)
- [x] Wire `src/lib/tds/services/entity-binding-service.ts` into `entity-binding` stage (fixes the `// TODO` in `rotator.ts`)
- [x] Wire `src/lib/tds/services/lp-token-service.ts` into `generate-token.ts` pipeline stage
- [x] Confirm cookies service (`cookies-service.ts`) is used by `set-cookie.ts` stage
- [x] Run a live click through all stages and verify geo/bot/token fields are populated on the `Click` record
- [x] Expanded `PipelinePayload` interface to resolve 70+ type errors across all 25 stages


**Reference**: `reference/Keitaro_source_php/application/Traffic/GeoDb/` and `reference/Keitaro_source_php/application/Component/BotDetection/`

---

### Phase 5: Behavioral Verification & Polish
**Status**: ✅ Complete
**Objective**: Empirically verify that our TypeScript pipeline produces behaviorally equivalent outputs to the PHP reference for the key traffic flows.

**Tasks**:
- [x] Implement `verify-pipeline.ts` for automated behavioral testing
- [x] Verify macro parity (click_id, subid, custom parameters)
- [x] Reach Parity on Filter logic (Geo, Uniqueness, Limits)
- [x] Reach Parity on Action execution (Redirects, Meta, Iframe)
- [x] Document compliance in `docs/verification-report.md`
- [x] Finalize `PipelinePayload` method implementation
- [x] Correct import paths for `rotator` and `db` across stages
- [x] Verify full LP-to-Offer second-level pipeline delegation

**Reference**: `reference/Keitaro_source_php/application/Traffic/Pipeline/` and `src/lib/tds/tests/verify-pipeline.ts`

---

### Phase 6: Model & Security Polish
**Status**: ✅ Complete
**Objective**: Harden the auth, API validation, and environment configuration layers to production-readiness. Clean up known model and operational concerns from `CONCERNS.md`.

**Tasks**:
- [x] Add `ADMIN_API_KEY` to `.env.example` with a descriptive comment
- [x] Remove `?api_key=` query-parameter auth channel from `admin-auth.ts` (prevents log leakage)
- [x] Store session token hash (not raw key) in `admin_session` cookie
- [x] Standardize all admin routes to use `checkAuth()` — fixed `src/app/api/admin/stats/route.ts`
- [x] Add Zod schema validation for core admin mutation routes (campaigns, streams, offers)
- [x] Set Prisma log level to `warn` + `error` only (removed `query` from `src/lib/db.ts`)
- [x] Document bot-rule regex safety guidance in `docs/bot-rules.md`
- [x] Update `AGENTS.md` API endpoint count to match live route count (24)
- [x] Update `next.config.ts`: disabled `typescript.ignoreBuildErrors` for production safety

---

### Phase 7: True Admin API Parity
**Status**: ⬜ Not Started
**Objective**: Map and build the remaining structural endpoints from Keitaro's ~40 missing controllers (specifically Groups, Labels, Logs, and Reports).
**Depends on**: Phase 6

**Tasks**:
- [ ] TBD (run `/plan 7` to create)

**Verification**:
- TBD

---

### Phase 8: Build the UI Reality
**Status**: ⬜ Not Started
**Objective**: Write the React/Next.js UI components using shadcn/ui to consume the APIs we built.
**Depends on**: Phase 7

**Tasks**:
- [ ] TBD (run `/plan 8` to create)

**Verification**:
- TBD

---

## Success Criteria

| Criterion | Target |
|-----------|--------|
| Action system compiles | Zero broken imports |
| Macro coverage | ≥ 48 macros (100% of PHP reference) |
| Pipeline wiring | All 3 traffic endpoints use `Pipeline.start()` |
| GeoIP/bot wiring | IP and bot fields populated on every Click record |
| Verification | All 5 reference flows verified and documented |
| Auth | Raw key not stored in cookie; query-param auth removed |
| Lint | `bun run lint` passes |

---

*Created: 2026-04-01*
*Reference: STRUCTURE_COMPARISON.md, .planning/codebase/CONCERNS.md, .planning/codebase/ARCHITECTURE.md*
