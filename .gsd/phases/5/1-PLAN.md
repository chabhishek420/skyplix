---
phase: 5
plan: 1
wave: 1
depends_on: []
files_modified:
  - internal/model/conversion.go
  - internal/queue/writer.go
  - internal/pipeline/stage/23_store_raw_clicks.go
autonomous: true
must_haves:
  truths:
    - "Conversion model encompasses attribution data (campaign, network, click_token)."
    - "Click attribution data is cached in Valkey for 24h during click recording."
    - "Queue writer supports both ClickRecord and ConversionRecord batching."
  artifacts:
    - "internal/model/conversion.go"
    - "internal/queue/writer.go (updated)"
---

# Plan 5.1: Conversion Foundation & Attribution Caching

<objective>
Establish the data models and storage foundation for conversion tracking.
The core of conversion tracking is attributing a postback (with a `click_token`) to the original click's metadata (campaign, stream, etc.).
This plan implements the attribution cache in Valkey and updates the ClickHouse writer to support conversion records.

Output:
- `Conversion` and `ConversionRecord` models.
- Attribution caching in Stage 23.
- Generic multi-table batch writer in `queue.Writer`.
</objective>

<context>
Load for context:
- internal/model/click.go
- internal/queue/writer.go
- internal/pipeline/stage/23_store_raw_clicks.go
- .gsd/SPEC.md
</context>

<tasks>

<task type="auto">
  <name>Define Conversion Models</name>
  <files>internal/model/conversion.go</files>
  <action>
    Create Conversion struct with fields: ID, CreatedAt, ClickToken, CampaignID, AffiliateNetworkID, Status (lead|sale|rejected|hold), Payout, Revenue, ExternalID.
    Ensure JSON tags match ClickHouse columns.
    AVOID: Complex methods; keep it as a DTO/POJO.
  </action>
  <verify>go build ./internal/model/...</verify>
  <done>Conversion model defined and compiles.</done>
</task>

<task type="auto">
  <name>Implement Attribution Caching in Stage 23</name>
  <files>internal/pipeline/stage/23_store_raw_clicks.go, internal/pipeline/payload.go</files>
  <action>
    Update StoreRawClicksStage to save click attribution metadata to Valkey.
    Key: "attr:{click_token}"
    Value: Serialized JSON of {CampaignID, StreamID, OfferID, LandingID, AffiliateNetworkID, CountryCode}.
    TTL: 24 hours (standard for attribution window).
    Inject Valkey client into the stage via the Server/Pipeline.
  </action>
  <verify>Run a click and check Valkey: `GET attr:<token>`</verify>
  <done>Click attribution data is persisted in Valkey on every click.</done>
</task>

<task type="auto">
  <name>Upgrade Queue Writer for Multi-Table Batches</name>
  <files>internal/queue/writer.go</files>
  <action>
    Define ConversionRecord struct.
    Update Writer struct to have two channels: ClickChan and ConvChan.
    Update Run() loop to use a select across both channels and two tickers/batches.
    Implement flushConversions() for the conversions table.
    AVOID: Using a single interface{} channel (performance hit); use separate typed channels for hot path.
  </action>
  <verify>go build ./internal/queue/...</verify>
  <done>Writer can asynchronously batch and flush both clicks and conversions to ClickHouse.</done>
</task>

</tasks>

<verification>
After all tasks, verify:
- [ ] go build ./... passes
- [ ] Unit tests for BotDB and RateLimiter still pass (no regressions in types)
</verification>

<success_criteria>
- [ ] Attribution cache is populated in Valkey.
- [ ] Writer supports multiple channels.
</success_criteria>
