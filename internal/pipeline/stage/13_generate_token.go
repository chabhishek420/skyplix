package stage

import (
	"crypto/rand"
	"encoding/binary"
	"encoding/hex"
	"fmt"
	"time"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// GenerateTokenStage — Pipeline Stage 13
// Generates a production-standard click token.
// Format: [8 hex timestamp][16 hex random] (24 chars total)
// This format matches Keitaro's high-performance click ID structure for better
// auditability and database indexing performance.
type GenerateTokenStage struct{}

func (s *GenerateTokenStage) AlwaysRun() bool { return false }
func (s *GenerateTokenStage) Name() string { return "GenerateToken" }

func (s *GenerateTokenStage) Process(payload *pipeline.Payload) error {
	// 1. Get 4 bytes for Unix timestamp (hex encoded as 8 chars)
	ts := uint32(time.Now().Unix())
	tsBytes := make([]byte, 4)
	binary.BigEndian.PutUint32(tsBytes, ts)

	// 2. Get 8 bytes for random (hex encoded as 16 chars)
	randBytes := make([]byte, 8)
	if _, err := rand.Read(randBytes); err != nil {
		return fmt.Errorf("read random bytes: %w", err)
	}

	// 3. Combine and encode (total 12 bytes -> 24 hex chars)
	finalBytes := append(tsBytes, randBytes...)
	payload.RawClick.ClickToken = hex.EncodeToString(finalBytes)

	return nil
}
