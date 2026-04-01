---
phase: 6
plan: 3
wave: 2
---

# Plan 6.3: Specialized Validation & Final Handoff

## Objective
Complete the API validation layer with the Offers route, provide clear documentation for custom bot-rule security, and update project metadata to match the finalized architecture.

## Context
- src/app/api/admin/offers/route.ts
- AGENTS.md
- docs/bot-rules.md
- next.config.ts

## Tasks

<task type="auto">
  <name>Implement Zod Validation for Offers</name>
  <files>src/app/api/admin/offers/route.ts</files>
  <action>
    - Define Zod schema for Offer creation and updates.
    - Validate `request.json()` in POST and PUT handlers.
    - Return 400 Bad Request with error details on validation failure.
  </action>
  <verify>POST invalid offer data and verify 400 response.</verify>
  <done>Offer mutations are protected by Zod schemas.</done>
</task>

<task type="auto">
  <name>Document Bot-Rule Regex Safety</name>
  <files>docs/bot-rules.md</files>
  <action>
    - [NEW] Create docs/bot-rules.md.
    - Explain how bot rules use regex.
    - Provide warnings about "Evil Regex" (ReDoS) and performance impacts of complex patterns.
    - Provide examples of safe vs unsafe patterns.
  </action>
  <verify>Verify file existence and quality of content.</verify>
  <done>Bot-rule safety documentation is available for users.</done>
</task>

<task type="auto">
  <name>Final Project Metadata Cleanup</name>
  <files>AGENTS.md, next.config.ts</files>
  <action>
    - [MODIFY] AGENTS.md: Update API route count from 32 to 24.
    - Ensure endpoint summaries reflect all current routes.
    - [MODIFY] next.config.ts: If previous check in Plan 6.1 succeeded, keep `ignoreBuildErrors: false`. If it failed, document WHY it must remain `true` as a comment in the file.
  </action>
  <verify>Check AGENTS.md for correct count and configuration file for hardening.</verify>
  <done>Project metadata is accurate and reflects the current codebase.</done>
</task>

## Success Criteria
- [ ] Offer validation prevents empty URLs or negative weights.
- [ ] Bot-rule docs are clear and helpful.
- [ ] AGENTS.md route count is exactly 24.
