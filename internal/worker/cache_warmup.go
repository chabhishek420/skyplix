package worker

import (
	"context"
	"time"

	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/cache"
)

// CacheWarmupWorker checks every 30s if a cache warmup has been scheduled.
// In Phase 2, this will load campaign/stream/offer entities into Valkey.
// Phase 1 stub: detects the warmup flag and logs it.
type CacheWarmupWorker struct {
	valkey *redis.Client
	cache  *cache.Cache
	logger *zap.Logger
}

func NewCacheWarmupWorker(valkey *redis.Client, cache *cache.Cache, logger *zap.Logger) *CacheWarmupWorker {
	return &CacheWarmupWorker{valkey: valkey, cache: cache, logger: logger}
}

func (w *CacheWarmupWorker) Name() string { return "cache-warmup" }

func (w *CacheWarmupWorker) Run(ctx context.Context) error {
	ticker := time.NewTicker(30 * time.Second)
	defer ticker.Stop()

	for {
		select {
		case <-ticker.C:
			// Check if warmup was requested (set by admin entity save)
			exists, err := w.valkey.Exists(ctx, "warmup:scheduled").Result()
			if err != nil {
				w.logger.Warn("cache warmup check failed", zap.Error(err))
				continue
			}
			if exists > 0 {
				w.logger.Info("cache warmup triggered by admin mutation")
				if err := w.cache.Warmup(ctx); err != nil {
					w.logger.Error("cache warmup failed", zap.Error(err))
				}
				// Delete the flag
				w.valkey.Del(ctx, "warmup:scheduled")
			}
		case <-ctx.Done():
			return nil
		}
	}
}

// SessionJanitorWorker periodically expires old uniqueness sessions.
// Phase 1 stub — Valkey TTL handles this automatically for now.
type SessionJanitorWorker struct {
	logger *zap.Logger
}

func NewSessionJanitorWorker(logger *zap.Logger) *SessionJanitorWorker {
	return &SessionJanitorWorker{logger: logger}
}

func (w *SessionJanitorWorker) Name() string { return "session-janitor" }

func (w *SessionJanitorWorker) Run(ctx context.Context) error {
	ticker := time.NewTicker(1 * time.Hour)
	defer ticker.Stop()

	for {
		select {
		case <-ticker.C:
			w.logger.Debug("session janitor: Valkey TTL handles expiry — no-op in Phase 1")
		case <-ctx.Done():
			return nil
		}
	}
}
