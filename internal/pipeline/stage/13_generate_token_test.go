package stage_test

import (
	"encoding/hex"
	"testing"
	"time"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/pipeline/stage"
)

func TestGenerateTokenStage(t *testing.T) {
	s := &stage.GenerateTokenStage{}
	payload := &pipeline.Payload{
		RawClick: &model.RawClick{},
	}

	before := time.Now().Unix()
	err := s.Process(payload)
	after := time.Now().Unix()

	if err != nil {
		t.Fatalf("Process failed: %v", err)
	}

	token := payload.RawClick.ClickToken
	if len(token) != 24 {
		t.Errorf("Expected token length 24, got %d (%s)", len(token), token)
	}

	// First 8 chars are hex timestamp
	tsBytes, err := hex.DecodeString(token[:8])
	if err != nil {
		t.Fatalf("Failed to decode timestamp part: %v", err)
	}

	// Reconstruct uint32 from big endian bytes
	ts := uint32(tsBytes[0])<<24 | uint32(tsBytes[1])<<16 | uint32(tsBytes[2])<<8 | uint32(tsBytes[3])

	if int64(ts) < before || int64(ts) > after {
		t.Errorf("Timestamp in token (%d) outside expected range [%d, %d]", ts, before, after)
	}
}
