# ROADMAP.md

> **Current Milestone**: v2.0 — Smart Optimizer & Scaling
> **Goal**: Transform SkyPlix into a self-optimizing, cluster-ready engine with state-of-the-art bot detection.

## Must-Haves
- [ ] **MAB Auto-Optimization**: Automated weight adjustment for streams and offers based on real-time CR/ROI metrics.
- [ ] **Advanced TLS Fingerprinting**: Integration of JA3/JA4 fingerprinting to identify compliance scrapers and headless browsers.
- [ ] **Cluster Execution**: Native support for horizontal scaling with global Valkey session consistency and geo-routing metrics.
- [ ] **Postback v2 & Macro Engine**: Customizable postback templates with a unified JS-based macro evaluator for dynamic logic.

---

## Phases

### Phase 7.9: Gap Closure & v2.0 Foundation Cleanup
**Status**: ✅ Completed
**Objective**: Synchronize shadow development with roadmap and backfill architectural docs.

**Gaps to Close:**
- [x] Sync Shadow Development: Formally plan JA3/JA4 and Cluster Bus work.
- [x] Documentation Hygiene: Populate `DECISIONS.md` with v1.0 and initial v2.0 rationale.
### Milestone v2.0: Scaling & Automation [100%]
- [x] Phase 5: Conversion Tracking Implementation [100%]
- [x] Phase 6: Admin Dashboard Components [100%]
- [x] Phase 7: Real-time Reports [100%]
- [x] Phase 8: Campaign Optimization (MAB) [100%]
- [x] Phase 11: High Availability (Valkey) [100%]
- [x] Phase 12: Advanced Bot Detection (TLS JA3) [100%]
- [x] Phase 13: Simulation & Triggers [100%]

### Milestone v2.1: Audit & Repair [100%]
- [x] Phase 13: Stabilization & GSD Logic Recovery [100%]
    - [x] Fix integration test 401s in cloaking.
    - [x] Resolve human IP bot flagging false positives.
    - [x] Sync GSD artifacts with codebase drift.
      - [x] [v11 Verification](.gsd/verification/v11-high-availability.md)
      - [x] [v12 Verification](.gsd/verification/v12-tls-fingerprinting.md)
Multi-Armed Bandit (Epsilon-Greedy or Thompson Sampling) logic.
**Requirements**:
- ClickHouse real-time metric reader (fetch CR/EPV per stream).
- Weighted selection engine upgrade to support "Auto-Optimize" mode.
- Background worker for periodic weight recalculation based on confidence intervals.

### Phase 9: JA3/JA4 Next-Gen Anti-Bot
**Status**: 🔄 In Progress
**Objective**: Harden cloaking with network-level fingerprinting.
- Implement JA3/JA4 TLS fingerprint extraction (requires reading raw ClientHello).
- Maintain a local reputation database of "bot-like" TLS signatures.
- Integrate behavior-based analysis (request frequency + header order consistency).

### Phase 10: High-Availability & Cluster Mode
**Status**: 🔄 In Progress
**Objective**: Enable geo-distributed scaling.
- Implement distributed Valkey locking for critical configuration updates.
- Centralized logging and metrics aggregation for multiple TDS nodes.
- Local cache warming sync via Pub/Sub for entity mutations.

### Phase 11: Enterprise Dashboard & Multi-User
**Status**: ⬜ Not Started
**Objective**: Scale the UI for team-based operations.
- Multi-user RBAC (Admin, Manager, Team Lead).
- Advanced Report Builder with custom dimensions and CSV/Excel exports.
- Collaborative campaign notes and audit logs for administrative changes.

---

*Note: Phase 1-7 (v1.0) history is archived in [.gsd/milestones/v1.0/phases/](file:///.gsd/milestones/v1.0/phases/)*
