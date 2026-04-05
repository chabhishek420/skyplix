package worker

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/analytics"
	"github.com/skyplix/zai-tds/internal/rotator"
)

// OptimizerWorker periodically recalculates stream weights for "Auto-Optimize" campaigns.
type OptimizerWorker struct {
	db        *pgxpool.Pool
	vk        *redis.Client
	analytics *analytics.Service
	optimizer *rotator.Optimizer
	logger    *zap.Logger
}

func NewOptimizerWorker(db *pgxpool.Pool, vk *redis.Client, analytics *analytics.Service, logger *zap.Logger) *OptimizerWorker {
	return &OptimizerWorker{
		db:        db,
		vk:        vk,
		analytics: analytics,
		optimizer: rotator.NewOptimizer(0.1), // 10% exploration
		logger:    logger,
	}
}

func (w *OptimizerWorker) Name() string { return "OptimizerWorker" }

func (w *OptimizerWorker) Run(ctx context.Context) error {
	ticker := time.NewTicker(10 * time.Minute)
	defer ticker.Stop()

	// Initial run
	w.runOptimization(ctx)

	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case <-ticker.C:
			w.runOptimization(ctx)
		}
	}
}

func (w *OptimizerWorker) runOptimization(ctx context.Context) {
	w.logger.Info("running auto-optimization cycle")

	// 1. Fetch campaigns with optimization enabled
	campaigns, err := w.fetchOptimizableCampaigns(ctx)
	if err != nil {
		w.logger.Error("failed to fetch optimizable campaigns", zap.Error(err))
		return
	}

	for _, campaignID := range campaigns {
		// 2. Fetch performance data (last 24h)
		perf, err := w.analytics.GetStreamPerformance(ctx, campaignID, 24*time.Hour)
		if err != nil {
			w.logger.Error("failed to get stream performance", zap.String("campaign_id", campaignID), zap.Error(err))
			continue
		}

		if len(perf) == 0 {
			continue
		}

		// 3. Calculate new weights
		weights := w.optimizer.CalculateWeights(perf)
		if len(weights) == 0 {
			continue
		}

		// 4. Update Valkey cache
		// Key format: optimized_weights:{campaignID}
		key := fmt.Sprintf("optimized_weights:%s", campaignID)
		val, _ := json.Marshal(weights)
		if err := w.vk.Set(ctx, key, val, 1*time.Hour).Err(); err != nil {
			w.logger.Error("failed to update optimized weights in valkey", zap.String("campaign_id", campaignID), zap.Error(err))
		} else {
			w.logger.Debug("updated optimized weights", zap.String("campaign_id", campaignID), zap.Int("streams", len(weights)))
		}
	}

	w.logger.Info("auto-optimization cycle complete", zap.Int("campaigns_processed", len(campaigns)))
}

func (w *OptimizerWorker) fetchOptimizableCampaigns(ctx context.Context) ([]string, error) {
	rows, err := w.db.Query(ctx, "SELECT id FROM campaigns WHERE is_optimization_enabled = true AND state = 'active'")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var ids []string
	for rows.Next() {
		var id string
		if err := rows.Scan(&id); err != nil {
			return nil, err
		}
		ids = append(ids, id)
	}
	return ids, nil
}
