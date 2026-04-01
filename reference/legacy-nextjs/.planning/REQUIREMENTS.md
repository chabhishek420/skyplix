# Requirements: zai-yt-keitaro

**Defined:** 2026-04-01
**Core Value:** Traffic must route correctly and operators must have reliable, secure backend controls for that routing engine.

## v1 Requirements

### Foundation

- [x] **CORE-01**: The action system and import graph are stable enough for the TDS backend to execute without broken core dependencies.

### Macros

- [x] **MACR-01**: Macro coverage is broad enough to support real Keitaro-style traffic redirection behavior.

### Live Traffic Pipeline

- [x] **PIPE-01**: Live `/api/click` and related traffic endpoints delegate into the shared TDS pipeline rather than duplicate routing logic.

### Geo, Bot, and Service Wiring

- [x] **GEO-01**: GeoIP, bot detection, and related traffic services are wired into the live engine paths.

### Behavioral Verification

- [x] **VERI-01**: The live Next.js traffic engine has been behaviorally checked against the Keitaro PHP reference for the implemented surface.

### Security and Validation

- [x] **SECU-01**: Admin authentication removes insecure query auth and uses hardened cookie/header checks.
- [x] **SECU-02**: Core mutation routes have schema validation and clearer safety boundaries.

### Admin API Parity Baseline

- [x] **API-01**: Core admin CRUD/reporting routes exist for campaigns, streams, offers, landings, domains, affiliate networks, traffic sources, clicks, conversions, reports, settings, and users.
- [x] **API-02**: Additional parity routes exist for groups, labels, triggers, stream actions/filters, and integration/template resources.

### Backend Parity Closure

- [ ] **PAR-01**: Remaining high-value Keitaro backend controllers are either implemented, intentionally mapped, or explicitly deferred with rationale.
- [ ] **PAR-02**: Missing traffic dispatcher/entry contracts needed for practical backend parity are implemented or intentionally represented.
- [ ] **PAR-03**: Same-name parity resources align on behavior and contract shape, not only on route naming.

### UI Reality

- [ ] **UI-01**: A real admin/dashboard UI exists for the stabilized backend surface.
- [ ] **UI-02**: The UI supports core operator workflows without requiring the original PHP application.

## v2 Requirements

### Platform Extras

- **PLAT-01**: Optional Keitaro-adjacent platform modules such as self-update, cleaner, branding, and system tooling can be added after core parity.
- **PLAT-02**: Secondary experience improvements and non-core admin conveniences can be added after the real UI ships.

## Out of Scope

| Feature | Reason |
|---------|--------|
| Rebuilding every PHP module one-for-one before prioritization | Too broad; parity work must focus on high-value traffic/admin behavior first |
| New product features beyond Keitaro parity and the admin UI milestone | Would create scope creep while core parity is still incomplete |
| Treating docs or older reports as parity proof | Source inspection already showed doc drift and false positives |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| CORE-01 | Phase 1 | Complete |
| MACR-01 | Phase 2 | Complete |
| PIPE-01 | Phase 3 | Complete |
| GEO-01 | Phase 4 | Complete |
| VERI-01 | Phase 5 | Complete |
| SECU-01 | Phase 6 | Complete |
| SECU-02 | Phase 6 | Complete |
| API-01 | Phase 7 | Complete |
| API-02 | Phase 7 | Complete |
| PAR-01 | Phase 7.1 | Pending |
| PAR-02 | Phase 7.1 | Pending |
| PAR-03 | Phase 7.1 | Pending |
| UI-01 | Phase 8 | Pending |
| UI-02 | Phase 8 | Pending |

**Coverage:**
- v1 requirements: 14 total
- Mapped to phases: 14
- Unmapped: 0 ✓

---
*Requirements defined: 2026-04-01*
*Last updated: 2026-04-01 after direct source parity audit*
