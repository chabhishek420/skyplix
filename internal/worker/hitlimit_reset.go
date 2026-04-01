package worker

import (
	"context"
	"fmt"
	"time"

	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"
)

// HitLimitResetWorker resets all daily click cap counters in Valkey at midnight UTC.
// Keitaro uses a cron task for this; we use a time-based ticker.
type HitLimitResetWorker struct {
	valkey *redis.Client
	logger *zap.Logger
}

func NewHitLimitResetWorker(valkey *redis.Client, logger *zap.Logger) *HitLimitResetWorker {
	return &HitLimitResetWorker{valkey: valkey, logger: logger}
}

func (w *HitLimitResetWorker) Name() string { return "hitlimit-reset" }

func (w *HitLimitResetWorker) Run(ctx context.Context) error {
	for {
		next := nextMidnightUTC()
		w.logger.Info("hitlimit reset scheduled",
			zap.Time("next_reset", next),
			zap.Duration("in", time.Until(next)),
		)

		select {
		case <-time.After(time.Until(next)):
			if err := w.reset(ctx); err != nil {
				w.logger.Error("hitlimit reset failed", zap.Error(err))
				// Continue — will retry at next midnight
			}
		case <-ctx.Done():
			return nil
		}
	}
}

// reset deletes all hitlimit:* keys from Valkey using SCAN + DEL.
func (w *HitLimitResetWorker) reset(ctx context.Context) error {
	var cursor uint64
	var deleted int64

	for {
		keys, nextCursor, err := w.valkey.Scan(ctx, cursor, "hitlimit:*", 100).Result()
		if err != nil {
			return fmt.Errorf("scan hitlimit keys: %w", err)
		}

		if len(keys) > 0 {
			n, err := w.valkey.Del(ctx, keys...).Result()
			if err != nil {
				return fmt.Errorf("delete hitlimit keys: %w", err)
			}
			deleted += n
		}

		cursor = nextCursor
		if cursor == 0 {
			break
		}
	}

	w.logger.Info("hit limit counters reset", zap.Int64("keys_deleted", deleted))
	return nil
}

// nextMidnightUTC returns the next midnight in UTC.
func nextMidnightUTC() time.Time {
	now := time.Now().UTC()
	return time.Date(now.Year(), now.Month(), now.Day()+1, 0, 0, 0, 0, time.UTC)
}
