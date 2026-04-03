package analytics

import (
	"time"
)

// ReportQuery defines the criteria for generating a report.
type ReportQuery struct {
	GroupBy   []string            `json:"group_by"`
	DateFrom  time.Time           `json:"date_from"`
	DateTo    time.Time           `json:"date_to"`
	Filters   map[string][]string `json:"filters"`
	SortField string              `json:"sort_field"`
	SortDir   string              `json:"sort_dir"` // "asc" or "desc"
	Limit     int                 `json:"limit"`
	Offset    int                 `json:"offset"`
}

// ReportRow represents a single row in the report result.
type ReportRow struct {
	Dimensions   map[string]string `json:"dimensions"`
	Clicks       uint64            `json:"clicks"`
	UniqueClicks uint64            `json:"unique_clicks"`
	Bots         uint64            `json:"bots"`
	Conversions  uint64            `json:"conversions"`
	CR           float64           `json:"cr"`     // Conversion Rate %
	Revenue      float64           `json:"revenue"`
	Cost         float64           `json:"cost"`
	Profit       float64           `json:"profit"`
	ROI          float64           `json:"roi"`    // Return on Investment %
	EPC          float64           `json:"epc"`    // Earnings Per Click
	CPC          float64           `json:"cpc"`    // Cost Per Click
}

// ReportSummary contains totals for the report.
type ReportSummary struct {
	Clicks       uint64  `json:"clicks"`
	UniqueClicks uint64  `json:"unique_clicks"`
	Bots         uint64  `json:"bots"`
	Conversions  uint64  `json:"conversions"`
	CR           float64 `json:"cr"`
	Revenue      float64 `json:"revenue"`
	Cost         float64 `json:"cost"`
	Profit       float64 `json:"profit"`
	ROI          float64 `json:"roi"`
	EPC          float64 `json:"epc"`
	CPC          float64 `json:"cpc"`
}

// ReportMeta provides context and pagination info for the report.
type ReportMeta struct {
	DateFrom  string   `json:"date_from"`
	DateTo    string   `json:"date_to"`
	GroupBy   []string `json:"group_by"`
	TotalRows int      `json:"total_rows"`
	Limit     int      `json:"limit"`
	Offset    int      `json:"offset"`
}

// ReportResponse is the complete API response for a report request.
type ReportResponse struct {
	Rows    []ReportRow   `json:"rows"`
	Summary ReportSummary `json:"summary"`
	Meta    ReportMeta    `json:"meta"`
}

// CalculateDerived computes derived metrics for a report row.
func (r *ReportRow) CalculateDerived() {
	// CR = conversions / unique_clicks * 100
	if r.UniqueClicks > 0 {
		r.CR = float64(r.Conversions) / float64(r.UniqueClicks) * 100
	} else {
		r.CR = 0
	}

	// EPC = revenue / clicks
	if r.Clicks > 0 {
		r.EPC = r.Revenue / float64(r.Clicks)
		r.CPC = r.Cost / float64(r.Clicks)
	} else {
		r.EPC = 0
		r.CPC = 0
	}

	// Profit = revenue - cost
	r.Profit = r.Revenue - r.Cost

	// ROI = profit / cost * 100
	if r.Cost != 0 {
		r.ROI = r.Profit / r.Cost * 100
	} else if r.Profit > 0 {
		r.ROI = 100 // Avoid infinity, though mathematically cost=0, profit>0 is infinite ROI
	} else {
		r.ROI = 0
	}
}

// CalculateDerived computes derived metrics for a report summary.
func (s *ReportSummary) CalculateDerived() {
	if s.UniqueClicks > 0 {
		s.CR = float64(s.Conversions) / float64(s.UniqueClicks) * 100
	} else {
		s.CR = 0
	}

	if s.Clicks > 0 {
		s.EPC = s.Revenue / float64(s.Clicks)
		s.CPC = s.Cost / float64(s.Clicks)
	} else {
		s.EPC = 0
		s.CPC = 0
	}

	s.Profit = s.Revenue - s.Cost

	if s.Cost != 0 {
		s.ROI = s.Profit / s.Cost * 100
	} else if s.Profit > 0 {
		s.ROI = 100
	} else {
		s.ROI = 0
	}
}
