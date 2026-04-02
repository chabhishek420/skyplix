package stage

import (
	"crypto/rand"
	"encoding/hex"

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
		return err
	}
	payload.RawClick.ClickToken = hex.EncodeToString(b)
	return nil
}
