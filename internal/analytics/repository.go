package analytics

import (
	"context"
	"errors"
	"fmt"
	"strings"
	"time"

	"github.com/google/uuid"
)

const (
	defaultMetricsLimit   = 50
	maxMetricsLimit       = 1000
	maxMetricsWindow      = 90 * 24 * time.Hour
	maxHourlyMetricsRange = 7 * 24 * time.Hour
)

// ErrInvalidMetricsQuery marks validation failures for analytics metric requests.
var ErrInvalidMetricsQuery = errors.New("invalid analytics query")

// MetricsGranularity controls optional time bucketing for aggregate metrics.
type MetricsGranularity string

const (
	MetricsGranularityNone MetricsGranularity = ""
	MetricsGranularityDay  MetricsGranularity = "day"
	MetricsGranularityHour MetricsGranularity = "hour"
)

// CampaignMetricsQuery defines tenant-scoped campaign aggregate inputs.
type CampaignMetricsQuery struct {
	TenantID    string
	DateFrom    time.Time
	DateTo      time.Time
	Granularity MetricsGranularity
	CampaignIDs []string
	Limit       int
	Offset      int
}

// StreamMetricsQuery defines tenant-scoped stream aggregate inputs.
type StreamMetricsQuery struct {
	TenantID    string
	DateFrom    time.Time
	DateTo      time.Time
	Granularity MetricsGranularity
	CampaignIDs []string
	StreamIDs   []string
	Limit       int
	Offset      int
}

// TenantMetricsResponse is the stable payload for tenant-scoped metric endpoints.
type TenantMetricsResponse struct {
	TenantID string        `json:"tenant_id"`
	Scope    string        `json:"scope"`
	Rows     []ReportRow   `json:"rows"`
	Summary  ReportSummary `json:"summary"`
	Meta     ReportMeta    `json:"meta"`
}

// ReportRepository abstracts report generation for service-level unit tests.
type ReportRepository interface {
	GenerateReport(ctx context.Context, query *ReportQuery) (*ReportResponse, error)
}

type reportRepositoryFunc func(ctx context.Context, query *ReportQuery) (*ReportResponse, error)

func (f reportRepositoryFunc) GenerateReport(ctx context.Context, query *ReportQuery) (*ReportResponse, error) {
	return f(ctx, query)
}

func (q *CampaignMetricsQuery) Validate() error {
	if _, err := validateBaseMetricsQuery(
		q.TenantID,
		q.DateFrom,
		q.DateTo,
		q.Granularity,
		&q.Limit,
		&q.Offset,
	); err != nil {
		return err
	}

	if err := validateUUIDList("campaign_id", q.CampaignIDs); err != nil {
		return err
	}

	return nil
}

func (q *CampaignMetricsQuery) toReportQuery() *ReportQuery {
	groupBy := []string{"campaign"}
	if q.Granularity == MetricsGranularityDay || q.Granularity == MetricsGranularityHour {
		groupBy = append(groupBy, string(q.Granularity))
	}

	filters := map[string][]string{}
	if len(q.CampaignIDs) > 0 {
		filters["campaign"] = q.CampaignIDs
	}

	return &ReportQuery{
		GroupBy:   groupBy,
		DateFrom:  q.DateFrom,
		DateTo:    q.DateTo,
		Filters:   filters,
		SortField: "clicks",
		SortDir:   "desc",
		Limit:     q.Limit,
		Offset:    q.Offset,
	}
}

func (q *StreamMetricsQuery) Validate() error {
	if _, err := validateBaseMetricsQuery(
		q.TenantID,
		q.DateFrom,
		q.DateTo,
		q.Granularity,
		&q.Limit,
		&q.Offset,
	); err != nil {
		return err
	}

	if err := validateUUIDList("campaign_id", q.CampaignIDs); err != nil {
		return err
	}
	if err := validateUUIDList("stream_id", q.StreamIDs); err != nil {
		return err
	}

	if len(q.StreamIDs) > 0 && len(q.CampaignIDs) == 0 {
		return invalidMetricsErr("stream_id filter requires campaign_id filter")
	}

	return nil
}

func (q *StreamMetricsQuery) toReportQuery() *ReportQuery {
	groupBy := []string{"stream"}
	if q.Granularity == MetricsGranularityDay || q.Granularity == MetricsGranularityHour {
		groupBy = append(groupBy, string(q.Granularity))
	}

	filters := map[string][]string{}
	if len(q.CampaignIDs) > 0 {
		filters["campaign"] = q.CampaignIDs
	}
	if len(q.StreamIDs) > 0 {
		filters["stream"] = q.StreamIDs
	}

	return &ReportQuery{
		GroupBy:   groupBy,
		DateFrom:  q.DateFrom,
		DateTo:    q.DateTo,
		Filters:   filters,
		SortField: "clicks",
		SortDir:   "desc",
		Limit:     q.Limit,
		Offset:    q.Offset,
	}
}

func validateBaseMetricsQuery(
	tenantID string,
	dateFrom time.Time,
	dateTo time.Time,
	granularity MetricsGranularity,
	limit *int,
	offset *int,
) (time.Duration, error) {
	if strings.TrimSpace(tenantID) == "" {
		return 0, invalidMetricsErr("tenant_id is required")
	}

	if dateFrom.IsZero() || dateTo.IsZero() {
		return 0, invalidMetricsErr("date range is required")
	}
	if !dateFrom.Before(dateTo) {
		return 0, invalidMetricsErr("date_from must be before date_to")
	}

	span := dateTo.Sub(dateFrom)
	if span > maxMetricsWindow {
		return 0, invalidMetricsErr("date range cannot exceed %d days", int(maxMetricsWindow.Hours()/24))
	}

	switch granularity {
	case MetricsGranularityNone, MetricsGranularityDay:
	case MetricsGranularityHour:
		if span > maxHourlyMetricsRange {
			return 0, invalidMetricsErr("hour grouping supports a maximum %d-day range", int(maxHourlyMetricsRange.Hours()/24))
		}
	default:
		return 0, invalidMetricsErr("unsupported grouping %q", granularity)
	}

	if *limit <= 0 {
		*limit = defaultMetricsLimit
	}
	if *limit > maxMetricsLimit {
		*limit = maxMetricsLimit
	}

	if *offset < 0 {
		return 0, invalidMetricsErr("offset must be >= 0")
	}

	return span, nil
}

func validateUUIDList(name string, values []string) error {
	for _, v := range values {
		trimmed := strings.TrimSpace(v)
		if trimmed == "" {
			return invalidMetricsErr("%s cannot contain blank values", name)
		}
		if _, err := uuid.Parse(trimmed); err != nil {
			return invalidMetricsErr("invalid %s UUID: %s", name, trimmed)
		}
	}
	return nil
}

func invalidMetricsErr(format string, args ...any) error {
	return fmt.Errorf("%w: %s", ErrInvalidMetricsQuery, fmt.Sprintf(format, args...))
}
