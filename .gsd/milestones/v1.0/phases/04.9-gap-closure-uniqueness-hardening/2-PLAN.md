---
phase: 4.9
plan: 2
wave: 2
depends_on: ["4.9.1"]
files_modified:
  - internal/pipeline/pipeline.go
  - internal/pipeline/stage/21_prepare.go
  - internal/pipeline/stage/22_checks.go
  - internal/action/special.go
autonomous: true
must_haves:
  truths:
    - "ToCampaignAction performs internal re-dispatch instead of 302 redirect."
    - "System prevents infinite campaign loops (max 10 hops)."
    - "Stages 21 and 22 are implemented and executing in order."
  artifacts:
    - "internal/pipeline/stage/21_prepare.go"
    - "internal/pipeline/stage/22_checks.go"
---

# Plan 4.9.2: Recursive Pipeline & Stubs

<objective>
Enhance the core routing engine to support internal re-dispatches (Recursive ToCampaign) and finish the pipeline stages 21-22.
This achieves production parity with Keitaro's internal re-routing logic, which is critical for complex multi-campaign flows.

Output:
- Recursive pipeline support (Hops tracking).
- Optimized ToCampaignAction.
- Stages 21 and 22.
</objective>

<context>
Load for context:
- internal/pipeline/pipeline.go
- internal/action/special.go
- internal/pipeline/stage/20_execute_action.go
- .gsd/SPEC.md
</context>

<tasks>

<task type="auto">
  <name>Implement Pipeline Recursion</name>
  <files>internal/pipeline/pipeline.go</files>
  <action>
    Add `Hops int` to `Payload` struct.
    Modify `Pipeline.Run` to reset `Abort` flag and re-evaluate stages if a specific `ReDispatch` flag is set on the payload.
    Include a safety limit of 10 hops to prevent infinite recursion.
    AVOID: Modifying the `stages` slice during execution.
  </action>
  <verify>Run a click through a campaign-to-campaign loop (A -> B) and check logic.</verify>
  <done>Pipeline supports internal re-dispatch via Payload flag.</done>
</task>

<task type="auto">
  <name>Update ToCampaignAction (Internal Redirect)</name>
  <files>internal/action/special.go</files>
  <action>
    Modify `ToCampaignAction` to set `Payload.ReDispatch = true`, clear current campaign/stream/offer/landing, and set the new campaign alias in `RawClick.CampaignAlias`.
    The `Pipeline.Run` loop should then pick up the new campaign in the next iteration.
    AVOID: Returning a 302 redirect for internal transfers.
  </action>
  <verify>Check response of ToCampaign click is the target campaign's action, not a 302.</verify>
  <done>ToCampaign transitions are silent (no HTTP 302).</done>
</task>

<task type="auto">
  <name>Implement Pipeline Stages 21-22</name>
  <files>internal/pipeline/stage/21_prepare.go, internal/pipeline/stage/22_checks.go</files>
  <action>
    Stage 21: `PrepareRawClickToStore` — Final normalization (e.g., ensuring click_id/tokens are set, trimming long referrers/UAs).
    Stage 22: `CheckSendingToAnotherCampaign` — Verification for `ReDispatch` state and cross-campaign data integrity.
    Wire these stages into the main pipeline in `internal/server/server.go`.
  </action>
  <verify>Log output shows "Stage 21" and "Stage 22" executing after action execution.</verify>
  <done>Pipeline stages are complete through stage 23.</done>
</task>

</tasks>

<verification>
After all tasks, verify:
- [ ] go test ./... passes.
- [ ] Manual test of A -> B campaign flow proves no 302 on the intermediate step.
</verification>

<success_criteria>
- [ ] Internal re-dispatch works for ToCampaign.
- [ ] Pipeline stubs are eliminated.
</success_criteria>
