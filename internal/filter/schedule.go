package filter

import (
	"time"

	"github.com/skyplix/zai-tds/internal/model"
)

type ScheduleFilter struct{}
func (f *ScheduleFilter) Type() string { return "Schedule" }
func (f *ScheduleFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	now := time.Now()
	day := int(now.Weekday()) // 0=Sunday, 1=Monday...
	hour := now.Hour()

	if days, ok := payload["days"].([]interface{}); ok && len(days) > 0 {
		found := false
		for _, d := range days {
			if di, ok := d.(float64); ok && int(di) == day {
				found = true
				break
			}
		}
		if !found {
			return false
		}
	}

	if hours, ok := payload["hours"].(map[string]interface{}); ok {
		if from, ok := hours["from"].(float64); ok {
			if to, ok := hours["to"].(float64); ok {
				return float64(hour) >= from && float64(hour) <= to
			}
		}
	}

	return true
}
