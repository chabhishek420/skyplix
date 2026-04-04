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
**Status**: ⬜ Not Started
**Objective**: Synchronize shadow development with roadmap and backfill architectural docs.

**Gaps to Close:**
- [ ] Sync Shadow Development: Formally plan JA3/JA4 and Cluster Bus work.
- [ ] Documentation Hygiene: Populate `DECISIONS.md` with v1.0 and initial v2.0 rationale.
- [ ] Verification Audit: Add tests and `VERIFICATION.md` for `cluster.Bus` and `filter` components.
- [ ] Latency Regression: Re-verify 2.06ms p99 baseline with new features enabled.

### Phase 8: Smart Optimization Engine (MAB)
**Status**: ⬜ Not Started
**Objective**: Implement Multi-Armed Bandit (Epsilon-Greedy or Thompson Sampling) logic.
**Requirements**:
- ClickHouse real-time metric reader (fetch CR/EPV per stream).
- Weighted selection engine upgrade to support "Auto-Optimize" mode.
- Background worker for periodic weight recalculation based on confidence intervals.

### Phase 9: JA3/JA4 Next-Gen Anti-Bot
**Status**: ⬜ Not Started
**Objective**: Harden cloaking with network-level fingerprinting.
- Implement JA3/JA4 TLS fingerprint extraction (requires reading raw ClientHello).
- Maintain a local reputation database of "bot-like" TLS signatures.
- Integrate behavior-based analysis (request frequency + header order consistency).

### Phase 10: High-Availability & Cluster Mode
**Status**: ⬜ Not Started
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
