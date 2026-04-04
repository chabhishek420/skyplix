---
phase: 5
plan: 2
wave: 2
depends_on: ["5.1"]
files_modified:
  - internal/analytics/models.go
  - internal/analytics/query_builder.go
  - internal/analytics/service.go
  - test/unit/analytics/query_builder_test.go
  - test/unit/analytics/service_test.go
autonomous: true
requirements:
  - stats aggregation
  - report builder
  - ClickHouse materialized views
must_haves:
  truths:
    - "Analytics service queries click stats and conversion stats from separate ClickHouse tables."
    - "Go-side merge combines click metrics and conversion metrics by grouping key — no ClickHouse JOIN."
    - "Dynamic SQL query builder uses a whitelist of allowed dimensions and filters to prevent injection."
    - "Date range optimization selects hourly tables for short ranges (<=2 days) and daily tables for longer ranges."
    - "Derived metrics (CR, EPC, CPC, profit, ROI) are calculated in Go after merging raw metrics."
    - "Device/OS/browser groupings only apply to click stats; conversion stats fall back to campaign+stream+offer+country level."
  artifacts:
    - "internal/analytics/models.go"
    - "internal/analytics/query_builder.go"
    - "internal/analytics/service.go"
    - "test/unit/analytics/query_builder_test.go"
---

# Plan 5.2: Analytics Reporting Service

<objective>
Implement the `internal/analytics/` package — the core reporting engine that queries ClickHouse materialized views and produces aggregated report data with derived metrics.

This package is the bridge between ClickHouse MVs (Plan 5.1) and the HTTP API (Plan 5.3). It encapsulates:
- Report query DTOs (request/response models)
- Dynamic SQL query builder with injection protection
- Parallel query execution against click + conversion stats tables
- Go-side merge of results by grouping key
- Derived metric calculation (CR, EPC, CPC, profit, ROI)
- Date range optimization (hourly vs daily table selection)
- Pagination and sorting

Output:
- Complete `internal/analytics/` package with 3 files + unit tests.
</objective>

<context>
Load for context:
- .planning/phases/05-conversion-tracking-analytics/05-RESEARCH.md (API contract, query architecture, derived metrics, groupable dimensions)
- .planning/phases/05-conversion-tracking-analytics/05-CONTEXT.md (decisions on reporting API surface, response shape)
- db/clickhouse/migrations/005_create_stats_materialized_views.sql (MV column names and types — created in Plan 5.1)
- internal/admin/handler/helpers.go (existing respondJSON, parsePagination patterns)
- internal/admin/handler/handler.go (handler struct DI pattern)
</context>

<tasks>

<task type="auto">
  <name>Create Report Models (DTOs)</name>
  <files>internal/analytics/models.go</files>
  <action>
    Create `internal/analytics/models.go` with these types:

    **ReportQuery** — parsed request:
    - GroupBy []string (validated dimension keys)
    - DateFrom time.Time
    - DateTo time.Time
    - Filters map[string][]string (dimension key -> filter values, e.g., "campaign_id" -> ["uuid1", "uuid2"])
    - SortField string
    - SortDir string ("asc" or "desc")
    - Limit int (default 50, max 1000)
    - Offset int (default 0)

    **ReportRow** — single result row:
    - Dimensions map[string]string (e.g., "campaign_id" -> "uuid", "country" -> "US")
    - Clicks uint64
    - UniqueClicks uint64
    - Bots uint64
    - Conversions uint64
    - CR float64 (conversion rate percentage)
    - Revenue float64
    - Cost float64
    - Profit float64
    - ROI float64
    - EPC float64 (earnings per click)
    - CPC float64 (cost per click)

    **ReportSummary** — totals:
    - Same metric fields as ReportRow (no dimensions)

    **ReportResponse** — API response envelope:
    - Rows []ReportRow
    - Summary ReportSummary
    - Meta ReportMeta

    **ReportMeta** — pagination and context:
    - DateFrom string (formatted date)
    - DateTo string (formatted date)
    - GroupBy []string
    - TotalRows int
    - Limit int
    - Offset int

    Include a `calculateDerived(row *ReportRow)` function that computes:
    - CR = conversions / unique_clicks * 100 (0 if unique_clicks == 0)
    - EPC = revenue / clicks (0 if clicks == 0)
    - CPC = cost / clicks (0 if clicks == 0)
    - Profit = revenue - cost
    - ROI = profit / cost * 100 (0 if cost == 0)

    AVOID: Using float64 for monetary fields in storage — keep as float64 only for computed display values.
  </action>
  <verify>go build ./internal/analytics/...</verify>
  <done>Models compile and provide complete DTOs for the reporting pipeline.</done>
</task>

<task type="auto">
  <name>Create Dynamic SQL Query Builder</name>
  <files>internal/analytics/query_builder.go, test/unit/analytics/query_builder_test.go</files>
  <action>
    Create `internal/analytics/query_builder.go` with a `QueryBuilder` that generates safe ClickHouse SQL.

    **Dimension Registry** — static whitelist map:
    ```
    var dimensionRegistry = map[string]DimensionDef{
      "campaign":  {Column: "campaign_id", Tables: "all"},
      "stream":    {Column: "stream_id", Tables: "all"},
      "offer":     {Column: "offer_id", Tables: "all"},
      "landing":   {Column: "landing_id", Tables: "clicks_only"},
      "country":   {Column: "country_code", Tables: "all"},
      "device":    {Column: "device_type", Tables: "clicks_only"},
      "os":        {Column: "os", Tables: "clicks_only"},
      "browser":   {Column: "browser", Tables: "clicks_only"},
      "day":       {Column: "toDate(hour)", Tables: "all", DailyColumn: "day"},
      "hour":      {Column: "hour", Tables: "all", DailyColumn: ""},
      "status":    {Column: "status", Tables: "convs_only"},
    }
    ```

    **DimensionDef** struct:
    - Column string (ClickHouse column expression)
    - Tables string ("all", "clicks_only", "convs_only")
    - DailyColumn string (override for daily tables, empty = same as Column)

    **BuildClickStatsQuery(q *ReportQuery) (sql string, args []any, err error)**:
    - Selects from `stats_hourly` or `stats_daily` based on date range heuristic:
      - Use hourly if: date range <= 2 days OR group_by contains "hour"
      - Use daily otherwise
    - SELECT: dimension columns + sum(clicks), sum(unique_clicks), sum(bots), sum(cost), sum(click_payout)
    - WHERE: time range filter + any dimension filters
    - GROUP BY: all selected dimensions
    - Use `?` placeholders for all filter values (parameterized queries)
    - Skip dimensions marked "convs_only"

    **BuildConvStatsQuery(q *ReportQuery) (sql string, args []any, err error)**:
    - Same logic but for conv_stats_hourly/conv_stats_daily
    - SELECT: dimension columns + sum(conversions), sum(revenue), sum(payout)
    - Skip dimensions marked "clicks_only" — when device/os/browser are requested,
      the conversion query uses a reduced GROUP BY (without those dimensions)
    - Always include `sum()` wrapper (SummingMergeTree requirement)

    **Validation**:
    - Return error for unknown dimension keys
    - Return error for empty date range
    - Return error if limit > 1000

    **Filter application**:
    - For UUID filter values: validate format before including
    - For string filters: use `IN (?, ?, ...)` with parameterized values
    - For time column: use `>= ?` and `< ?` (exclusive end for daily, inclusive for hourly)

    Create `test/unit/analytics/query_builder_test.go` with table-driven tests:
    - Single group_by dimension generates correct SQL
    - Multi-group_by generates correct GROUP BY clause
    - Filters generate parameterized WHERE clauses
    - Date range optimization picks correct table
    - Invalid dimension returns error
    - Device-only dimensions excluded from conversion query
    - "hour" group_by forces hourly table
    - Empty group_by generates valid SQL (aggregate-only)

    AVOID:
    - String concatenation of user-supplied filter values into SQL.
    - Using `*` in SELECT — always enumerate columns.
    - Generating ORDER BY in the SQL — sorting happens in Go after merge.
  </action>
  <verify>go test -v ./test/unit/analytics/...</verify>
  <done>Query builder generates correct, parameterized SQL for all dimension/filter combinations. Tests pass.</done>
</task>

<task type="auto">
  <name>Create Analytics Service (Query + Merge + Compute)</name>
  <files>internal/analytics/service.go, test/unit/analytics/service_test.go</files>
  <action>
    Create `internal/analytics/service.go` with the `Service` struct:

    ```go
    type Service struct {
      ch     driver.Conn    // ClickHouse read connection
      db     *pgxpool.Pool  // PostgreSQL for entity name resolution
      logger *zap.Logger
      qb     *QueryBuilder
    }
    ```

    **Constructor**: `New(ch driver.Conn, db *pgxpool.Pool, logger *zap.Logger) *Service`

    **GenerateReport(ctx context.Context, q *ReportQuery) (*ReportResponse, error)**:
    1. Validate query (call QueryBuilder validation)
    2. Build click stats SQL and conv stats SQL in parallel
    3. Execute both queries concurrently using `errgroup.Group` with 5s timeout context
    4. Parse click stats rows into a map keyed by composite grouping key (e.g., "campaign_id:uuid|country:US")
    5. Parse conv stats rows and merge into the same map by grouping key
    6. For each merged row, call `calculateDerived()` to compute CR/EPC/CPC/profit/ROI
    7. Compute summary totals across all rows, then calculateDerived on summary
    8. Sort rows by requested sort field/direction
    9. Apply offset + limit pagination
    10. Build and return ReportResponse with rows, summary, and meta

    **Key implementation details**:
    - Composite key builder: join dimension values with `|` separator for map lookup
    - When device/os/browser are in group_by, conv stats merge at reduced key level
      (without device dims), so one conv stats row maps to multiple click stats rows
    - Handle zero-division safely in derived metrics
    - Sort comparison: support numeric fields (clicks, conversions, revenue, etc.) and string fields (dimensions)

    **Entity name enrichment** (optional, best-effort):
    - After building rows, batch-load campaign names from PostgreSQL cache for any campaign_id dimensions
    - Add "campaign_name", "offer_name", "stream_name" to Dimensions map
    - If PG is nil or query fails, skip enrichment (don't fail the report)

    Create `test/unit/analytics/service_test.go` with tests:
    - Derived metric calculation correctness (known inputs -> expected CR/EPC/ROI)
    - Zero-division safety (0 clicks, 0 cost)
    - Merge logic: click-only row, conversion-only row, merged row
    - Sort by clicks desc
    - Pagination (offset/limit)

    AVOID:
    - ClickHouse JOINs in the query layer.
    - Storing context in the Service struct.
    - Blocking on PostgreSQL for name enrichment (use short timeout).
  </action>
  <verify>go test -v ./test/unit/analytics/... && go build ./internal/analytics/...</verify>
  <done>Analytics service compiles, queries ClickHouse MVs, merges results in Go, and computes derived metrics. Unit tests pass.</done>
</task>

</tasks>

<verification>
After all tasks, verify:
- [ ] `internal/analytics/` package has models.go, query_builder.go, service.go.
- [ ] `go build ./internal/analytics/...` compiles cleanly.
- [ ] `go test ./test/unit/analytics/...` passes.
- [ ] Query builder generates parameterized SQL (no string interpolation of user values).
- [ ] Dimension whitelist prevents unknown dimensions.
- [ ] Date range heuristic selects hourly tables for <=2 day ranges.
- [ ] Derived metrics handle zero-division without panic.
- [ ] `go vet ./internal/analytics/...` reports no issues.
</verification>

<success_criteria>
- [ ] Analytics package is complete with query building, execution, merge, and derived metric computation.
- [ ] All SQL queries use parameterized values for injection safety.
- [ ] Click stats and conversion stats are queried and merged in Go (no ClickHouse JOIN).
- [ ] Unit tests validate query generation, metric computation, and merge logic.
</success_criteria>
