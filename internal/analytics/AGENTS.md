<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-06 | Updated: 2026-04-06 -->

# analytics

## Purpose
Analytics service for querying ClickHouse and generating reports. Powers the admin UI dashboards and reports.

## Key Files

| File | Purpose |
|------|---------|
| `service.go` | Main analytics service |
| `service_test.go` | Service tests |
| `query_builder.go` | SQL query construction |
| `query_builder_test.go` | Query builder tests |
| `models.go` | Analytics data models |

## Data Sources

- **ClickHouse** - Primary analytics database
- Real-time materialized views for common aggregations
- Historical data for trend analysis

## For AI Agents

### Working In This Directory
- ClickHouse query construction
- Date range filtering
- Campaign/stream aggregation
- Time-series data handling

### Testing
- Use `zap.NewNop()` for test logger
- Mock ClickHouse client in tests

<!-- MANUAL: -->
