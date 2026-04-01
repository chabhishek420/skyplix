## Current Position
- **Phase**: Pre-Phase 1 (Design & Research Complete)
- **Task**: Final Architecture Audit and Context Flush
- **Status**: Active (resumed 2026-04-02T00:41)

## Last Session Summary
Performed a deep code-level audit of the Keitaro PHP reference source to validate all architectural assumptions before starting the Go rewrite. Found and fixed 8 material flaws:
1. Keitaro DOES support ClickHouse (`rbooster`).
2. There are 19 Action Types (not 15), including the critical `Remote` proxy action.
3. Bot detection runs *inline* in pipeline Stage 3, not out-of-band in Phase 4.
4. Entity Binding (locking returning visitors to streams/offers via Valkey) is a hard requirement.
5. The cron system has 20+ workers, including the critical Redis command queue flush.
6. `mssola/device-detector` doesn't exist; must use `robicode/device-detector`.
7. Stream selection is 3-tier (FORCED -> REGULAR -> DEFAULT) based on campaign type.
8. `gateway.php` bare-domain context is required.

All 6 planning documents were updated. The foundation is now rock solid.

## In-Progress Work
- Ready to begin Phase 1 (Foundation). No Go code written yet.
- Files modified: `ARCHITECTURE.md`, `ROADMAP.md`, `DECISIONS.md`, `SPEC.md`, `STACK.md`, `RESEARCH.md`, `STATE.md`, `JOURNAL.md`
- Tests status: N/A

## Blockers
- None.

## Context Dump
The research context is saturated with Keitaro PHP source code files. This pause clears the context window to maximize token efficiency for the upcoming Go implementation.

### Decisions Made
- **ADR-008**: Bot Detection must be in Phase 1 (pipeline block) to enable testing of the `IsBot` stream filter.
- **ADR-009**: Entity Binding is required in Phase 2 for rotation consistency.
- **ADR-010**: Go device detection needs to be selected and benchmarked in Phase 1 (`robicode` vs `mileusna`).

### Approaches Tried
- Cross-referencing PHP `StageInterface` implementations with `.gsd` docs: identified missing links in our mental model (EntityBinding, Remote action, exact bot detection location).

### Current Hypothesis
The architecture is 100% validated against the real production system. The 2-level pipeline (23+13 stages) powered by Valkey cache and async workers is the correct path forward for sub-5ms latency.

### Files of Interest
- `reference/Keitaro_source_php/Traffic/Pipeline/Pipeline.php` (The truth of the sequence)
- `.gsd/ROADMAP.md` (The updated execution plan)

## Next Steps
1. Run `/resume` to restore basic context.
2. Run `/plan 1` to create the objective and task breakdown for Phase 1.
3. Scaffold the Go project (`cmd`, `internal`, `db`) and Docker Compose.
