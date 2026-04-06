package attribution

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// Service handles click metadata caching for postback attribution.
type Service struct {
	vk     *redis.Client
	logger *zap.Logger
}

// New creates a new attribution service.
func New(vk *redis.Client, logger *zap.Logger) *Service {
	return &Service{
		vk:     vk,
		logger: logger,
	}
}

// SaveClickAttribution caches the click metadata in Valkey for 24h.
// Key: attr:{click_token}
func (s *Service) SaveClickAttribution(ctx context.Context, token string, data model.AttributionData) error {
	ctx, cancel := context.WithTimeout(ctx, 2*time.Second)
	defer cancel()

	if token == "" {
		return nil
	}

	key := fmt.Sprintf("attr:%s", token)
	val, err := json.Marshal(data)
	if err != nil {
		return fmt.Errorf("marshal attribution data: %w", err)
	}

	if err := s.vk.Set(ctx, key, val, 24*time.Hour).Err(); err != nil {
		return fmt.Errorf("save attribution to valkey: %w", err)
	}

	return nil
}

// GetClickAttribution retrieves cached click metadata for a token.
func (s *Service) GetClickAttribution(ctx context.Context, token string) (*model.AttributionData, error) {
	ctx, cancel := context.WithTimeout(ctx, 1*time.Second)
	defer cancel()

	key := fmt.Sprintf("attr:%s", token)
	val, err := s.vk.Get(ctx, key).Result()
	if err == redis.Nil {
		return nil, nil // Not found
	}
	if err != nil {
		return nil, fmt.Errorf("get attribution from valkey: %w", err)
	}

	var data model.AttributionData
	if err := json.Unmarshal([]byte(val), &data); err != nil {
		return nil, fmt.Errorf("unmarshal attribution data: %w", err)
	}

	return &data, nil
}

// IsDuplicateExternalID checks if an external transaction ID has already been processed.
// Uses Valkey with a 30-day TTL for deduplication.
func (s *Service) IsDuplicateExternalID(ctx context.Context, externalID string) (bool, error) {
	ctx, cancel := context.WithTimeout(ctx, 1*time.Second)
	defer cancel()

	if externalID == "" {
		return false, nil
	}

	key := fmt.Sprintf("conv:tx:%s", externalID)
	// SetNX returns true if key was set (didn't exist), false if it exists.
	added, err := s.vk.SetNX(ctx, key, "1", 30*24*time.Hour).Result()
	if err != nil {
		return false, fmt.Errorf("check duplicate in valkey: %w", err)
	}

	return !added, nil
}
