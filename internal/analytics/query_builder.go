package analytics

import (
	"errors"
	"fmt"
	"strings"
	"time"

	"github.com/google/uuid"
)

// DimensionDef defines a dimension's properties for query building.
type DimensionDef struct {
	Column      string // ClickHouse column expression
	Tables      string // "all", "clicks_only", "convs_only"
	DailyColumn string // Override for daily tables, empty = same as Column
}

// dimensionRegistry is a whitelist of allowed dimensions.
var dimensionRegistry = map[string]DimensionDef{
	"campaign": {Column: "campaign_id", Tables: "all"},
	"stream":   {Column: "stream_id", Tables: "all"},
	"offer":    {Column: "offer_id", Tables: "all"},
	"landing":  {Column: "landing_id", Tables: "clicks_only"},
	"country":  {Column: "country_code", Tables: "all"},
	"device":   {Column: "device_type", Tables: "clicks_only"},
	"os":       {Column: "os", Tables: "clicks_only"},
	"browser":  {Column: "browser", Tables: "clicks_only"},
	"day":      {Column: "toDate(hour)", Tables: "all", DailyColumn: "day"},
	"hour":     {Column: "hour", Tables: "all", DailyColumn: ""},
	"status":   {Column: "status", Tables: "convs_only"},
}

// QueryBuilder generates safe ClickHouse SQL for reports.
type QueryBuilder struct{}

// NewQueryBuilder creates a new QueryBuilder instance.
func NewQueryBuilder() *QueryBuilder {
	return &QueryBuilder{}
}

// BuildClickStatsQuery generates a parameterized SQL query for click statistics.
func (qb *QueryBuilder) BuildClickStatsQuery(q *ReportQuery) (string, []any, error) {
	if err := qb.validate(q); err != nil {
		return "", nil, err
	}

	table := qb.getClickTable(q)
	dims, cols := qb.getClickDimensions(q)

	var selectParts []string
	selectParts = append(selectParts, cols...)
	selectParts = append(selectParts,
		"sum(clicks) AS clicks",
		"sum(unique_clicks) AS unique_clicks",
		"sum(bots) AS bots",
		"sum(cost) AS cost",
		"sum(click_payout) AS click_payout",
	)

	var whereParts []string
	var args []any

	// Time range filter
	timeCol := "hour"
	if table == "stats_daily" {
		timeCol = "day"
	}
	whereParts = append(whereParts, fmt.Sprintf("%s >= ?", timeCol))
	args = append(args, q.DateFrom)
	whereParts = append(whereParts, fmt.Sprintf("%s < ?", timeCol))
	args = append(args, q.DateTo)

	// Dimension filters
	for dim, values := range q.Filters {
		def, ok := dimensionRegistry[dim]
		if !ok || def.Tables == "convs_only" {
			continue
		}

		col := def.Column
		if table == "stats_daily" && def.DailyColumn != "" {
			col = def.DailyColumn
		}

		if len(values) == 1 {
			whereParts = append(whereParts, fmt.Sprintf("%s = ?", col))
			args = append(args, values[0])
		} else if len(values) > 1 {
			placeholders := strings.Repeat("?, ", len(values)-1) + "?"
			whereParts = append(whereParts, fmt.Sprintf("%s IN (%s)", col, placeholders))
			for _, v := range values {
				args = append(args, v)
			}
		}
	}

	query := fmt.Sprintf("SELECT %s FROM %s WHERE %s",
		strings.Join(selectParts, ", "),
		table,
		strings.Join(whereParts, " AND "),
	)

	if len(dims) > 0 {
		query += " GROUP BY " + strings.Join(dims, ", ")
	}

	return query, args, nil
}

// BuildConvStatsQuery generates a parameterized SQL query for conversion statistics.
func (qb *QueryBuilder) BuildConvStatsQuery(q *ReportQuery) (string, []any, error) {
	if err := qb.validate(q); err != nil {
		return "", nil, err
	}

	table := qb.getConvTable(q)
	dims, cols := qb.getConvDimensions(q)

	var selectParts []string
	selectParts = append(selectParts, cols...)
	selectParts = append(selectParts,
		"sum(conversions) AS conversions",
		"sum(revenue) AS revenue",
		"sum(payout) AS payout",
	)

	var whereParts []string
	var args []any

	// Time range filter
	timeCol := "hour"
	if table == "conv_stats_daily" {
		timeCol = "day"
	}
	whereParts = append(whereParts, fmt.Sprintf("%s >= ?", timeCol))
	args = append(args, q.DateFrom)
	whereParts = append(whereParts, fmt.Sprintf("%s < ?", timeCol))
	args = append(args, q.DateTo)

	// Dimension filters
	for dim, values := range q.Filters {
		def, ok := dimensionRegistry[dim]
		if !ok || def.Tables == "clicks_only" {
			continue
		}

		col := def.Column
		if table == "conv_stats_daily" && def.DailyColumn != "" {
			col = def.DailyColumn
		}

		if len(values) == 1 {
			whereParts = append(whereParts, fmt.Sprintf("%s = ?", col))
			args = append(args, values[0])
		} else if len(values) > 1 {
			placeholders := strings.Repeat("?, ", len(values)-1) + "?"
			whereParts = append(whereParts, fmt.Sprintf("%s IN (%s)", col, placeholders))
			for _, v := range values {
				args = append(args, v)
			}
		}
	}

	query := fmt.Sprintf("SELECT %s FROM %s WHERE %s",
		strings.Join(selectParts, ", "),
		table,
		strings.Join(whereParts, " AND "),
	)

	if len(dims) > 0 {
		query += " GROUP BY " + strings.Join(dims, ", ")
	}

	return query, args, nil
}

func (qb *QueryBuilder) validate(q *ReportQuery) error {
	if q.DateFrom.IsZero() || q.DateTo.IsZero() {
		return errors.New("date range is required")
	}
	if q.DateFrom.After(q.DateTo) {
		return errors.New("start date cannot be after end date")
	}

	for _, dim := range q.GroupBy {
		if _, ok := dimensionRegistry[dim]; !ok {
			return fmt.Errorf("invalid group_by dimension: %s", dim)
		}
	}

	for dim, values := range q.Filters {
		if _, ok := dimensionRegistry[dim]; !ok {
			return fmt.Errorf("invalid filter dimension: %s", dim)
		}

		// Basic UUID validation for ID fields
		if strings.HasSuffix(dim, "_id") || dim == "campaign" || dim == "stream" || dim == "offer" || dim == "landing" {
			for _, v := range values {
				if _, err := uuid.Parse(v); err != nil {
					return fmt.Errorf("invalid UUID for dimension %s: %s", dim, v)
				}
			}
		}
	}

	if q.Limit > 1000 {
		return errors.New("limit cannot exceed 1000")
	}
	if q.Limit <= 0 {
		q.Limit = 50
	}

	return nil
}

func (qb *QueryBuilder) getClickTable(q *ReportQuery) string {
	// Use hourly if span <= 2 days OR "hour" is requested
	span := q.DateTo.Sub(q.DateFrom)
	for _, dim := range q.GroupBy {
		if dim == "hour" {
			return "stats_hourly"
		}
	}

	if span <= 48*time.Hour {
		return "stats_hourly"
	}
	return "stats_daily"
}

func (qb *QueryBuilder) getConvTable(q *ReportQuery) string {
	span := q.DateTo.Sub(q.DateFrom)
	for _, dim := range q.GroupBy {
		if dim == "hour" {
			return "conv_stats_hourly"
		}
	}

	if span <= 48*time.Hour {
		return "conv_stats_hourly"
	}
	return "conv_stats_daily"
}

func (qb *QueryBuilder) getClickDimensions(q *ReportQuery) (dims []string, cols []string) {
	table := qb.getClickTable(q)
	for _, dim := range q.GroupBy {
		def := dimensionRegistry[dim]
		if def.Tables == "convs_only" {
			continue
		}

		col := def.Column
		if table == "stats_daily" && def.DailyColumn != "" {
			col = def.DailyColumn
		}
		dims = append(dims, col)
		cols = append(cols, fmt.Sprintf("%s AS %s", col, dim))
	}
	return dims, cols
}

func (qb *QueryBuilder) getConvDimensions(q *ReportQuery) (dims []string, cols []string) {
	table := qb.getConvTable(q)
	for _, dim := range q.GroupBy {
		def := dimensionRegistry[dim]
		if def.Tables == "clicks_only" {
			continue
		}

		col := def.Column
		if table == "conv_stats_daily" && def.DailyColumn != "" {
			col = def.DailyColumn
		}
		dims = append(dims, col)
		cols = append(cols, fmt.Sprintf("%s AS %s", col, dim))
	}
	return dims, cols
}
