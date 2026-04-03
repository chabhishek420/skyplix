package analytics

import (
	"testing"
	"time"

	"github.com/google/uuid"
	"github.com/stretchr/testify/assert"
)

func TestQueryBuilder_BuildClickStatsQuery(t *testing.T) {
	qb := NewQueryBuilder()
	from := time.Now().Add(-24 * time.Hour)
	to := time.Now()
	campaignID := uuid.New().String()

	t.Run("Single dimension", func(t *testing.T) {
		q := &ReportQuery{
			GroupBy:  []string{"campaign"},
			DateFrom: from,
			DateTo:   to,
		}
		sql, args, err := qb.BuildClickStatsQuery(q)
		assert.NoError(t, err)
		assert.Contains(t, sql, "SELECT campaign_id AS campaign, sum(clicks) AS clicks")
		assert.Contains(t, sql, "FROM stats_hourly")
		assert.Contains(t, sql, "GROUP BY campaign_id")
		assert.Len(t, args, 2)
	})

	t.Run("Daily table for long range", func(t *testing.T) {
		q := &ReportQuery{
			GroupBy:  []string{"campaign"},
			DateFrom: from.Add(-10 * 24 * time.Hour),
			DateTo:   to,
		}
		sql, _, err := qb.BuildClickStatsQuery(q)
		assert.NoError(t, err)
		assert.Contains(t, sql, "FROM stats_daily")
	})

	t.Run("Filters", func(t *testing.T) {
		q := &ReportQuery{
			GroupBy:  []string{"country"},
			DateFrom: from,
			DateTo:   to,
			Filters: map[string][]string{
				"campaign": {campaignID},
				"country":  {"US", "GB"},
			},
		}
		sql, args, err := qb.BuildClickStatsQuery(q)
		assert.NoError(t, err)
		assert.Contains(t, sql, "campaign_id = ?")
		assert.Contains(t, sql, "country_code IN (?, ?)")
		assert.Len(t, args, 5) // from, to, campaign, US, GB
	})

	t.Run("Invalid dimension", func(t *testing.T) {
		q := &ReportQuery{
			GroupBy:  []string{"invalid"},
			DateFrom: from,
			DateTo:   to,
		}
		_, _, err := qb.BuildClickStatsQuery(q)
		assert.Error(t, err)
	})

	t.Run("Excluded dimensions for clicks", func(t *testing.T) {
		q := &ReportQuery{
			GroupBy:  []string{"status"}, // convs_only
			DateFrom: from,
			DateTo:   to,
		}
		sql, _, err := qb.BuildClickStatsQuery(q)
		assert.NoError(t, err)
		assert.NotContains(t, sql, "status")
	})

	t.Run("Hour forces hourly table", func(t *testing.T) {
		q := &ReportQuery{
			GroupBy:  []string{"hour"},
			DateFrom: from.Add(-10 * 24 * time.Hour),
			DateTo:   to,
		}
		sql, _, err := qb.BuildClickStatsQuery(q)
		assert.NoError(t, err)
		assert.Contains(t, sql, "FROM stats_hourly")
	})
}

func TestQueryBuilder_BuildConvStatsQuery(t *testing.T) {
	qb := NewQueryBuilder()
	from := time.Now().Add(-24 * time.Hour)
	to := time.Now()

	t.Run("Device dimensions excluded from conversions", func(t *testing.T) {
		q := &ReportQuery{
			GroupBy:  []string{"campaign", "device"},
			DateFrom: from,
			DateTo:   to,
		}
		sql, _, err := qb.BuildConvStatsQuery(q)
		assert.NoError(t, err)
		assert.Contains(t, sql, "campaign_id AS campaign")
		assert.NotContains(t, sql, "device_type")
		assert.Contains(t, sql, "GROUP BY campaign_id")
		assert.NotContains(t, sql, "GROUP BY campaign_id, device_type")
	})
}
