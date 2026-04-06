package handler

import (
	"fmt"
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/analytics"
)

// ReportsHandler handles reporting API requests.
type ReportsHandler struct {
	logger    *zap.Logger
	analytics *analytics.Service
}

// NewReportsHandler creates a new reports handler.
func NewReportsHandler(logger *zap.Logger, analytics *analytics.Service) *ReportsHandler {
	return &ReportsHandler{
		logger:    logger,
		analytics: analytics,
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

	query := &analytics.ReportQuery{
		Filters: make(map[string][]string),
	}

	// 1. Group By
	if groupBy := q.Get("group_by"); groupBy != "" {
		query.GroupBy = parseCommaSeparated(groupBy)
	}

	// 2. Date Range & Preset
	preset := q.Get("preset")
	dateFromStr := q.Get("date_from")
	dateToStr := q.Get("date_to")

	if preset != "" {
		from, to, err := resolvePreset(preset)
		if err != nil {
			return nil, err
		}
		query.DateFrom = from
		query.DateTo = to
	} else if dateFromStr != "" || dateToStr != "" {
		if dateFromStr != "" {
			from, err := parseDate(dateFromStr)
			if err != nil {
				return nil, fmt.Errorf("invalid date_from: %w", err)
			}
			query.DateFrom = from
		}
		if dateToStr != "" {
			to, err := parseDate(dateToStr)
			if err != nil {
				return nil, fmt.Errorf("invalid date_to: %w", err)
			}
			query.DateTo = to
		} else {
			query.DateTo = time.Now().UTC()
		}
	} else {
		// Default to today
		from, to, _ := resolvePreset("today")
		query.DateFrom = from
		query.DateTo = to
	}

	// 3. Filters
	if campaigns := q.Get("campaign_id"); campaigns != "" {
		ids := parseCommaSeparated(campaigns)
		for _, id := range ids {
			if _, err := uuid.Parse(id); err != nil {
				return nil, fmt.Errorf("invalid campaign_id UUID: %s", id)
			}
		}
		query.Filters["campaign_id"] = ids
	}
	if countries := q.Get("country"); countries != "" {
		query.Filters["country_code"] = parseCommaSeparated(countries)
	}
	if deviceType := q.Get("device_type"); deviceType != "" {
		query.Filters["device_type"] = []string{deviceType}
	}
	if streamID := q.Get("stream_id"); streamID != "" {
		if _, err := uuid.Parse(streamID); err != nil {
			return nil, fmt.Errorf("invalid stream_id UUID: %s", streamID)
		}
		query.Filters["stream_id"] = []string{streamID}
	}
	if offerID := q.Get("offer_id"); offerID != "" {
		if _, err := uuid.Parse(offerID); err != nil {
			return nil, fmt.Errorf("invalid offer_id UUID: %s", offerID)
		}
		query.Filters["offer_id"] = []string{offerID}
	}

	// 4. Sort
	sortStr := q.Get("sort")
	if sortStr == "" {
		sortStr = "clicks:desc"
	}
	field, dir := parseSort(sortStr)
	query.SortField = field
	query.SortDir = dir

	// 5. Pagination
	limit := 50
	if lStr := q.Get("limit"); lStr != "" {
		if l, err := strconv.Atoi(lStr); err == nil && l > 0 {
			limit = l
		}
	}
	if limit > 1000 {
		limit = 1000
	}
	query.Limit = limit

	offset := 0
	if oStr := q.Get("offset"); oStr != "" {
		if o, err := strconv.Atoi(oStr); err == nil && o >= 0 {
			offset = o
		}
	}
	query.Offset = offset

	return query, nil
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

func (h *ReportsHandler) respondJSON(w http.ResponseWriter, status int, data interface{}) {
	respondJSON(w, status, data)
}

func (h *ReportsHandler) respondError(w http.ResponseWriter, status int, message string) {
	respondError(w, status, message)
}
