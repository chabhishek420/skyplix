package stage

import (
	"crypto/rand"
	"encoding/binary"
	"encoding/hex"
	"fmt"
	"net/url"
	"time"

	actionpkg "github.com/skyplix/zai-tds/internal/action"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// GenerateTokenStage — Pipeline Stage 13
// Generates a production-standard click token.
// Format: [8 hex timestamp][16 hex random] (24 chars total)
// Also injects _token, _subid, and aff_sub2 params into redirect URLs when token URL injection is needed.
// Generates NEW_UNIQUE_ID for aff_sub2 on every click (Phase 8).
type GenerateTokenStage struct{}

func (s *GenerateTokenStage) AlwaysRun() bool { return false }
func (s *GenerateTokenStage) Name() string    { return "GenerateToken" }

func (s *GenerateTokenStage) Process(payload *pipeline.Payload) error {
	if payload == nil || payload.RawClick == nil {
		return nil
	}

	ts := uint32(time.Now().Unix())
	tsBytes := make([]byte, 4)
	binary.BigEndian.PutUint32(tsBytes, ts)

	randBytes := make([]byte, 8)
	if _, err := rand.Read(randBytes); err != nil {
		return fmt.Errorf("read random bytes: %w", err)
	}

	finalBytes := append(tsBytes, randBytes...)
	payload.RawClick.ClickToken = hex.EncodeToString(finalBytes)

	if payload.RawClick.SubID == "" {
		payload.RawClick.SubID = generateClickSubID()
	}

	// Phase 8: Generate NEW_UNIQUE_ID for aff_sub2 on every click
	payload.RawClick.SubID2 = generateUniqueID()

	if !shouldAddTokenToURL(payload) {
		return nil
	}

	// Inject _token and _subid into redirect URLs (Landing, Offer, or ActionPayload)
	// This ensures tracking params are present in the final outbound URL.
	token := payload.RawClick.ClickToken
	subID := payload.RawClick.SubID
	affSub2 := payload.RawClick.SubID2
	if payload.Landing != nil && payload.Landing.URL != "" {
		payload.Landing.URL = addTrackingParams(payload.Landing.URL, token, subID, affSub2)
	}
	if payload.Offer != nil && payload.Offer.URL != "" {
		payload.Offer.URL = addTrackingParams(payload.Offer.URL, token, subID, affSub2)
	}
	if payload.Stream != nil && actionpkg.IsRedirectActionType(payload.Stream.ActionType) {
		if targetURL, ok := payload.Stream.ActionPayload["url"].(string); ok && targetURL != "" {
			payload.ActionPayloadURL = addTrackingParams(targetURL, token, subID, affSub2)
		}
	}

	return nil
}

// shouldAddTokenToURL follows Keitaro token URL behavior: only clicks with a chosen offer
// should receive _token/_subid URL injection.
func shouldAddTokenToURL(payload *pipeline.Payload) bool {
	return payload != nil && payload.Offer != nil
}

func addTrackingParams(rawURL, token, subID, affSub2 string) string {
	parsed, err := url.Parse(rawURL)
	if err != nil {
		return rawURL
	}

	q := parsed.Query()
	q.Set("_token", token)
	q.Set("_subid", subID)
	q.Set("aff_sub2", affSub2)
	parsed.RawQuery = q.Encode()

	return parsed.String()
}

// generateClickSubID creates a Keitaro-style primary click sub_id independent from token.
func generateClickSubID() string {
	ts := uint32(time.Now().Unix())
	tsBytes := make([]byte, 4)
	binary.BigEndian.PutUint32(tsBytes, ts)

	randBytes := make([]byte, 8)
	if _, err := rand.Read(randBytes); err != nil {
		return fmt.Sprintf("%d", time.Now().UnixNano())
	}

	return hex.EncodeToString(append(tsBytes, randBytes...))
}

// generateUniqueID generates a new unique ID for aff_sub2 on every click.
// Uses crypto/rand for cryptographically secure random bytes.
func generateUniqueID() string {
	b := make([]byte, 16)
	if _, err := rand.Read(b); err != nil {
		// Fallback to timestamp-based if rand fails
		return fmt.Sprintf("%d%x", time.Now().UnixNano(), time.Now().UnixNano())
	}
	return hex.EncodeToString(b)
}
