# FEATURE-MAP.md

This document maps Keitaro PHP features and Yljary Scale enhancements to the SkyPlix implementation.

## Status Legend
- ✅ **Complete**: Fully implemented and tested.
- 🔄 **Partial**: Core logic exists, but some edge cases or sub-features are missing.
- ⬜ **Missing**: Not yet implemented.
- 🚀 **Enhanced**: Implementation exceeds Keitaro parity (Yljary Scale).

---

## 1. Campaign Features

| Feature | Keitaro Parity | SkyPlix Status | Notes |
|---------|----------------|----------------|-------|
| UUID-slugged URLs | Required | ✅ Complete | Handled by `/{alias}` route and `FindCampaign` stage. |
| Cost Models (CPC/CPM/CPA) | Required | 🔄 Partial | `Cost` field exists in `RawClick`, but CPA/CPM logic needs integration. |
| Daily + Total Click Caps | Required | ✅ Complete | Implemented in `UpdateHitLimit` stage. |
| Scheduling (Days/Hours) | Required | ✅ Complete | Implemented via `ScheduleFilter`. |
| Uniqueness (Global/24h/Session) | Required | ✅ Complete | Stages 8, 10, 18 handle these. |
| Campaign-level filtering | Required | ✅ Complete | `Campaign` model supports filter settings. |
| Campaign Clone / Template | Required | ✅ Complete | `HandleCloneCampaign` implemented in Admin API. |
| Campaign Groups / Folders | Required | ⬜ Missing | Schema support needed for grouping. |
| Campaign Notes and Tags | Required | ⬜ Missing | |

---

## 2. Stream/Flow Features

| Feature | Keitaro Parity | SkyPlix Status | Notes |
|---------|----------------|----------------|-------|
| Waterfall Priority (3-tier) | Required | ✅ Complete | FORCED -> REGULAR -> DEFAULT implemented in `ChooseStream`. |
| Stream Weight Splitting | Required | ✅ Complete | Weighted random selection in `ChooseStream` via `Rotator`. |
| Filter Logic (AND/OR/NOT) | Required | ✅ Complete | `MatchAll` (AND) and individual filter logic implemented. |
| Filter: Geo (Country/City/ISP) | Required | ✅ Complete | `GeoFilter` uses MaxMind mmdb. |
| Filter: Device/OS/Browser | Required | ✅ Complete | `DeviceFilter` and UA parser integrated. |
| Filter: Referrer/Keyword | Required | ✅ Complete | `TrafficFilter` handles referrer domain/URL. |
| Filter: IP Range (CIDR) | Required | ✅ Complete | `CIDRBlacklist` and IP filters implemented. |
| Filter: Custom URL Param | Required | ✅ Complete | `ParamsFilter` implemented. |
| Filter: User-Agent Regex | Required | ✅ Complete | Implemented. |
| Filter: Custom JS Result | Required | ⬜ Missing | Planned for Phase 9/12 (Bot Detection). |
| Redirect: 302/301/JS/Iframe | Required | ✅ Complete | Handled by `ActionEngine`. |
| Offer Rotation within Stream | Required | ✅ Complete | `ChooseOffer` stage supports weighted rotation. |

---

## 3. Offer & Landing Features

| Feature | Keitaro Parity | SkyPlix Status | Notes |
|---------|----------------|----------------|-------|
| Macro Tokens ({click_id}, etc) | Required | ✅ Complete | `macro` package handles substitution. |
| Offer Weights and Caps | Required | ✅ Complete | `Offer` model and `ChooseOffer` stage. |
| Status Tracking | Required | ✅ Complete | `State` field in models. |
| Landing Rotation | Required | ✅ Complete | `ChooseLanding` stage. |

---

## 4. Conversion & Attribution Features

| Feature | Keitaro Parity | SkyPlix Status | Notes |
|---------|----------------|----------------|-------|
| S2S Postback (GET/POST) | Required | ✅ Complete | `PostbackHandler` implemented. |
| Pixel Tracking (1x1) | Required | ⬜ Missing | Trivial to add but not explicitly in `routes.go` yet. |
| Multiple Conv Types | Required | 🔄 Partial | `Status` field exists, but logic for multiple per click needs verification. |
| Payout & Revenue Tracking | Required | ✅ Complete | Handled in `PostbackHandler`. |
| Postback Token Validation | Required | ✅ Complete | Secure key lookup in `PostbackHandler`. |
| Transaction-ID Deduplication | Required | ✅ Complete | Using Valkey NX in `PostbackHandler` (as per memory). |
| Conversion Replay | Required | ⬜ Missing | |

---

## 5. Analytics & Reporting

| Feature | Keitaro Parity | SkyPlix Status | Notes |
|---------|----------------|----------------|-------|
| Real-time Stats (<10s) | Required | ✅ Complete | ClickHouse materialized views (`stats_hourly`). |
| Multi-dimension Drilldown | Required | ✅ Complete | `GenerateReport` with `GroupBy`. |
| Dimensions: Geo/Device/OS | Required | ✅ Complete | Supported in `QueryBuilder`. |
| Metrics: ROI/CR/EPC/Profit | Required | ✅ Complete | Calculated in `ReportRow.CalculateDerived`. |
| Export (CSV/XLSX) | Required | ⬜ Missing | |
| Saved Report Templates | Required | ⬜ Missing | |

---

## 6. Administration & Scalability

| Feature | Keitaro Parity | SkyPlix Status | Notes |
|---------|----------------|----------------|-------|
| Multi-workspace Isolation | Required | ⬜ Missing | Needs schema updates. |
| RBAC (Roles) | Required | 🔄 Partial | `Role` field in `User`, but middleware needs enforcement. |
| API Key Management | Required | ✅ Complete | `APIKeyAuth` middleware and CRUD. |
| Tracking Domains | Required | ✅ Complete | `Domain` model and `DomainRedirect` stage. |
| Bot Database Management | Required | ✅ Complete | `botdb` package and CRUD APIs. |
| System Health Dashboard | Required | 🔄 Partial | `/api/v1/ready` provides JSON, UI needed. |
| Audit Log | Required | ⬜ Missing | |
| Graceful Shutdown | Required | ✅ Complete | `main.go` handles SIGINT/SIGTERM. |

---

## 7. Yljary Scale Enhancements (Beyond Keitaro)

| Feature | Source | SkyPlix Status | Notes |
|---------|--------|----------------|-------|
| Stateless Scaling | Yljary | ✅ Complete | State in PG/Valkey/CH. |
| ClickHouse Batch Writing | Yljary | ✅ Complete | `queue/writer.go` handles async batching. |
| Multi-Armed Bandit (MAB) | Yljary | 🔄 Partial | Roadmap Phase 8. |
| Advanced TLS (JA3/JA4) | Yljary | 🔄 Partial | Roadmap Phase 12. Implementation started. |
| DC/Proxy/VPN Detection | Yljary | ✅ Complete | `GeoIP` lookup with `is_datacenter`. |
| Click Velocity Detection | Yljary | ✅ Complete | `ratelimit` package. |
| Headless Browser Challenge | Yljary | ⬜ Missing | Planned for Phase 9/12. |
| Cross-campaign Blacklist | Yljary | ✅ Complete | Global `botdb`. |

---

## Summary of Gaps

1. **Administration**: Multi-workspace and RBAC enforcement.
2. **Reporting**: Exports and Saved Templates.
3. **Advanced Bot Detection**: JS challenges and full JA3 integration.
4. **Campaign Org**: Groups/Folders and Tags.
5. **Analytics**: Conversion replay and complex attribution models.
