package stage

import (
	"github.com/skyplix/zai-tds/internal/hitlimit"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// UpdateHitLimitStage increments daily click counters and aborts if caps are exceeded.
type UpdateHitLimitStage struct {
	Service *hitlimit.Service
}

func (s *UpdateHitLimitStage) Name() string      { return "UpdateHitLimit" }
func (s *UpdateHitLimitStage) AlwaysRun() bool { return false }

func (s *UpdateHitLimitStage) Process(p *pipeline.Payload) error {
	if p.Stream == nil {
		return nil
	}

	// 1. Check daily limit
	if p.Stream.DailyLimit > 0 {
		allowed, current, err := s.Service.Check(p.Ctx, p.Stream.ID, p.Stream.DailyLimit)
		if err == nil && !allowed {
			// Cap exceeded
			p.Abort = true
			p.AbortCode = 404 // Or fallback to default stream if we want to be smarter
			return nil
		}
		_ = current
	}

	// 2. Increment counter
	_ = s.Service.Increment(p.Ctx, p.Stream.ID)

	return nil
}
