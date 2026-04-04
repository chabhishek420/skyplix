---
phase: 4.9.4
plan: 1
wave: 1
gap_closure: true
must_haves:
  truths:
    - "Global Uniqueness is tracked using SETNX in Valkey (sess:{visitor}:global)."
    - "UpdateGlobalUniquenessStage is wired into the pipeline."
    - "p99 latency is recorded under 1000 RPS concurrent load."
---

# Plan 4.9.4: Uniqueness & Benchmarking Gaps

## Problem
1. `IsUniqueGlobal` is present in the RawClick model but never populated by any pipeline stage.
2. The core v1.0 must-have of "<5ms p99" has not been verified with high-concurrency benchmarks.

## Root Cause
1. Global uniqueness was deferred in Phase 1 as part of "uniqueness debt" (ADR-009 focus was on campaign binding).
2. Development focus was on correctness (integration tests) rather than performance verification.

## Tasks

### 1. Hardening: Global Uniqueness
<task type="auto">
  <name>Implement Global Uniqueness logic</name>
  <files>internal/session/session.go, internal/pipeline/stage/global_uniqueness.go</files>
  <action>
    - Add `CheckGlobalUniqueness(ctx, visitorCode) (bool, error)` to `session.Service`. Use key: `sess:{visitorCode}:global` with 24h TTL.
    - Create `UpdateGlobalUniquenessStage` in new file.
    - Wire stage into Level 1 pipeline in `server.go` after `BuildRawClick`.
  </action>
  <verify>Run two consecutive clicks from same IP/UA. Second click must have IsUniqueGlobal=false.</verify>
  <done>Global uniqueness is tracked and persisted in Valkey.</done>
</task>

### 2. Verification: Performance Benchmark
<task type="auto">
  <name>Run p99 Benchmark Suite</name>
  <files>test/benchmark/load_test.sh</files>
  <action>
    - Create a shell script that uses `wrk` or `hey` to hit the `/click` endpoint with 10k requests at -c 50.
    - Output the p99 latency results.
    - Record the result in a new file `test/benchmark/RESULTS.md`.
    - AVOID: Testing against a production DB; use the local dev containers.
  </action>
  <verify>Record a clean p99 result.</verify>
  <done>Baseline performance verified against <5ms target.</done>
</task>

### 3. Documentation: Debt Closure
<task type="auto">
  <name>Update Projekt Trackers</name>
  <files>ROADMAP.md, TODO.md</files>
  <action>
    - Update `ROADMAP.md` Phase 4 and 4.9 to reflect that these gaps are closed.
    - Update `TODO.md` to move Uniqueness items from "Deferred" to "Complete".
  </action>
  <verify>Check file status.</verify>
  <done>Documentation reflects the new reality.</done>
</task>
