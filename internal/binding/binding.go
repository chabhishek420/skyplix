package binding

import (
	"context"
	"fmt"
	"time"

	"github.com/google/uuid"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"
)

const bindTTL = 30 * 24 * time.Hour // 30 days (Keitaro default)

// Service handles visitor entity binding in Valkey.
// When campaign.BindVisitors=true, returning visitors are pinned
// to their previously assigned stream/landing/offer.
type Service struct {
	vk     *redis.Client
	logger *zap.Logger
}

// New creates a new binding service.
func New(vk *redis.Client, logger *zap.Logger) *Service {
	return &Service{vk: vk, logger: logger}
}

// GetBinding checks if a visitor already has a binding for the given entity type and scope.
// BindType: "stream", "landing", "offer"
// ScopeID:  campaignID for stream binding, streamID for landing/offer binding.
// Returns uuid.Nil if no binding exists.
func (s *Service) GetBinding(ctx context.Context, visitorCode, bindType string, scopeID uuid.UUID) (uuid.UUID, error) {
	key := fmt.Sprintf("bind:%s:%s:%s", bindType, scopeID, visitorCode)
	val, err := s.vk.Get(ctx, key).Result()
	if err == redis.Nil {
		return uuid.Nil, nil
	}
	if err != nil {
		return uuid.Nil, err
	}
	return uuid.Parse(val)
}

// SetBinding creates a binding for a visitor to an entity. TTL = 30 days.
func (s *Service) SetBinding(ctx context.Context, visitorCode, bindType string, scopeID, entityID uuid.UUID) error {
	key := fmt.Sprintf("bind:%s:%s:%s", bindType, scopeID, visitorCode)
	return s.vk.Set(ctx, key, entityID.String(), bindTTL).Err()
}
