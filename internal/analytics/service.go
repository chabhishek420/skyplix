package analytics

import (
	"context"
	"fmt"
	"sort"
	"strings"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/jackc/pgx/v5/pgxpool"
	"go.uber.org/zap"
	"golang.org/x/sync/errgroup"
)

// Service provides analytics reporting functionality.
type Service struct {
	ch     driver.Conn
	db     *pgxpool.Pool
	logger *zap.Logger
	qb     *QueryBuilder
}

// New creates a new analytics service.
func New(ch driver.Conn, db *pgxpool.Pool, logger *zap.Logger) *Service {
	return &Service{
		ch:     ch,
		db:     db,
		logger: logger,
		qb:     NewQueryBuilder(),
	}
}

// GenerateReport executes the reporting pipeline and returns aggregated results.
func (s *Service) GenerateReport(ctx context.Context, q *ReportQuery) (*ReportResponse, error) {
	// 1. Validate query (QueryBuilder validation is internal to Build methods, but we can call a standalone validation if needed)
	if q.Limit <= 0 {
		q.Limit = 50
	}

	// 2. Build queries in parallel
	var clickSQL, convSQL string
	var clickArgs, convArgs []any
	var err error

	clickSQL, clickArgs, err = s.qb.BuildClickStatsQuery(q)
	if err != nil {
		return nil, fmt.Errorf("build click stats query: %w", err)
	}

	convSQL, convArgs, err = s.qb.BuildConvStatsQuery(q)
	if err != nil {
		return nil, fmt.Errorf("build conv stats query: %w", err)
	}

	// 3. Execute queries concurrently
	var clickRows, convRows []ReportRow
	g, ctx := errgroup.WithContext(ctx)
	// Add timeout to context
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	g.Go(func() error {
		rows, err := s.ch.Query(ctx, clickSQL, clickArgs...)
		if err != nil {
			return fmt.Errorf("execute click stats query: %w", err)
		}
		defer rows.Close()

		for rows.Next() {
			row := ReportRow{Dimensions: make(map[string]string)}
			var dest []any
			for _, dim := range q.GroupBy {
				if dimensionRegistry[dim].Tables == "convs_only" {
					continue
				}
				var val any
				dest = append(dest, &val)
			}
			dest = append(dest, &row.Clicks, &row.UniqueClicks, &row.Bots, &row.Cost, &row.CPC) // click_payout as CPC temporarily

			if err := rows.Scan(dest...); err != nil {
				return fmt.Errorf("scan click stats row: %w", err)
			}

			// Map dimensions back from any to string
			idx := 0
			for _, dim := range q.GroupBy {
				if dimensionRegistry[dim].Tables == "convs_only" {
					continue
				}
				val := *(dest[idx].(*any))
				row.Dimensions[dim] = fmt.Sprintf("%v", val)
				idx++
			}
			clickRows = append(clickRows, row)
		}
		return nil
	})

	g.Go(func() error {
		rows, err := s.ch.Query(ctx, convSQL, convArgs...)
		if err != nil {
			return fmt.Errorf("execute conv stats query: %w", err)
		}
		defer rows.Close()

		for rows.Next() {
			row := ReportRow{Dimensions: make(map[string]string)}
			var dest []any
			for _, dim := range q.GroupBy {
				if dimensionRegistry[dim].Tables == "clicks_only" {
					continue
				}
				var val any
				dest = append(dest, &val)
			}
			dest = append(dest, &row.Conversions, &row.Revenue, &row.ROI) // payout as ROI temporarily

			if err := rows.Scan(dest...); err != nil {
				return fmt.Errorf("scan conv stats row: %w", err)
			}

			idx := 0
			for _, dim := range q.GroupBy {
				if dimensionRegistry[dim].Tables == "clicks_only" {
					continue
				}
				val := *(dest[idx].(*any))
				row.Dimensions[dim] = fmt.Sprintf("%v", val)
				idx++
			}
			convRows = append(convRows, row)
		}
		return nil
	})

	if err := g.Wait(); err != nil {
		return nil, err
	}

	// 4. Merge results by grouping key
	merged := make(map[string]*ReportRow)

	for i := range clickRows {
		key := s.buildKey(clickRows[i].Dimensions, q.GroupBy)
		merged[key] = &clickRows[i]
	}

	for i := range convRows {
		// When device/os/browser are present, convRows will have fewer dimensions.
		// We need to merge them into all clickRows that match the available dimensions.
		key := s.buildKey(convRows[i].Dimensions, q.GroupBy)

		if row, ok := merged[key]; ok {
			row.Conversions += convRows[i].Conversions
			row.Revenue += convRows[i].Revenue
		} else {
			// Find all rows in merged that start with this key (if device dimensions follow)
			// For simplicity and performance, if exact key not found, we add it as a new row
			// unless we implement more complex prefix matching.
			merged[key] = &convRows[i]
		}
	}

	// 5. Compute derived metrics and summary
	rows := make([]ReportRow, 0, len(merged))
	summary := ReportSummary{}

	for _, row := range merged {
		row.CalculateDerived()
		rows = append(rows, *row)

		summary.Clicks += row.Clicks
		summary.UniqueClicks += row.UniqueClicks
		summary.Bots += row.Bots
		summary.Conversions += row.Conversions
		summary.Revenue += row.Revenue
		summary.Cost += row.Cost
	}
	summary.CalculateDerived()

	// 6. Enrichment (Entity names from PG) - best effort
	s.enrichEntityNames(ctx, rows, q.GroupBy)

	// 7. Sort
	s.sortRows(rows, q.SortField, q.SortDir)

	// 8. Paginate
	total := len(rows)
	start := q.Offset
	if start > total {
		start = total
	}
	end := start + q.Limit
	if end > total {
		end = total
	}

	paginatedRows := rows[start:end]

	return &ReportResponse{
		Rows:    paginatedRows,
		Summary: summary,
		Meta: ReportMeta{
			DateFrom:  q.DateFrom.Format("2006-01-02"),
			DateTo:    q.DateTo.Format("2006-01-02"),
			GroupBy:   q.GroupBy,
			TotalRows: total,
			Limit:     q.Limit,
			Offset:    q.Offset,
		},
	}, nil
}

func (s *Service) buildKey(dims map[string]string, groupBy []string) string {
	var parts []string
	for _, dim := range groupBy {
		if val, ok := dims[dim]; ok {
			parts = append(parts, val)
		} else {
			parts = append(parts, "") // Placeholder for missing dimensions in one of the tables
		}
	}
	return strings.Join(parts, "|")
}

func (s *Service) sortRows(rows []ReportRow, field, dir string) {
	if field == "" {
		field = "clicks"
	}
	if dir == "" {
		dir = "desc"
	}

	sort.Slice(rows, func(i, j int) bool {
		var valI, valJ any
		switch field {
		case "clicks":
			valI, valJ = rows[i].Clicks, rows[j].Clicks
		case "conversions":
			valI, valJ = rows[i].Conversions, rows[j].Conversions
		case "revenue":
			valI, valJ = rows[i].Revenue, rows[j].Revenue
		case "cost":
			valI, valJ = rows[i].Cost, rows[j].Cost
		case "profit":
			valI, valJ = rows[i].Profit, rows[j].Profit
		case "cr":
			valI, valJ = rows[i].CR, rows[j].CR
		case "roi":
			valI, valJ = rows[i].ROI, rows[j].ROI
		case "epc":
			valI, valJ = rows[i].EPC, rows[j].EPC
		case "cpc":
			valI, valJ = rows[i].CPC, rows[j].CPC
		default:
			// Sort by dimension
			valI, valJ = rows[i].Dimensions[field], rows[j].Dimensions[field]
		}

		res := false
		switch vI := valI.(type) {
		case uint64:
			res = vI < valJ.(uint64)
		case float64:
			res = vI < valJ.(float64)
		case string:
			res = vI < valJ.(string)
		}

		if dir == "desc" {
			return !res
		}
		return res
	})
}

func (s *Service) enrichEntityNames(ctx context.Context, rows []ReportRow, groupBy []string) {
	if s.db == nil {
		return
	}

	for _, dim := range groupBy {
		table := ""
		switch dim {
		case "campaign":
			table = "campaigns"
		case "offer":
			table = "offers"
		case "stream":
			table = "streams"
		case "landing":
			table = "landings"
		case "source":
			table = "sources"
		case "network":
			table = "affiliate_networks"
		}

		if table == "" {
			continue
		}

		// Collect IDs
		idsMap := make(map[string]bool)
		for i := range rows {
			if id := rows[i].Dimensions[dim]; id != "" {
				idsMap[id] = true
			}
		}

		if len(idsMap) == 0 {
			continue
		}

		var ids []string
		for id := range idsMap {
			ids = append(ids, id)
		}

		// Batch load names
		names := make(map[string]string)
		query := fmt.Sprintf("SELECT id, name FROM %s WHERE id = ANY($1)", table)

		// Short timeout for enrichment
		enrichCtx, cancel := context.WithTimeout(ctx, 1*time.Second)
		dbRows, err := s.db.Query(enrichCtx, query, ids)
		cancel()

		if err == nil {
			for dbRows.Next() {
				var id, name string
				if err := dbRows.Scan(&id, &name); err == nil {
					names[id] = name
				}
			}
			dbRows.Close()

			// Apply names
			nameKey := dim + "_name"
			for i := range rows {
				id := rows[i].Dimensions[dim]
				if name, ok := names[id]; ok {
					rows[i].Dimensions[nameKey] = name
				}
			}
		}
	}
}

// GetClicksLog returns raw click logs with pagination.
func (s *Service) GetClicksLog(ctx context.Context, q *ReportQuery) (*LogResponse, error) {
	// 1. Build Query
	var whereParts []string
	var args []any

	whereParts = append(whereParts, "created_at >= ?")
	args = append(args, q.DateFrom)
	whereParts = append(whereParts, "created_at < ?")
	args = append(args, q.DateTo)

	for dim, values := range q.Filters {
		def, ok := dimensionRegistry[dim]
		if !ok || def.Tables == "convs_only" {
			continue
		}
		if len(values) == 1 {
			whereParts = append(whereParts, fmt.Sprintf("%s = ?", def.Column))
			args = append(args, values[0])
		} else if len(values) > 1 {
			placeholders := strings.Repeat("?, ", len(values)-1) + "?"
			whereParts = append(whereParts, fmt.Sprintf("%s IN (%s)", def.Column, placeholders))
			for _, v := range values {
				args = append(args, v)
			}
		}
	}

	query := fmt.Sprintf("SELECT * FROM clicks WHERE %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
		strings.Join(whereParts, " AND "), q.Limit, q.Offset)

	// 2. Execute
	rows, err := s.ch.Query(ctx, query, args...)
	if err != nil {
		return nil, fmt.Errorf("execute clicks log query: %w", err)
	}
	defer rows.Close()

	var result []map[string]any
	columns := rows.ColumnTypes()

	for rows.Next() {
		row := make(map[string]any)
		dest := make([]any, len(columns))
		for i := range dest {
			var val any
			dest[i] = &val
		}

		if err := rows.Scan(dest...); err != nil {
			return nil, fmt.Errorf("scan click log row: %w", err)
		}

		for i, col := range columns {
			val := *(dest[i].(*any))
			row[col.Name()] = val
		}
		result = append(result, row)
	}

	return &LogResponse{
		Rows:   result,
		Limit:  q.Limit,
		Offset: q.Offset,
		Total:  0, // Total count omitted for performance in ClickHouse raw query
	}, nil
}

// GetConversionsLog returns raw conversion logs with pagination.
func (s *Service) GetConversionsLog(ctx context.Context, q *ReportQuery) (*LogResponse, error) {
	var whereParts []string
	var args []any

	whereParts = append(whereParts, "created_at >= ?")
	args = append(args, q.DateFrom)
	whereParts = append(whereParts, "created_at < ?")
	args = append(args, q.DateTo)

	for dim, values := range q.Filters {
		def, ok := dimensionRegistry[dim]
		if !ok || def.Tables == "clicks_only" {
			continue
		}
		if len(values) == 1 {
			whereParts = append(whereParts, fmt.Sprintf("%s = ?", def.Column))
			args = append(args, values[0])
		} else if len(values) > 1 {
			placeholders := strings.Repeat("?, ", len(values)-1) + "?"
			whereParts = append(whereParts, fmt.Sprintf("%s IN (%s)", def.Column, placeholders))
			for _, v := range values {
				args = append(args, v)
			}
		}
	}

	query := fmt.Sprintf("SELECT * FROM conversions WHERE %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
		strings.Join(whereParts, " AND "), q.Limit, q.Offset)

	rows, err := s.ch.Query(ctx, query, args...)
	if err != nil {
		return nil, fmt.Errorf("execute conversions log query: %w", err)
	}
	defer rows.Close()

	var result []map[string]any
	columns := rows.ColumnTypes()

	for rows.Next() {
		row := make(map[string]any)
		dest := make([]any, len(columns))
		for i := range dest {
			var val any
			dest[i] = &val
		}

		if err := rows.Scan(dest...); err != nil {
			return nil, fmt.Errorf("scan conversion log row: %w", err)
		}

		for i, col := range columns {
			val := *(dest[i].(*any))
			row[col.Name()] = val
		}
		result = append(result, row)
	}

	return &LogResponse{
		Rows:   result,
		Limit:  q.Limit,
		Offset: q.Offset,
		Total:  0,
	}, nil
}

// StreamPerformance holds raw metrics used for optimization.
type StreamPerformance struct {
	StreamID    string
	Clicks      uint64
	Conversions uint64
	Revenue     float64
	EPC         float64
	CR          float64
}

// GetStreamPerformance returns performance metrics for all streams of a campaign in a time window.
func (s *Service) GetStreamPerformance(ctx context.Context, campaignID string, window time.Duration) (map[string]StreamPerformance, error) {
	dateFrom := time.Now().Add(-window)

	// Query stats_hourly for efficiency
	query := `
		SELECT
			toString(stream_id),
			sum(clicks) as clicks,
			sum(unique_clicks) as unique_clicks
		FROM stats_hourly
		WHERE campaign_id = ? AND hour >= ?
		GROUP BY stream_id
	`
	rows, err := s.ch.Query(ctx, query, campaignID, dateFrom)
	if err != nil {
		return nil, fmt.Errorf("query stream clicks: %w", err)
	}
	defer rows.Close()

	perfMap := make(map[string]StreamPerformance)
	for rows.Next() {
		var id string
		var clicks, unique uint64
		if err := rows.Scan(&id, &clicks, &unique); err != nil {
			return nil, err
		}
		perfMap[id] = StreamPerformance{
			StreamID: id,
			Clicks:   clicks,
		}
	}

	// Query conversions
	convQuery := `
		SELECT
			toString(stream_id),
			count() as conversions,
			sum(revenue) as revenue
		FROM conv_stats_hourly
		WHERE campaign_id = ? AND hour >= ?
		GROUP BY stream_id
	`
	crows, err := s.ch.Query(ctx, convQuery, campaignID, dateFrom)
	if err != nil {
		return nil, fmt.Errorf("query stream convs: %w", err)
	}
	defer crows.Close()

	for crows.Next() {
		var id string
		var convs uint64
		var rev float64
		if err := crows.Scan(&id, &convs, &rev); err != nil {
			return nil, err
		}
		p := perfMap[id]
		p.Conversions = convs
		p.Revenue = rev

		if p.Clicks > 0 {
			p.EPC = rev / float64(p.Clicks)
			p.CR = float64(convs) / float64(p.Clicks) * 100
		}
		perfMap[id] = p
	}

	return perfMap, nil
}
