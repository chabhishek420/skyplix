package analytics

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestReportRow_CalculateDerived(t *testing.T) {
	t.Run("Normal case", func(t *testing.T) {
		row := &ReportRow{
			Clicks:       1000,
			UniqueClicks: 800,
			Conversions:  40,
			Revenue:      200.0,
			Cost:         100.0,
		}
		row.CalculateDerived()
		assert.Equal(t, 5.0, row.CR)     // 40 / 800 * 100
		assert.Equal(t, 0.2, row.EPC)    // 200 / 1000
		assert.Equal(t, 0.1, row.CPC)    // 100 / 1000
		assert.Equal(t, 100.0, row.Profit) // 200 - 100
		assert.Equal(t, 100.0, row.ROI)    // 100 / 100 * 100
	})

	t.Run("Zero clicks", func(t *testing.T) {
		row := &ReportRow{
			Clicks:       0,
			UniqueClicks: 0,
			Conversions:  0,
			Revenue:      0,
			Cost:         0,
		}
		row.CalculateDerived()
		assert.Equal(t, 0.0, row.CR)
		assert.Equal(t, 0.0, row.EPC)
		assert.Equal(t, 0.0, row.ROI)
	})

	t.Run("Zero cost with profit", func(t *testing.T) {
		row := &ReportRow{
			Clicks:       100,
			UniqueClicks: 100,
			Conversions:  10,
			Revenue:      50.0,
			Cost:         0.0,
		}
		row.CalculateDerived()
		assert.Equal(t, 100.0, row.ROI) // Handled as 100% in CalculateDerived
	})
}

func TestReportSummary_CalculateDerived(t *testing.T) {
	summary := &ReportSummary{
		Clicks:       10000,
		UniqueClicks: 8000,
		Conversions:  400,
		Revenue:      2000.0,
		Cost:         1500.0,
	}
	summary.CalculateDerived()
	assert.Equal(t, 5.0, summary.CR)
	assert.Equal(t, 0.2, summary.EPC)
	assert.Equal(t, 500.0, summary.Profit)
	assert.InDelta(t, 33.33, summary.ROI, 0.01)
}
