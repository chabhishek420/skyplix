package optimizer

import (
	"time"

	"github.com/skyplix/zai-tds/internal/model"
)

// FeatureVector is the explicit feature payload used for optimization scoring.
type FeatureVector struct {
	CampaignID         string `json:"campaign_id"`
	OptimizationMetric string `json:"optimization_metric"`
	VisitorCode        string `json:"visitor_code"`
	CountryCode        string `json:"country_code"`
	DeviceType         string `json:"device_type"`
	IsUniqueCampaign   bool   `json:"is_unique_campaign"`
	HourOfDay          int    `json:"hour_of_day"`
	DayOfWeek          int    `json:"day_of_week"`
}

// BuildFeatureVector constructs deterministic optimizer features from campaign/click context.
func BuildFeatureVector(campaign *model.Campaign, rawClick *model.RawClick, visitorCode string, now time.Time) FeatureVector {
	if now.IsZero() {
		now = time.Now().UTC()
	}

	features := FeatureVector{
		VisitorCode: visitorCode,
		HourOfDay:   now.UTC().Hour(),
		DayOfWeek:   int(now.UTC().Weekday()),
	}

	if campaign != nil {
		features.CampaignID = campaign.ID.String()
		features.OptimizationMetric = campaign.OptimizationMetric
	}

	if rawClick != nil {
		features.CountryCode = rawClick.CountryCode
		features.DeviceType = rawClick.DeviceType
		features.IsUniqueCampaign = rawClick.IsUniqueCampaign
	}

	return features
}
