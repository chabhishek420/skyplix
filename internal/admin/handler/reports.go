package handler

import (
	"context"
	"errors"
	"fmt"
	"net/http"
	"net/url"
	"strconv"
	"strings"
	"time"

	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/analytics"
	"github.com/skyplix/zai-tds/internal/auth"
)

const tenantIDHeader = "X-Tenant-ID"

type analyticsService interface {
	GenerateReport(ctx context.Context, query *analytics.ReportQuery) (*analytics.ReportResponse, error)
	GetClicksLog(ctx context.Context, query *analytics.ReportQuery) (*analytics.LogResponse, error)
	GetConversionsLog(ctx context.Context, query *analytics.ReportQuery) (*analytics.LogResponse, error)
	GetCampaignMetrics(ctx context.Context, query analytics.CampaignMetricsQuery) (*analytics.TenantMetricsResponse, error)
	GetStreamMetrics(ctx context.Context, query analytics.StreamMetricsQuery) (*analytics.TenantMetricsResponse, error)
}

// ReportsHandler handles reporting API requests.
type ReportsHandler struct {
	logger    *zap.Logger
	analytics analyticsService
}

// NewReportsHandler creates a new reports handler.
func NewReportsHandler(logger *zap.Logger, analyticsSvc analyticsService) *ReportsHandler {
	return &ReportsHandler{
		logger:    logger,
		analytics: analyticsSvc,
	}
}

// HandleReport handles the report generation request.
// GET /api/v1/reports
func (h *ReportsHandler) HandleReport(w http.ResponseWriter, r *http.Request) {
	query, err := h.parseQuery(r)
	if err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	report, err := h.analytics.GenerateReport(r.Context(), query)
	if err != nil {
		h.logger.Error("failed to generate report", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to generate report")
		return
	}

	h.respondJSON(w, http.StatusOK, report)
}

// HandleCampaignMetrics handles tenant-scoped campaign aggregates.
// GET /api/v1/reports/campaigns
func (h *ReportsHandler) HandleCampaignMetrics(w http.ResponseWriter, r *http.Request) {
	query, err := h.parseCampaignMetricsQuery(r)
	if err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	result, err := h.analytics.GetCampaignMetrics(r.Context(), query)
	if err != nil {
		if errors.Is(err, analytics.ErrInvalidMetricsQuery) {
			h.respondError(w, http.StatusBadRequest, err.Error())
			return
		}
		h.logger.Error("failed to get campaign metrics", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to get campaign metrics")
		return
	}

	h.respondJSON(w, http.StatusOK, result)
}

// HandleStreamMetrics handles tenant-scoped stream aggregates.
// GET /api/v1/reports/streams
func (h *ReportsHandler) HandleStreamMetrics(w http.ResponseWriter, r *http.Request) {
	query, err := h.parseStreamMetricsQuery(r)
	if err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	result, err := h.analytics.GetStreamMetrics(r.Context(), query)
	if err != nil {
		if errors.Is(err, analytics.ErrInvalidMetricsQuery) {
			h.respondError(w, http.StatusBadRequest, err.Error())
			return
		}
		h.logger.Error("failed to get stream metrics", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to get stream metrics")
		return
	}

	h.respondJSON(w, http.StatusOK, result)
}

// HandleClicksLog handles the raw clicks log request.
// GET /api/v1/logs/clicks
func (h *ReportsHandler) HandleClicksLog(w http.ResponseWriter, r *http.Request) {
	query, err := h.parseQuery(r)
	if err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	logs, err := h.analytics.GetClicksLog(r.Context(), query)
	if err != nil {
		h.logger.Error("failed to get clicks log", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to get clicks log")
		return
	}

	h.respondJSON(w, http.StatusOK, logs)
}

// HandleConversionsLog handles the raw conversions log request.
// GET /api/v1/logs/conversions
func (h *ReportsHandler) HandleConversionsLog(w http.ResponseWriter, r *http.Request) {
	query, err := h.parseQuery(r)
	if err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	logs, err := h.analytics.GetConversionsLog(r.Context(), query)
	if err != nil {
		h.logger.Error("failed to get conversions log", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to get conversions log")
		return
	}

	h.respondJSON(w, http.StatusOK, logs)
}

func (h *ReportsHandler) parseQuery(r *http.Request) (*analytics.ReportQuery, error) {
	q := r.URL.Query()

	dateFrom, dateTo, err := parseDateRange(q)
	if err != nil {
		return nil, err
	}

	query := &analytics.ReportQuery{
		DateFrom: dateFrom,
		DateTo:   dateTo,
		Filters:  make(map[string][]string),
	}

	if groupBy := q.Get("group_by"); groupBy != "" {
		rawDimensions := parseCommaSeparated(groupBy)
		query.GroupBy = make([]string, 0, len(rawDimensions))
		for _, dim := range rawDimensions {
			query.GroupBy = append(query.GroupBy, normalizeReportDimension(dim))
		}
	}

	if campaigns := firstNonEmptyValue(q.Get("campaign"), q.Get("campaign_id")); campaigns != "" {
		ids, err := parseUUIDList(campaigns, "campaign_id")
		if err != nil {
			return nil, err
		}
		query.Filters["campaign"] = ids
	}

	if countries := firstNonEmptyValue(q.Get("country"), q.Get("country_code")); countries != "" {
		query.Filters["country"] = parseCommaSeparated(countries)
	}

	if devices := firstNonEmptyValue(q.Get("device"), q.Get("device_type")); devices != "" {
		query.Filters["device"] = parseCommaSeparated(devices)
	}

	if streams := firstNonEmptyValue(q.Get("stream"), q.Get("stream_id")); streams != "" {
		ids, err := parseUUIDList(streams, "stream_id")
		if err != nil {
			return nil, err
		}
		query.Filters["stream"] = ids
	}

	if offers := firstNonEmptyValue(q.Get("offer"), q.Get("offer_id")); offers != "" {
		ids, err := parseUUIDList(offers, "offer_id")
		if err != nil {
			return nil, err
		}
		query.Filters["offer"] = ids
	}

	sortStr := q.Get("sort")
	if sortStr == "" {
		sortStr = "clicks:desc"
	}
	field, dir := parseSort(sortStr)
	query.SortField = normalizeReportDimension(field)
	query.SortDir = dir

	limit, offset, err := parsePagination(q, 50)
	if err != nil {
		return nil, err
	}
	query.Limit = limit
	query.Offset = offset

	return query, nil
}

func (h *ReportsHandler) parseCampaignMetricsQuery(r *http.Request) (analytics.CampaignMetricsQuery, error) {
	q := r.URL.Query()

	tenantID, err := resolveTenantID(r)
	if err != nil {
		return analytics.CampaignMetricsQuery{}, err
	}

	dateFrom, dateTo, err := parseDateRange(q)
	if err != nil {
		return analytics.CampaignMetricsQuery{}, err
	}

	campaignIDs, err := parseUUIDList(firstNonEmptyValue(q.Get("campaign_id"), q.Get("campaign")), "campaign_id")
	if err != nil {
		return analytics.CampaignMetricsQuery{}, err
	}

	limit, offset, err := parsePagination(q, 50)
	if err != nil {
		return analytics.CampaignMetricsQuery{}, err
	}

	granularity := parseMetricsGranularity(firstNonEmptyValue(q.Get("group_by"), q.Get("granularity")))

	return analytics.CampaignMetricsQuery{
		TenantID:    tenantID,
		DateFrom:    dateFrom,
		DateTo:      dateTo,
		Granularity: granularity,
		CampaignIDs: campaignIDs,
		Limit:       limit,
		Offset:      offset,
	}, nil
}

func (h *ReportsHandler) parseStreamMetricsQuery(r *http.Request) (analytics.StreamMetricsQuery, error) {
	q := r.URL.Query()

	tenantID, err := resolveTenantID(r)
	if err != nil {
		return analytics.StreamMetricsQuery{}, err
	}

	dateFrom, dateTo, err := parseDateRange(q)
	if err != nil {
		return analytics.StreamMetricsQuery{}, err
	}

	campaignIDs, err := parseUUIDList(firstNonEmptyValue(q.Get("campaign_id"), q.Get("campaign")), "campaign_id")
	if err != nil {
		return analytics.StreamMetricsQuery{}, err
	}
	streamIDs, err := parseUUIDList(firstNonEmptyValue(q.Get("stream_id"), q.Get("stream")), "stream_id")
	if err != nil {
		return analytics.StreamMetricsQuery{}, err
	}

	limit, offset, err := parsePagination(q, 50)
	if err != nil {
		return analytics.StreamMetricsQuery{}, err
	}

	granularity := parseMetricsGranularity(firstNonEmptyValue(q.Get("group_by"), q.Get("granularity")))

	return analytics.StreamMetricsQuery{
		TenantID:    tenantID,
		DateFrom:    dateFrom,
		DateTo:      dateTo,
		Granularity: granularity,
		CampaignIDs: campaignIDs,
		StreamIDs:   streamIDs,
		Limit:       limit,
		Offset:      offset,
	}, nil
}

// Helpers

func parseCommaSeparated(s string) []string {
	parts := strings.Split(s, ",")
	var result []string
	for _, p := range parts {
		if p = strings.TrimSpace(p); p != "" {
			result = append(result, p)
		}
	}
	return result
}

func parseDate(s string) (time.Time, error) {
	return time.Parse("2006-01-02", s)
}

func resolvePreset(preset string) (time.Time, time.Time, error) {
	now := time.Now().UTC()
	today := time.Date(now.Year(), now.Month(), now.Day(), 0, 0, 0, 0, time.UTC)
	endOfToday := time.Date(now.Year(), now.Month(), now.Day(), 23, 59, 59, 999999999, time.UTC)

	switch preset {
	case "today":
		return today, endOfToday, nil
	case "yesterday":
		yesterday := today.AddDate(0, 0, -1)
		endOfYesterday := time.Date(yesterday.Year(), yesterday.Month(), yesterday.Day(), 23, 59, 59, 999999999, time.UTC)
		return yesterday, endOfYesterday, nil
	case "last_7d":
		return today.AddDate(0, 0, -6), endOfToday, nil
	case "last_30d":
		return today.AddDate(0, 0, -29), endOfToday, nil
	case "this_month":
		firstOfMonth := time.Date(now.Year(), now.Month(), 1, 0, 0, 0, 0, time.UTC)
		return firstOfMonth, endOfToday, nil
	default:
		return time.Time{}, time.Time{}, fmt.Errorf("unknown preset: %s", preset)
	}
}

func parseDateRange(q url.Values) (time.Time, time.Time, error) {
	preset := q.Get("preset")
	dateFromStr := q.Get("date_from")
	dateToStr := q.Get("date_to")

	if preset != "" {
		return resolvePreset(preset)
	}

	if dateFromStr != "" || dateToStr != "" {
		var dateFrom time.Time
		var dateTo time.Time
		var err error

		if dateFromStr != "" {
			dateFrom, err = parseDate(dateFromStr)
			if err != nil {
				return time.Time{}, time.Time{}, fmt.Errorf("invalid date_from: %w", err)
			}
		}

		if dateToStr != "" {
			dateTo, err = parseDate(dateToStr)
			if err != nil {
				return time.Time{}, time.Time{}, fmt.Errorf("invalid date_to: %w", err)
			}
		} else {
			dateTo = time.Now().UTC()
		}

		if dateFrom.IsZero() {
			dateFrom = dateTo.AddDate(0, 0, -1)
		}

		return dateFrom, dateTo, nil
	}

	from, to, err := resolvePreset("today")
	if err != nil {
		return time.Time{}, time.Time{}, err
	}
	return from, to, nil
}

func parseSort(s string) (field, dir string) {
	parts := strings.Split(s, ":")
	field = parts[0]
	dir = "desc"
	if len(parts) > 1 {
		if parts[1] == "asc" {
			dir = "asc"
		}
	}
	return field, dir
}

func parsePagination(q url.Values, defaultLimit int) (int, int, error) {
	limit := defaultLimit
	if lStr := q.Get("limit"); lStr != "" {
		l, err := strconv.Atoi(lStr)
		if err != nil {
			return 0, 0, fmt.Errorf("invalid limit: %w", err)
		}
		if l > 0 {
			limit = l
		}
	}
	if limit > 1000 {
		limit = 1000
	}

	offset := 0
	if oStr := q.Get("offset"); oStr != "" {
		o, err := strconv.Atoi(oStr)
		if err != nil {
			return 0, 0, fmt.Errorf("invalid offset: %w", err)
		}
		if o < 0 {
			return 0, 0, fmt.Errorf("offset must be >= 0")
		}
		offset = o
	}

	return limit, offset, nil
}

func parseUUIDList(raw, field string) ([]string, error) {
	if strings.TrimSpace(raw) == "" {
		return nil, nil
	}

	values := parseCommaSeparated(raw)
	for _, value := range values {
		if _, err := uuid.Parse(value); err != nil {
			return nil, fmt.Errorf("invalid %s UUID: %s", field, value)
		}
	}
	return values, nil
}

func parseMetricsGranularity(raw string) analytics.MetricsGranularity {
	switch strings.ToLower(strings.TrimSpace(raw)) {
	case "", "none":
		return analytics.MetricsGranularityNone
	case "day", "daily":
		return analytics.MetricsGranularityDay
	case "hour", "hourly":
		return analytics.MetricsGranularityHour
	default:
		return analytics.MetricsGranularity(strings.TrimSpace(raw))
	}
}

func normalizeReportDimension(raw string) string {
	dim := strings.ToLower(strings.TrimSpace(raw))
	switch dim {
	case "campaign_id":
		return "campaign"
	case "stream_id":
		return "stream"
	case "offer_id":
		return "offer"
	case "landing_id":
		return "landing"
	case "country_code":
		return "country"
	case "device_type":
		return "device"
	default:
		return dim
	}
}

func firstNonEmptyValue(values ...string) string {
	for _, value := range values {
		if trimmed := strings.TrimSpace(value); trimmed != "" {
			return trimmed
		}
	}
	return ""
}

func resolveTenantID(r *http.Request) (string, error) {
	if r == nil {
		return "", fmt.Errorf("missing tenant context")
	}

	if id := strings.TrimSpace(r.Header.Get(tenantIDHeader)); id != "" {
		return id, nil
	}

	if id := strings.TrimSpace(r.URL.Query().Get("tenant_id")); id != "" {
		return id, nil
	}

	if userID, ok := r.Context().Value(auth.UserIDKey).(string); ok {
		if id := strings.TrimSpace(userID); id != "" {
			return id, nil
		}
	}

	return "", fmt.Errorf("missing tenant context")
}

func (h *ReportsHandler) respondJSON(w http.ResponseWriter, status int, data interface{}) {
	respondJSON(w, status, data)
}

func (h *ReportsHandler) respondError(w http.ResponseWriter, status int, message string) {
	respondError(w, status, message)
}
