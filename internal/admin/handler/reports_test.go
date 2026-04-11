package handler

import (
	"context"
	"fmt"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/google/uuid"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/analytics"
)

type fakeAnalyticsService struct {
	generateReportResp  *analytics.ReportResponse
	generateReportErr   error
	generateReportQuery *analytics.ReportQuery

	campaignResp  *analytics.TenantMetricsResponse
	campaignErr   error
	campaignQuery *analytics.CampaignMetricsQuery

	streamResp  *analytics.TenantMetricsResponse
	streamErr   error
	streamQuery *analytics.StreamMetricsQuery
}

func (f *fakeAnalyticsService) GenerateReport(_ context.Context, query *analytics.ReportQuery) (*analytics.ReportResponse, error) {
	f.generateReportQuery = query
	if f.generateReportErr != nil {
		return nil, f.generateReportErr
	}
	if f.generateReportResp != nil {
		return f.generateReportResp, nil
	}
	return &analytics.ReportResponse{}, nil
}

func (f *fakeAnalyticsService) GetClicksLog(_ context.Context, _ *analytics.ReportQuery) (*analytics.LogResponse, error) {
	return &analytics.LogResponse{}, nil
}

func (f *fakeAnalyticsService) GetConversionsLog(_ context.Context, _ *analytics.ReportQuery) (*analytics.LogResponse, error) {
	return &analytics.LogResponse{}, nil
}

func (f *fakeAnalyticsService) GetCampaignMetrics(_ context.Context, query analytics.CampaignMetricsQuery) (*analytics.TenantMetricsResponse, error) {
	copied := query
	f.campaignQuery = &copied
	if f.campaignErr != nil {
		return nil, f.campaignErr
	}
	if f.campaignResp != nil {
		return f.campaignResp, nil
	}
	return &analytics.TenantMetricsResponse{}, nil
}

func (f *fakeAnalyticsService) GetStreamMetrics(_ context.Context, query analytics.StreamMetricsQuery) (*analytics.TenantMetricsResponse, error) {
	copied := query
	f.streamQuery = &copied
	if f.streamErr != nil {
		return nil, f.streamErr
	}
	if f.streamResp != nil {
		return f.streamResp, nil
	}
	return &analytics.TenantMetricsResponse{}, nil
}

func TestReportsHandler_HandleCampaignMetrics_RequiresTenantContext(t *testing.T) {
	svc := &fakeAnalyticsService{}
	h := NewReportsHandler(zap.NewNop(), svc)

	req := httptest.NewRequest(http.MethodGet, "/api/v1/reports/campaigns", nil)
	resp := httptest.NewRecorder()

	h.HandleCampaignMetrics(resp, req)

	require.Equal(t, http.StatusBadRequest, resp.Code)
	assert.Contains(t, resp.Body.String(), "missing tenant context")
	assert.Nil(t, svc.campaignQuery)
}

func TestReportsHandler_HandleStreamMetrics_PassesTenantScopedQuery(t *testing.T) {
	campaignID := uuid.New().String()
	streamID := uuid.New().String()

	svc := &fakeAnalyticsService{
		streamResp: &analytics.TenantMetricsResponse{TenantID: "tenant-a", Scope: "stream"},
	}
	h := NewReportsHandler(zap.NewNop(), svc)

	url := fmt.Sprintf(
		"/api/v1/reports/streams?date_from=2026-04-10&date_to=2026-04-11&campaign_id=%s&stream_id=%s&group_by=day&limit=10&offset=2",
		campaignID,
		streamID,
	)
	req := httptest.NewRequest(http.MethodGet, url, nil)
	req.Header.Set("X-Tenant-ID", "tenant-a")
	resp := httptest.NewRecorder()

	h.HandleStreamMetrics(resp, req)

	require.Equal(t, http.StatusOK, resp.Code)
	require.NotNil(t, svc.streamQuery)

	assert.Equal(t, "tenant-a", svc.streamQuery.TenantID)
	assert.Equal(t, analytics.MetricsGranularityDay, svc.streamQuery.Granularity)
	assert.Equal(t, []string{campaignID}, svc.streamQuery.CampaignIDs)
	assert.Equal(t, []string{streamID}, svc.streamQuery.StreamIDs)
	assert.Equal(t, 10, svc.streamQuery.Limit)
	assert.Equal(t, 2, svc.streamQuery.Offset)
}

func TestReportsHandler_HandleReport_UsesCanonicalFilterKeys(t *testing.T) {
	campaignID := uuid.New().String()
	streamID := uuid.New().String()

	svc := &fakeAnalyticsService{generateReportResp: &analytics.ReportResponse{}}
	h := NewReportsHandler(zap.NewNop(), svc)

	url := fmt.Sprintf(
		"/api/v1/reports?campaign_id=%s&stream_id=%s&country=US&device_type=mobile&group_by=campaign_id,stream_id",
		campaignID,
		streamID,
	)
	req := httptest.NewRequest(http.MethodGet, url, nil)
	resp := httptest.NewRecorder()

	h.HandleReport(resp, req)

	require.Equal(t, http.StatusOK, resp.Code)
	require.NotNil(t, svc.generateReportQuery)

	assert.Equal(t, []string{"campaign", "stream"}, svc.generateReportQuery.GroupBy)
	assert.Equal(t, []string{campaignID}, svc.generateReportQuery.Filters["campaign"])
	assert.Equal(t, []string{streamID}, svc.generateReportQuery.Filters["stream"])
	assert.Equal(t, []string{"US"}, svc.generateReportQuery.Filters["country"])
	assert.Equal(t, []string{"mobile"}, svc.generateReportQuery.Filters["device"])
	assert.NotContains(t, svc.generateReportQuery.Filters, "campaign_id")
	assert.NotContains(t, svc.generateReportQuery.Filters, "stream_id")
}
