package stage_test

import (
	"encoding/hex"
	"net/url"
	"strings"
	"testing"
	"time"

	"github.com/google/uuid"
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

	tsBytes, err := hex.DecodeString(token[:8])
	if err != nil {
		t.Fatalf("Failed to decode timestamp part: %v", err)
	}

	ts := uint32(tsBytes[0])<<24 | uint32(tsBytes[1])<<16 | uint32(tsBytes[2])<<8 | uint32(tsBytes[3])

	if int64(ts) < before || int64(ts) > after {
		t.Errorf("Timestamp in token (%d) outside expected range [%d, %d]", ts, before, after)
	}

	if payload.RawClick.SubID == "" {
		t.Fatal("Primary click sub_id should be generated")
	}
	if payload.RawClick.SubID2 == "" {
		t.Fatal("aff_sub2 value should be generated")
	}
}

func TestGenerateTokenStage_WithRedirectActionAndOffer(t *testing.T) {
	s := &stage.GenerateTokenStage{}
	payload := &pipeline.Payload{
		RawClick: &model.RawClick{},
		Offer:    &model.Offer{ID: uuid.New()},
		Stream: &model.Stream{
			ID:         uuid.New(),
			ActionType: "HttpRedirect",
			ActionPayload: map[string]interface{}{
				"url": "https://example.com/offer?campaign=123",
			},
		},
	}

	err := s.Process(payload)
	if err != nil {
		t.Fatalf("Process failed: %v", err)
	}

	if payload.ActionPayloadURL == "" {
		t.Fatal("ActionPayloadURL should be set for redirect action when offer is selected")
	}

	parsed, err := url.Parse(payload.ActionPayloadURL)
	if err != nil {
		t.Fatalf("ActionPayloadURL should be parseable: %v", err)
	}
	q := parsed.Query()

	if got := q.Get("_token"); got == "" || got != payload.RawClick.ClickToken {
		t.Errorf("_token should match ClickToken, got %q", got)
	}
	if got := q.Get("_subid"); got == "" || got != payload.RawClick.SubID {
		t.Errorf("_subid should match primary SubID, got %q", got)
	}
	if got := q.Get("aff_sub2"); got == "" || got != payload.RawClick.SubID2 {
		t.Errorf("aff_sub2 should match SubID2, got %q", got)
	}
	if !strings.Contains(payload.ActionPayloadURL, "campaign=123") {
		t.Errorf("ActionPayloadURL should preserve existing params: %s", payload.ActionPayloadURL)
	}
}

func TestGenerateTokenStage_NoOffer_SkipsActionPayloadInjection(t *testing.T) {
	s := &stage.GenerateTokenStage{}
	payload := &pipeline.Payload{
		RawClick: &model.RawClick{},
		Stream: &model.Stream{
			ID:         uuid.New(),
			ActionType: "HttpRedirect",
			ActionPayload: map[string]interface{}{
				"url": "https://example.com/offer",
			},
		},
	}

	if err := s.Process(payload); err != nil {
		t.Fatalf("Process failed: %v", err)
	}

	if payload.ActionPayloadURL != "" {
		t.Errorf("ActionPayloadURL should be empty when no offer was selected: %s", payload.ActionPayloadURL)
	}
}

func TestGenerateTokenStage_NonRedirectAction(t *testing.T) {
	s := &stage.GenerateTokenStage{}
	payload := &pipeline.Payload{
		RawClick: &model.RawClick{},
		Offer:    &model.Offer{ID: uuid.New()},
		Stream: &model.Stream{
			ID:         uuid.New(),
			ActionType: "Content",
			ActionPayload: map[string]interface{}{
				"url": "https://example.com/page",
			},
		},
	}

	err := s.Process(payload)
	if err != nil {
		t.Fatalf("Process failed: %v", err)
	}

	if payload.ActionPayloadURL != "" {
		t.Errorf("ActionPayloadURL should NOT be set for non-redirect action: %s", payload.ActionPayloadURL)
	}
}

func TestGenerateTokenStage_NoStream(t *testing.T) {
	s := &stage.GenerateTokenStage{}
	payload := &pipeline.Payload{
		RawClick: &model.RawClick{},
		Stream:   nil,
	}

	err := s.Process(payload)
	if err != nil {
		t.Fatalf("Process failed: %v", err)
	}

	if payload.ActionPayloadURL != "" {
		t.Errorf("ActionPayloadURL should be empty when no stream: %s", payload.ActionPayloadURL)
	}
}

func TestGenerateTokenStage_BlankReferrerAliasAction(t *testing.T) {
	s := &stage.GenerateTokenStage{}
	payload := &pipeline.Payload{
		RawClick: &model.RawClick{},
		Offer:    &model.Offer{ID: uuid.New()},
		Stream: &model.Stream{
			ID:         uuid.New(),
			ActionType: "blank_referrer",
			ActionPayload: map[string]interface{}{
				"url": "https://example.com/offer",
			},
		},
	}

	if err := s.Process(payload); err != nil {
		t.Fatalf("Process failed: %v", err)
	}

	if payload.ActionPayloadURL == "" {
		t.Fatal("ActionPayloadURL should be set for blank_referrer alias action")
	}

	parsed, err := url.Parse(payload.ActionPayloadURL)
	if err != nil {
		t.Fatalf("ActionPayloadURL should be parseable: %v", err)
	}
	q := parsed.Query()

	if q.Get("_token") == "" || q.Get("_subid") == "" || q.Get("aff_sub2") == "" {
		t.Errorf("ActionPayloadURL should contain tracking params for alias action: %s", payload.ActionPayloadURL)
	}
}
