# Keitaro Compatibility & Comparison

SkyPlix TDS is designed as a high-performance, modern alternative to Keitaro TDS. This document outlines the compatibility level between SkyPlix (Go) and Keitaro (PHP reference source).

## 1. Architecture Parity

SkyPlix replicates Keitaro's robust **Pipeline & Stage** architecture for click processing.

| Keitaro Stage (PHP) | SkyPlix Stage (Go) | Status |
| :--- | :--- | :--- |
| `DomainRedirectStage` | `DomainRedirectStage` | ✅ Implemented |
| `CheckPrefetchStage` | `CheckPrefetchStage` | ✅ Implemented |
| `BuildRawClickStage` | `BuildRawClickStage` | ✅ Implemented (Enhanced performance) |
| `FindCampaignStage` | `FindCampaignStage` | ✅ Implemented |
| `ChooseStreamStage` | `ChooseStreamStage` | ✅ Implemented (3-tier logic) |
| `ChooseOfferStage` | `ChooseOfferStage` | ✅ Implemented |
| `StoreRawClicksStage` | `StoreRawClicksStage` | ✅ Implemented (Async ClickHouse) |
| `ExecuteActionStage` | `ExecuteActionStage` | ✅ Implemented |

## 2. Traffic Filters Compatibility

SkyPlix supports all 27 core filter types found in Keitaro.

| Filter Category | Types | Parity |
| :--- | :--- | :--- |
| **Geo** | Country, Region, City | 100% |
| **Device** | Type, Model, Browser, OS, Version | 100% |
| **Network** | IP, IPv6, ISP, Operator, Connection, Proxy | 100% |
| **Traffic** | Referrer, Language, UserAgent, URL Token | 100% |
| **Tracking** | Uniqueness, Limit, Interval | 100% |

## 3. Key Differences & Enhancements

### Performance
- **SkyPlix:** Single Go binary, sub-5ms p99 latency, native Valkey/ClickHouse drivers.
- **Keitaro:** PHP-based, typically requires RoadRunner/Swoole for high load, higher memory overhead.

### Data Storage
- **SkyPlix:** Uses ClickHouse **Materialized Views** for real-time aggregation, allowing for instant reports even with millions of daily clicks.
- **Keitaro:** Uses standard SQL aggregation (optimized via specialized tables in newer versions).

### Authentication
- **SkyPlix:** JWT-based dashboard authentication with in-memory token management.
- **Keitaro:** Session/Cookie based PHP authentication.

## 4. What is Left? (Gaps)

The following Keitaro features are currently not implemented in SkyPlix v1.0:

1.  **Simulation Engine:** The ability to "test" a click path in the admin UI and see the trace of filter matches.
2.  **Advanced Triggers:** Execution of custom code or webhooks based on conversion thresholds (e.g., "disable stream after 50 sales").
3.  **UI Diagnostics:** Built-in server health and configuration check page in the dashboard.
4.  **Extensive Action Library:** While core "Redirect" and "HTML" are supported, Keitaro has a wider array of predefined JS/iframe injection actions.

## 5. Migration

SkyPlix includes a migration CLI (`skyplix migrate keitaro`) which handles:
- Campaign metadata (Alias, Name, Status)
- *Note: Streams and Filters currently require manual recreation due to diverging JSON schemas.*
