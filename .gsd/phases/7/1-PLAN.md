---
phase: 7
plan: 1
wave: 1
---

# Plan 7.1: True Admin API Parity - Groups and Labels

## Objective
Implement `GroupsController` and `LabelsController` equivalent APIs in Next.js to match the structural grouping and tagging capabilities of the Keitaro interface. These entities are essential for frontend categorization.

## Context
- `reference/Keitaro_source_php/application/Component/Groups/GroupsController.php`
- `reference/Keitaro_source_php/application/Component/Labels/LabelsController.php`
- `.gsd/ROADMAP.md`

## Tasks

<task type="auto">
  <name>Implement Groups API Route</name>
  <files>src/app/api/admin/groups/route.ts</files>
  <action>
    - Create a standard CRUD API for `Groups` with Zod validation.
    - Endpoints: GET (list), POST (create), PUT (update), DELETE (delete).
    - Ensure `checkAuth()` middleware protects all routes.
    - Since `Group` exists in Prisma schema (or needs to be added), ensure Prisma queries map correctly to `type` (e.g., campaign groups).
  </action>
  <verify>curl -X GET 'http://localhost:3000/api/admin/groups' with standard X-API-Key</verify>
  <done>Returns a valid 200 response array of groups</done>
</task>

<task type="auto">
  <name>Implement Labels API Route</name>
  <files>src/app/api/admin/labels/route.ts</files>
  <action>
    - Create a standard CRUD API for `Labels` with Zod validation.
    - Endpoints: GET (list), POST (create), PUT (update), DELETE (delete).
    - Include `checkAuth()` protection.
    - Labels are heavily used in reporting and filtering, so ensure the returned payload matches Keitaro's expected structure (`id`, `name`, `color`, etc).
  </action>
  <verify>curl -X GET 'http://localhost:3000/api/admin/labels' with standard X-API-Key</verify>
  <done>Returns a valid 200 response array of labels</done>
</task>

## Success Criteria
- [ ] `GET /api/admin/groups` correctly queries Prisma.
- [ ] `GET /api/admin/labels` correctly queries Prisma.
- [ ] Both endpoints are fully typed and secured with Zod schemas.
