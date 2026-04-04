package stage

import (
	"crypto/rand"
	"encoding/hex"
	"fmt"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// GenerateTokenStage — Pipeline Stage 13
// Generates a cryptographically random 32-char hex click token.
// This token is used for conversion postback attribution.
type GenerateTokenStage struct{}

func (s *GenerateTokenStage) AlwaysRun() bool { return false }
func (s *GenerateTokenStage) Name() string { return "GenerateToken" }

func (s *GenerateTokenStage) Process(payload *pipeline.Payload) error {
	b := make([]byte, 16)
	if _, err := rand.Read(b); err != nil {
		return fmt.Errorf("read random bytes: %w", err)
	}
	payload.RawClick.ClickToken = hex.EncodeToString(b)
	return nil
}
