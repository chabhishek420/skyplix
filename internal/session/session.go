package session

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"
)

// Service handles uniqueness tracking and session persistence in Valkey.
type Service struct {
	vk     *redis.Client
	logger *zap.Logger
}

// New creates a new session service.
func New(vk *redis.Client, logger *zap.Logger) *Service {
	return &Service{
		vk:     vk,
		logger: logger,
	}
}

// CheckGlobalUniqueness returns true if this visitor has NEVER visited this TDS before (within 24h).
// Key: sess:{visitor_code}:global — TTL 24h
func (s *Service) CheckGlobalUniqueness(ctx context.Context, visitorCode string) (bool, error) {
	key := fmt.Sprintf("sess:%s:global", visitorCode)
	// SETNX returns true if the key was set, false if it already exists
	isNew, err := s.vk.SetNX(ctx, key, "1", 24*time.Hour).Result()
	if err != nil {
		return false, fmt.Errorf("check global uniqueness: %w", err)
	}
	return isNew, nil
}

// CheckCampaignUniqueness returns true if this visitor has NOT visited this campaign before.
// Sets the uniqueness flag in Valkey if unique.
// Key: sess:{visitor_code}:campaign:{campaign_id} — TTL 24h
func (s *Service) CheckCampaignUniqueness(ctx context.Context, visitorCode string, campaignID uuid.UUID) (bool, error) {
	key := fmt.Sprintf("sess:%s:campaign:%s", visitorCode, campaignID)
	// SETNX returns true if the key was set, false if it already exists
	isNew, err := s.vk.SetNX(ctx, key, "1", 24*time.Hour).Result()
	if err != nil {
		return false, fmt.Errorf("check campaign uniqueness: %w", err)
	}
	return isNew, nil
}

// CheckStreamUniqueness returns true if this visitor has NOT visited this stream before.
// Key: sess:{visitor_code}:stream:{stream_id} — TTL 24h
func (s *Service) CheckStreamUniqueness(ctx context.Context, visitorCode string, streamID uuid.UUID) (bool, error) {
	key := fmt.Sprintf("sess:%s:stream:%s", visitorCode, streamID)
	isNew, err := s.vk.SetNX(ctx, key, "1", 24*time.Hour).Result()
	if err != nil {
		return false, fmt.Errorf("check stream uniqueness: %w", err)
	}
	return isNew, nil
}

// SaveSession persists the current session state to Valkey.
// Called by SaveUniquenessSession stage (stage 18).
func (s *Service) SaveSession(ctx context.Context, visitorCode string, data map[string]string) error {
	if len(data) == 0 {
		return nil
	}
	key := fmt.Sprintf("sess:%s", visitorCode)
	// Use HSet for session data Hash
	if err := s.vk.HSet(ctx, key, data).Err(); err != nil {
		return fmt.Errorf("save session hash: %w", err)
	}
	return s.vk.Expire(ctx, key, 24*time.Hour).Err()
}

// SaveClickSnapshot stores the full RawClick state for L2 redirection matching.
// Key: click:snap:{click_token} — TTL 24h
func (s *Service) SaveClickSnapshot(ctx context.Context, token string, click *model.RawClick) error {
	if token == "" || click == nil {
		return nil
	}
	key := fmt.Sprintf("click:snap:%s", token)
	data, err := json.Marshal(click)
	if err != nil {
		return err
	}
	return s.vk.Set(ctx, key, data, 24*time.Hour).Err()
}

// GetClickSnapshot retrieves a previously stored click state.
func (s *Service) GetClickSnapshot(ctx context.Context, token string) (*model.RawClick, error) {
	key := fmt.Sprintf("click:snap:%s", token)
	val, err := s.vk.Get(ctx, key).Result()
	if err == redis.Nil {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	var click model.RawClick
	if err := json.Unmarshal([]byte(val), &click); err != nil {
		return nil, err
	}
	return &click, nil
}

// GetSession retrieves the full session hash for a visitor.
func (s *Service) GetSession(ctx context.Context, visitorCode string) (map[string]string, error) {
	key := fmt.Sprintf("sess:%s", visitorCode)
	return s.vk.HGetAll(ctx, key).Result()
}

// GetBoundStream returns the stream ID this visitor is pinned to for this campaign.
func (s *Service) GetBoundStream(ctx context.Context, visitorCode string, campaignID uuid.UUID) (uuid.UUID, error) {
	key := fmt.Sprintf("bind:%s:cam:%s", visitorCode, campaignID)
	val, err := s.vk.Get(ctx, key).Result()
	if err == redis.Nil {
		return uuid.Nil, nil
	}
	if err != nil {
		return uuid.Nil, err
	}
	return uuid.Parse(val)
}

// BindToStream pins this visitor to the specified stream for 24h.
func (s *Service) BindToStream(ctx context.Context, visitorCode string, campaignID uuid.UUID, streamID uuid.UUID) error {
	key := fmt.Sprintf("bind:%s:cam:%s", visitorCode, campaignID)
	return s.vk.Set(ctx, key, streamID.String(), 24*time.Hour).Err()
}
