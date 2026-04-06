package worker

import (
	"context"
	"fmt"
	"net/http"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/model"
)

// TriggerWorker monitors the health of external URLs (Offers/Streams) and disables them on failure.
type TriggerWorker struct {
	db     *pgxpool.Pool
	cache  *cache.Cache
	client *http.Client
	logger *zap.Logger
}

func NewTriggerWorker(db *pgxpool.Pool, cache *cache.Cache, logger *zap.Logger) *TriggerWorker {
	return &TriggerWorker{
		db:    db,
		cache: cache,
		client: &http.Client{
			Timeout: 10 * time.Second,
		},
		logger: logger,
	}
}

func (w *TriggerWorker) Name() string { return "TriggerWorker" }

func (w *TriggerWorker) Run(ctx context.Context) error {
	ticker := time.NewTicker(5 * time.Minute)
	defer ticker.Stop()

	// Initial run
	w.checkAll(ctx)

	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case <-ticker.C:
			w.checkAll(ctx)
		}
	}
}

func (w *TriggerWorker) checkAll(ctx context.Context) {
	w.logger.Info("running active health checks for offers")

	// 1. Fetch all active offers
	offers, err := w.fetchActiveOffers(ctx)
	if err != nil {
		w.logger.Error("failed to fetch active offers for health check", zap.Error(err))
		return
	}

	for _, off := range offers {
		if err := w.checkOffer(ctx, off); err != nil {
			w.logger.Warn("offer health check failed - disabling", zap.String("id", off.ID.String()), zap.String("name", off.Name), zap.Error(err))

			// Auto-disable logic (Keitaro parity)
			_, _ = w.db.Exec(ctx, "UPDATE offers SET state = 'disabled', updated_at = NOW() WHERE id = $1", off.ID)
			w.cache.ScheduleWarmup()
		}
	}
}

func (w *TriggerWorker) checkOffer(ctx context.Context, off model.Offer) error {
	if off.URL == "" {
		return nil
	}

	req, err := http.NewRequestWithContext(ctx, "GET", off.URL, nil)
	if err != nil {
		return err
	}
	req.Header.Set("User-Agent", "SkyPlix-Health-Checker/1.0")

	resp, err := w.client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 400 {
		return fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	return nil
}

func (w *TriggerWorker) fetchActiveOffers(ctx context.Context) ([]model.Offer, error) {
	rows, err := w.db.Query(ctx, "SELECT id, name, url FROM offers WHERE state = 'active'")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var results []model.Offer
	for rows.Next() {
		var o model.Offer
		if err := rows.Scan(&o.ID, &o.Name, &o.URL); err != nil {
			return nil, err
		}
		results = append(results, o)
	}
	return results, nil
}
