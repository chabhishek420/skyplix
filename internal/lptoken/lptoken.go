/*
 * MODIFIED: internal/lptoken/lptoken.go
 * PURPOSE: Implemented session context persistence for LP tokens. Added
 *          Save method to link existing click tokens with routing context.
 */
package lptoken

import (
	"context"
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"time"

	"github.com/google/uuid"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

const lpTTL = 1 * time.Hour

// LPContext holds the Level 1 click context needed by Level 2.
type LPContext struct {
	CampaignID  uuid.UUID `json:"campaign_id"`
	StreamID    uuid.UUID `json:"stream_id"`
	VisitorCode string    `json:"visitor_code"`
	SubID1      string    `json:"sub_id_1,omitempty"`
	SubID2      string    `json:"sub_id_2,omitempty"`
	SubID3      string    `json:"sub_id_3,omitempty"`
	SubID4      string    `json:"sub_id_4,omitempty"`
	SubID5      string    `json:"sub_id_5,omitempty"`
}

// Service manages LP tokens for landing→offer click linking.
type Service struct {
	vk     *redis.Client
	logger *zap.Logger
}

// New creates a new LP token service.
func New(vk *redis.Client, logger *zap.Logger) *Service {
	return &Service{vk: vk, logger: logger}
}

// Create generates an LP token and stores the Level 1 context in Valkey. TTL = 1h.
func (s *Service) Create(ctx context.Context, click *model.RawClick, visitorCode string) (string, error) {
	token := generateToken()

	lpCtx := LPContext{
		CampaignID:  click.CampaignID,
		StreamID:    click.StreamID,
		VisitorCode: visitorCode,
		SubID1:      click.SubID1,
		SubID2:      click.SubID2,
		SubID3:      click.SubID3,
		SubID4:      click.SubID4,
		SubID5:      click.SubID5,
	}

	data, err := json.Marshal(lpCtx)
	if err != nil {
		return "", fmt.Errorf("marshal lp context: %w", err)
	}

	key := fmt.Sprintf("lp:%s", token)
	if err := s.vk.Set(ctx, key, data, lpTTL).Err(); err != nil {
		return "", fmt.Errorf("store lp token: %w", err)
	}

	return token, nil
}

// Save stores the Level 1 click context using an existing token string.
func (s *Service) Save(ctx context.Context, token string, lpCtx *LPContext) error {
	data, err := json.Marshal(lpCtx)
	if err != nil {
		return fmt.Errorf("marshal lp context: %w", err)
	}

	key := fmt.Sprintf("lp:%s", token)
	if err := s.vk.Set(ctx, key, data, lpTTL).Err(); err != nil {
		return fmt.Errorf("store lp token: %w", err)
	}

	return nil
}

// Resolve retrieves the Level 1 click context from an LP token.
func (s *Service) Resolve(ctx context.Context, token string) (*LPContext, error) {
	key := fmt.Sprintf("lp:%s", token)
	val, err := s.vk.Get(ctx, key).Result()
	if err == redis.Nil {
		return nil, nil // Token expired or invalid
	}
	if err != nil {
		return nil, err
	}

	var lpCtx LPContext
	if err := json.Unmarshal([]byte(val), &lpCtx); err != nil {
		return nil, err
	}
	return &lpCtx, nil
}

func generateToken() string {
	b := make([]byte, 16)
	rand.Read(b)
	return hex.EncodeToString(b)
}
