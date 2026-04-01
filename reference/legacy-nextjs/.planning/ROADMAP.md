# Roadmap: zai-yt-keitaro

## Overview

This roadmap reflects the brownfield reality of the repo after reconstructing the old `.gsd` execution history into `.planning/`. Phases 1-7 are already complete from the legacy parity push, Phase 7.1 is an inserted backend parity closure phase derived from direct source audit, and Phase 8 remains the UI build once the backend gaps are closed.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (7.1): Urgent insertion before the next integer phase

- [x] **Phase 1: Fix Action System & Broken Imports** - Stabilize the backend execution baseline.
- [x] **Phase 2: Complete Macro Coverage** - Expand macro support for traffic-routing parity.
- [x] **Phase 3: Pipeline Delegation — Wire Live Endpoints** - Route live traffic endpoints through the shared engine.
- [x] **Phase 4: GeoIP, Bot Detection & Service Wiring** - Wire the enrichment and bot-detection subsystems into runtime paths.
- [x] **Phase 5: Behavioral Verification Against PHP Reference** - Check implemented engine behavior against the reference source.
- [x] **Phase 6: Model & Security Polish** - Harden auth, validation, and supporting config.
- [x] **Phase 7: True Admin API Parity** - Ship the major admin API parity pass.
- [ ] **Phase 7.1: Backend Parity Closure (INSERTED)** - Close the remaining high-value backend parity gaps found by direct source audit.
- [ ] **Phase 8: Build the UI Reality** - Build the real admin/dashboard UI on top of the stabilized backend.

## Phase Details

### Phase 1: Fix Action System & Broken Imports
**Goal**: Restore basic backend execution integrity so the traffic engine can run without broken action wiring or missing imports.
**Depends on**: Nothing (first phase)
**Requirements**: [CORE-01]
**Success Criteria** (what must be TRUE):
  1. Core action execution paths are no longer blocked by import/runtime failures.
  2. The TDS backend can proceed into later parity work from a stable baseline.
**Plans**: Historical completion in legacy `.gsd`

### Phase 2: Complete Macro Coverage
**Goal**: Expand macro behavior so the Next.js port can substitute the key Keitaro-style tracking values used by live routing.
**Depends on**: Phase 1
**Requirements**: [MACR-01]
**Success Criteria** (what must be TRUE):
  1. The macro surface is broad enough for realistic traffic-routing use.
  2. Macro handling no longer blocks live endpoint parity.
**Plans**: Historical completion in legacy `.gsd`

### Phase 3: Pipeline Delegation — Wire Live Endpoints
**Goal**: Make the real public traffic endpoints delegate into the shared traffic pipeline instead of ad hoc route logic.
**Depends on**: Phase 2
**Requirements**: [PIPE-01]
**Success Criteria** (what must be TRUE):
  1. Live traffic endpoints call the shared pipeline runner.
  2. Endpoint behavior is centralized enough to support parity verification.
**Plans**: Historical completion in legacy `.gsd`

### Phase 4: GeoIP, Bot Detection & Service Wiring
**Goal**: Connect the enrichment and cloaking subsystems to runtime traffic handling.
**Depends on**: Phase 3
**Requirements**: [GEO-01]
**Success Criteria** (what must be TRUE):
  1. GeoIP, bot detection, and related services influence live traffic outcomes.
  2. Safe/bot-handling flows exist in the runtime path.
**Plans**: Historical completion in legacy `.gsd`

### Phase 5: Behavioral Verification Against PHP Reference
**Goal**: Verify the implemented traffic-engine behavior against the original Keitaro PHP source.
**Depends on**: Phase 4
**Requirements**: [VERI-01]
**Success Criteria** (what must be TRUE):
  1. Implemented engine behavior has been checked directly against the reference source.
  2. Major engine mismatches discovered during verification are resolved or known.
**Plans**: Historical completion in legacy `.gsd`

### Phase 6: Model & Security Polish
**Goal**: Harden admin auth, standardize route protection, and improve mutation validation and metadata.
**Depends on**: Phase 5
**Requirements**: [SECU-01, SECU-02]
**Success Criteria** (what must be TRUE):
  1. Insecure admin auth paths are removed.
  2. Core admin mutations validate input consistently.
  3. Project metadata and configuration reflect the hardened backend.
**Plans**: 3 plans

Plans:
- [x] 06-01: Core Auth & Config Hardening
- [x] 06-02: Admin API Coverage & Validation (Part 1)
- [x] 06-03: Specialized Validation & Final Handoff

### Phase 7: True Admin API Parity
**Goal**: Implement the major missing admin API resources needed for the next parity milestone.
**Depends on**: Phase 6
**Requirements**: [API-01, API-02]
**Success Criteria** (what must be TRUE):
  1. Additional Keitaro-aligned admin resources exist in the Next.js API surface.
  2. Stream child resources and integration/template endpoints are available.
  3. The backend is ready for a fresh parity audit before UI work.
**Plans**: 3 plans

Plans:
- [x] 07-01: True Admin API Parity - Groups and Labels
- [x] 07-02: True Admin API Parity - Triggers and API Templates
- [x] 07-03: True Admin API Parity - Stream Entities and Integrations

### Phase 7.1: Backend Parity Closure (INSERTED)
**Goal**: Close the highest-value Keitaro backend parity gaps still missing in the Next.js port, prioritizing traffic dispatchers and partial controller contracts over peripheral platform modules.
**Depends on**: Phase 7
**Requirements**: [PAR-01, PAR-02, PAR-03]
**Success Criteria** (what must be TRUE):
  1. High-value missing traffic/admin parity gaps are implemented or explicitly deferred with rationale.
  2. Same-name parity endpoints behave materially closer to reference contracts, not just generic CRUD.
  3. A fresh source-level backend audit finds no unaccounted critical gap that should block the UI phase.
**Plans**: TBD

### Phase 8: Build the UI Reality
**Goal**: Build the real admin/dashboard UI on top of the stabilized backend surface so operators can use the system without the original PHP app.
**Depends on**: Phase 7.1
**Requirements**: [UI-01, UI-02]
**Success Criteria** (what must be TRUE):
  1. The UI exposes core operator workflows against the live backend.
  2. The UI reflects the stabilized backend contracts rather than paper assumptions.
  3. The product can be operated as a real Next.js application, not just as a backend port.
**Plans**: TBD

## Progress

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Fix Action System & Broken Imports | Historical | Complete | Legacy |
| 2. Complete Macro Coverage | Historical | Complete | Legacy |
| 3. Pipeline Delegation — Wire Live Endpoints | Historical | Complete | Legacy |
| 4. GeoIP, Bot Detection & Service Wiring | Historical | Complete | Legacy |
| 5. Behavioral Verification Against PHP Reference | Historical | Complete | Legacy |
| 6. Model & Security Polish | 3/3 | Complete | Legacy |
| 7. True Admin API Parity | 3/3 | Complete | Legacy |
| 7.1 Backend Parity Closure | 0/TBD | In progress | - |
| 8. Build the UI Reality | 0/TBD | Not started | - |
