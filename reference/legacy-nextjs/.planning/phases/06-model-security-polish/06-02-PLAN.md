---
phase: 6
plan: 2
wave: 1
---

# Plan 6.2: Admin API Coverage & Validation (Part 1)

## Objective
Standardize all admin API routes to use the `checkAuth()` helper consistently and implement Zod schema validation for core mutation routes (Campaigns, Streams) to prevent invalid data from reaching the database.

## Context
- src/app/api/admin/campaigns/route.ts
- src/app/api/admin/streams/route.ts
- src/lib/auth/admin-auth.ts

## Tasks

<task type="auto">
  <name>Standardize Route Authentication</name>
  <files>src/app/api/admin/**/*</files>
  <action>
    - Audit all `route.ts` files in `src/app/api/admin/` to ensure `checkAuth(request)` is called at the beginning of each relevant handler (GET, POST, PUT, DELETE).
    - Specifically check `stats/route.ts`, `reports/route.ts`, and `audit-logs/route.ts`.
    - Fix any routes that were using bespoke auth or missing it entirely.
  </action>
  <verify>Check representative route files for checkAuth presence.</verify>
  <done>All admin API handlers consistently call checkAuth.</done>
</task>

<task type="auto">
  <name>Implement Zod Validation for Campaigns & Streams</name>
  <files>src/app/api/admin/campaigns/route.ts, src/app/api/admin/streams/route.ts</files>
  <action>
    - Define Zod schemas for Campaign and Stream creation/update in their respective route files (or a shared validation file).
    - Validate `request.json()` against the schema before processing mutations.
    - Return a 400 Bad Request if validation fails, with descriptive error messages.
  </action>
  <verify>Attempt to POST invalid JSON to /api/admin/campaigns and verify 400 response.</verify>
  <done>Campaign and Stream mutations are protected by Zod schemas.</done>
</task>

## Success Criteria
- [ ] Admin routes return 401 for unauthenticated requests.
- [ ] Invalid campaign data returns 400 with details.
- [ ] Valid campaign data processes correctly.
