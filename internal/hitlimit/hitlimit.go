package hitlimit

import (
	"context"
	"fmt"
	"strconv"
	"time"

	"github.com/google/uuid"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"
)

// Service handles hit limit enforcement for streams and campaigns.
// Uses Valkey counters with YYYYMMDD suffix for daily tracking.
type Service struct {
	vk     *redis.Client
	logger *zap.Logger
}

// New creates a new hit limit service.
func New(vk *redis.Client, logger *zap.Logger) *Service {
	return &Service{
		vk:     vk,
		logger: logger,
	}
}

// Check verifies if the stream has exceeded its click cap.
// Returns (allowed bool, currentCount int64).
// Key: hitlimit:{stream_id}:{YYYYMMDD} — TTL 25h (auto-expire next day)
func (s *Service) Check(ctx context.Context, streamID uuid.UUID, dailyLimit int64) (bool, int64, error) {
	if dailyLimit <= 0 {
		return true, 0, nil // Unlimited
	}

	key := s.dailyKey(streamID)
	val, err := s.vk.Get(ctx, key).Result()
	if err == redis.Nil {
		return true, 0, nil // No counter yet, allowed
	} else if err != nil {
		return false, 0, fmt.Errorf("check hit limit get: %w", err)
	}

	current, err := strconv.ParseInt(val, 10, 64)
	if err != nil {
		return false, 0, fmt.Errorf("check hit limit parse: %w", err)
	}

	if current >= dailyLimit {
		return false, current, nil
	}
	return true, current, nil
}

// Increment bumps the counter for this stream today.
// Called after stream selection confirms the click is allowed.
func (s *Service) Increment(ctx context.Context, streamID uuid.UUID) error {
	key := s.dailyKey(streamID)
	if err := s.vk.Incr(ctx, key).Err(); err != nil {
		return fmt.Errorf("increment hit limit: %w", err)
	}
	// Expire ensure it doesn't leak memory, 25 hours is enough to span a whole day.
	return s.vk.Expire(ctx, key, 25*time.Hour).Err()
}

// Reset clears all hit limit counters for a stream.
// Called by the HitLimitReset background worker.
func (s *Service) Reset(ctx context.Context, streamID uuid.UUID) error {
	key := s.dailyKey(streamID)
	return s.vk.Del(ctx, key).Err()
}

func (s *Service) dailyKey(id uuid.UUID) string {
	date := time.Now().UTC().Format("20060102")
	return fmt.Sprintf("hitlimit:%s:%s", id, date)
}
