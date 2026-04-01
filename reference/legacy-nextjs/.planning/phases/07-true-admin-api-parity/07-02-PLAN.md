---
phase: 7
plan: 2
wave: 1
---

# Plan 7.2: True Admin API Parity - Triggers and API Templates

## Objective
Implement `Triggers` API endpoints for managing traffic automations (e.g., pausing campaigns/streams on thresholds) and the configuration/template objects provided out-of-the-box by Keitaro (`AffiliateNetworkTemplates`, `CodePresets`, `TrafficSourceTemplates`).

## Context
- `reference/Keitaro_source_php/application/Component/Triggers/TriggersController.php`
- `reference/Keitaro_source_php/application/Component/AffiliateNetworkTemplates/AffiliateNetworkTemplatesController.php`
- `reference/Keitaro_source_php/application/Component/TrafficSourceTemplates/TrafficSourceTemplatesController.php`
- `.gsd/ROADMAP.md`

## Tasks

<task type="auto">
  <name>Implement Triggers API Route</name>
  <files>src/app/api/admin/triggers/route.ts</files>
  <action>
    - Ensure standard CRUD `checkAuth()` security wrapper.
    - Endpoints: GET, POST, PUT, DELETE for Triggers.
    - Map logic to Prisma schema (a Trigger evaluates metric constraints).
    - Provide robust Zod validation.
  </action>
  <verify>curl -X GET 'http://localhost:3000/api/admin/triggers' with standard X-API-Key</verify>
  <done>Returns a valid 200 array</done>
</task>

<task type="auto">
  <name>Implement Settings Templates Providers API Route</name>
  <files>
    - src/app/api/admin/templates/affiliate-networks/route.ts
    - src/app/api/admin/templates/traffic-sources/route.ts
  </files>
  <action>
    - Create read-only GET endpoints serving the static JSON representations matching Keitaro's core network template catalogue.
    - Wrap in `checkAuth()`.
  </action>
  <verify>curl -X GET 'http://localhost:3000/api/admin/templates/affiliate-networks'</verify>
  <done>JSON returns known standard presets correctly</done>
</task>

## Success Criteria
- [ ] `GET /api/admin/triggers` returns valid trigger objects.
- [ ] Boilerplate templates API properly exposes standard setups for the UI dropdown.
