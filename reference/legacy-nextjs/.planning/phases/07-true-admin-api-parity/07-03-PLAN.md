---
phase: 7
plan: 3
wave: 2
---

# Plan 7.3: True Admin API Parity - Stream Entities and Integrations

## Objective
Implement `StreamActions`, `StreamFilters`, `AppsFlyer`, and `Facebook` API endpoints. Keitaro UI handles streams uniquely by querying their filters and actions explicitly as child relationships through these specific controllers rather than one massive stream object. Integrations allow postback firing setups.

## Context
- `reference/Keitaro_source_php/application/Component/StreamActions/StreamActionsController.php`
- `reference/Keitaro_source_php/application/Component/StreamFilters/StreamFiltersController.php`
- `reference/Keitaro_source_php/application/Component/Facebook/FacebookController.php`
- `.gsd/ROADMAP.md`

## Tasks

<task type="auto">
  <name>Implement Stream Actions and Filters APIs</name>
  <files>
    - src/app/api/admin/streams/actions/route.ts
    - src/app/api/admin/streams/filters/route.ts
  </files>
  <action>
    - Stream Filters and Actions need generic CRUD wrapped in `checkAuth()`.
    - Accept `stream_id` to query specific related data.
    - Setup robust Zod payload validation to match Keitaro's JSON format.
  </action>
  <verify>curl -X GET 'http://localhost:3000/api/admin/streams/filters?stream_id=1'</verify>
  <done>Returns filters for a specific stream</done>
</task>

<task type="auto">
  <name>Implement Integration APIs</name>
  <files>
    - src/app/api/admin/integrations/facebook/route.ts
    - src/app/api/admin/integrations/appsflyer/route.ts
  </files>
  <action>
    - Setup GET/PUT handlers for storing and defining the webhook setup keys for FB/AppsFlyer.
    - Wrap in `checkAuth()`.
  </action>
  <verify>curl -X GET 'http://localhost:3000/api/admin/integrations/facebook'</verify>
  <done>Returns integration configuration wrapper</done>
</task>

## Success Criteria
- [ ] Child components of Streams can be operated on individually.
- [ ] Integrations exist as accessible config keys.
