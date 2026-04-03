---
phase: 5
plan: 2
wave: 2
depends_on: ["5.1"]
files_modified:
  - internal/admin/handler/postback.go
  - internal/server/routes.go
  - internal/server/server.go
autonomous: true
must_haves:
  truths:
    - "System accepts GET/POST /postback/{key} from external sources."
    - "Conversion is attributed to original click via sub_id (click_token)."
    - "Success returns 200 'ok', failure returns error with code."
  artifacts:
    - "internal/admin/handler/postback.go"
    - "internal/server/routes.go (updated)"
---

# Plan 5.2: Postback API & Attribution Engine

<objective>
Implement the S2S (Server-to-Server) postback API for receiving conversions.
Attribuation is the heart of the system—converting a click_token from an external network (via sub_id) back into internal campaign/offer data.

Output:
- Postback handler supporting GET and POST.
- Global postback key validation (settings based).
- Attribution engine (Valkey first, CH fallback).
- Async conversion recording.
</objective>

<context>
Load for context:
- internal/admin/handler/campaigns.go
- internal/queue/writer.go
- internal/botdb/valkey.go (for cache pattern)
</context>

<tasks>

<task type="auto">
  <name>Implement Postback Handler</name>
  <files>internal/admin/handler/postback.go</files>
  <action>
    Create HandlePostback and resolve click_token from query params (sub_id, click_id, etc.).
    Implement attribution lookup:
      1. Check Valkey: "attr:{token}" -> campaign_id, offer_id, etc.
      2. If missing, query ClickHouse: `SELECT campaign_id, ... FROM clicks WHERE click_token = token`.
    AVOID: Blocking the request for too long during CH lookup; use a reasonable timeout.
    Create ConversionRecord and push to convChan.
  </action>
  <verify>curl "http://localhost:8080/postback/global_key?sub_id=T123&payout=1.5"</verify>
  <done>Conversions are successfully attributed and queued for ClickHouse.</done>
</task>

<task type="auto">
  <name>Wire Postback Routes</name>
  <files>internal/server/routes.go, internal/server/server.go</files>
  <action>
    Register `POST /postback/{key}` and `GET /postback/{key}`.
    Note: These are PUBLIC routes (no X-Api-Key) but rely on the {key} in the path or query.
    Inject ConvChan into the handler.
  </action>
  <verify>Check routes with a test request to a non-existent key returns 401/403.</verify>
  <done>Postback API endpoints are reachable.</done>
</task>

</tasks>

<verification>
After all tasks, verify:
- [ ] Send test postback -> verify record in CH `conversions` table.
- [ ] Check Valkey attribution cache hit (log output).
</verification>

<success_criteria>
- [ ] Postbacks correctly attribute to campaigns/offers.
- [ ] High-volume ingestion is async and non-blocking.
</success_criteria>
