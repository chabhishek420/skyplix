package analytics_test

import (
	"context"
	"testing"
	"time"

	"github.com/google/uuid"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/analytics"
)

type fakeReportRepository struct {
	report   *analytics.ReportResponse
	err      error
	called   int
	captured []*analytics.ReportQuery
}

func (f *fakeReportRepository) GenerateReport(_ context.Context, query *analytics.ReportQuery) (*analytics.ReportResponse, error) {
	f.called++

	copied := &analytics.ReportQuery{
		GroupBy:   append([]string(nil), query.GroupBy...),
		DateFrom:  query.DateFrom,
		DateTo:    query.DateTo,
		Filters:   copyFilters(query.Filters),
		SortField: query.SortField,
		SortDir:   query.SortDir,
		Limit:     query.Limit,
		Offset:    query.Offset,
	}
	f.captured = append(f.captured, copied)

	if f.err != nil {
		return nil, f.err
	}
	if f.report != nil {
		return f.report, nil
	}
	return &analytics.ReportResponse{}, nil
}

func copyFilters(source map[string][]string) map[string][]string {
	if source == nil {
		return nil
	}

	result := make(map[string][]string, len(source))
	for key, values := range source {
		result[key] = append([]string(nil), values...)
	}
	return result
}

func TestService_GetCampaignMetrics_ValidQuery(t *testing.T) {
	campaignID := uuid.New().String()
	repo := &fakeReportRepository{
		report: &analytics.ReportResponse{
			Rows: []analytics.ReportRow{
				{Dimensions: map[string]string{"campaign": campaignID, "day": "2026-04-10"}, Clicks: 42, Conversions: 4},
			},
			Summary: analytics.ReportSummary{Clicks: 42, Conversions: 4},
			Meta: analytics.ReportMeta{
				DateFrom:  "2026-04-10",
				DateTo:    "2026-04-11",
				GroupBy:   []string{"campaign", "day"},
				TotalRows: 1,
				Limit:     25,
				Offset:    5,
			},
		},
	}

	svc := analytics.NewWithReportRepository(nil, nil, zap.NewNop(), repo)
	query := analytics.CampaignMetricsQuery{
		TenantID:    "tenant-a",
		DateFrom:    time.Date(2026, 4, 10, 0, 0, 0, 0, time.UTC),
		DateTo:      time.Date(2026, 4, 11, 0, 0, 0, 0, time.UTC),
		Granularity: analytics.MetricsGranularityDay,
		CampaignIDs: []string{campaignID},
		Limit:       25,
		Offset:      5,
	}

	result, err := svc.GetCampaignMetrics(context.Background(), query)
	require.NoError(t, err)
	require.NotNil(t, result)

	assert.Equal(t, "tenant-a", result.TenantID)
	assert.Equal(t, "campaign", result.Scope)
	assert.Len(t, result.Rows, 1)
	assert.Equal(t, uint64(42), result.Summary.Clicks)

	require.Equal(t, 1, repo.called)
	require.Len(t, repo.captured, 1)
	captured := repo.captured[0]
	assert.Equal(t, []string{"campaign", "day"}, captured.GroupBy)
	assert.Equal(t, []string{campaignID}, captured.Filters["campaign"])
	assert.Equal(t, 25, captured.Limit)
	assert.Equal(t, 5, captured.Offset)
}

func TestService_GetStreamMetrics_EmptyResultSet(t *testing.T) {
	repo := &fakeReportRepository{
		report: &analytics.ReportResponse{
			Rows:    []analytics.ReportRow{},
			Summary: analytics.ReportSummary{},
			Meta: analytics.ReportMeta{
				DateFrom:  "2026-04-10",
				DateTo:    "2026-04-11",
				GroupBy:   []string{"stream"},
				TotalRows: 0,
				Limit:     50,
				Offset:    0,
			},
		},
	}

	svc := analytics.NewWithReportRepository(nil, nil, zap.NewNop(), repo)
	query := analytics.StreamMetricsQuery{
		TenantID: "tenant-a",
		DateFrom: time.Date(2026, 4, 10, 0, 0, 0, 0, time.UTC),
		DateTo:   time.Date(2026, 4, 11, 0, 0, 0, 0, time.UTC),
	}

	result, err := svc.GetStreamMetrics(context.Background(), query)
	require.NoError(t, err)
	require.NotNil(t, result)

	assert.Equal(t, "tenant-a", result.TenantID)
	assert.Equal(t, "stream", result.Scope)
	assert.Empty(t, result.Rows)
	assert.Equal(t, 0, result.Meta.TotalRows)
	assert.Equal(t, 1, repo.called)
}

func TestService_GetStreamMetrics_InvalidFilterCombination(t *testing.T) {
	repo := &fakeReportRepository{}
	svc := analytics.NewWithReportRepository(nil, nil, zap.NewNop(), repo)

	query := analytics.StreamMetricsQuery{
		TenantID:  "tenant-a",
		DateFrom:  time.Date(2026, 4, 10, 0, 0, 0, 0, time.UTC),
		DateTo:    time.Date(2026, 4, 11, 0, 0, 0, 0, time.UTC),
		StreamIDs: []string{uuid.New().String()},
	}

	result, err := svc.GetStreamMetrics(context.Background(), query)
	require.Error(t, err)
	assert.Nil(t, result)
	assert.ErrorIs(t, err, analytics.ErrInvalidMetricsQuery)
	assert.Equal(t, 0, repo.called)
}
