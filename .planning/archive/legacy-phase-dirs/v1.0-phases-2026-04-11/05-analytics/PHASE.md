# Phase 5: Conversion Tracking & Analytics

## Status: 🔲 NOT STARTED (Partial Implementation)

## Goal
Real-time data processing and conversion attribution.

## Verified Implementation

### What Exists (Verified by Code Inspection)

| Component | File | Status |
|-----------|------|--------|
| **Postback Handler** | `internal/admin/handler/postback.go` | ✅ Exists |
| **Postback Macro** | `internal/macro/postback.go` | ✅ Macro expansion |
| **Conversion Model** | `internal/model/conversion.go` | ✅ Data model |
| **Attribution Service** | `internal/attribution/service.go` | ✅ Attribution logic |
| **Analytics Service** | `internal/analytics/service.go` | ✅ Query service |
| **Query Builder** | `internal/analytics/query_builder.go` | ✅ Query construction |
| **ClickHouse Writer** | `internal/queue/writer.go` | ✅ Batch writes |
| **ClickHouse Config** | `internal/config/config.go` | ✅ Configuration |

### Configuration (Verified)
```go
// internal/config/config.go
type Config struct {
    ClickHouse ClickHouseConfig
    // ...
}
```

### docker-compose Services (Verified)
- ✅ ClickHouse service defined
- ✅ ClickHouse port 8123 (HTTP) and 9000 (TCP)
- ✅ Health check configured

### What Needs Verification
- [ ] Real-time streaming to ClickHouse (verify `23_store_raw_clicks.go`)
- [ ] Postback validation logic (HMAC-SHA256)
- [ ] Analytics query performance

## Requirements to Implement
- [~] DATA-01: Real-time analytics (ClickHouse writer exists)
- [x] DATA-02: Postback processing (handler exists)
- [x] DATA-03: Conversion attribution (service exists)
- [ ] DATA-04: Payout/cost tracking (verify implementation)

## Success Criteria
- [ ] Postbacks received, validated, attributed
- [ ] Clicks/conversions streamed to ClickHouse
- [ ] Analytics queries < 1 second

## Dependencies
- Phase 3 (Actions & Landers)
