package ratelimit

import (
	"context"
	"fmt"
	"net"
	"time"

	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"
)

// Service provides distributed rate limiting using Valkey.
type Service struct {
	vk     *redis.Client
	logger *zap.Logger
}

// New creates a new rate limiting service.
func New(vk *redis.Client, logger *zap.Logger) *Service {
	return &Service{
		vk:     vk,
		logger: logger,
	}
}

// CheckIPLimit checks if the given IP has exceeded its request limit.
func (s *Service) CheckIPLimit(ctx context.Context, ip net.IP, limit int, window time.Duration) (bool, int64, error) {
	if ip == nil {
		return true, 0, nil
	}

	key := fmt.Sprintf("ratelimit:ip:%s", ip.String())
	return s.incrementAndCheck(ctx, key, limit, window)
}

func (s *Service) incrementAndCheck(ctx context.Context, key string, limit int, window time.Duration) (bool, int64, error) {
	// Atomic increment
	count, err := s.vk.Incr(ctx, key).Result()
	if err != nil {
		return true, 0, err
	}

	// Set expiration on first hit
	if count == 1 {
		if err := s.vk.Expire(ctx, key, window).Err(); err != nil {
			// If expire fails, we might have an eternal counter, but better to allow than block.
			s.logger.Error("failed to set rate limit expiration", zap.String("key", key), zap.Error(err))
		}
	}

	if count > int64(limit) {
		return false, count, nil
	}

	return true, count, nil
}
