package stage

import (
	"testing"

	"github.com/google/uuid"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

func TestChooseStreamStage_SelectAndBind_EnforcesGlobalBadTrafficAction(t *testing.T) {
	s := &ChooseStreamStage{BadTrafficAction: "Status404"}
	payload := &pipeline.Payload{
		RawClick:    &model.RawClick{IsBot: true},
		Campaign:    &model.Campaign{},
		VisitorCode: "",
	}
	stream := &model.Stream{
		ID:         uuid.New(),
		CampaignID: uuid.New(),
		ActionType: "HttpRedirect",
	}

	s.selectAndBind(payload, stream)

	if payload.Stream == nil {
		t.Fatal("expected selected stream to be set")
	}
	if payload.Stream.ActionType != "Status404" {
		t.Fatalf("expected bot traffic action to be forced to Status404, got %q", payload.Stream.ActionType)
	}
}

func TestChooseStreamStage_SelectAndBind_UsesPerStreamBadTrafficOverride(t *testing.T) {
	s := &ChooseStreamStage{BadTrafficAction: "Status404"}
	payload := &pipeline.Payload{
		RawClick: &model.RawClick{IsBot: true},
		Campaign: &model.Campaign{},
	}
	stream := &model.Stream{
		ID:         uuid.New(),
		CampaignID: uuid.New(),
		ActionType: "HttpRedirect",
		ActionPayload: map[string]interface{}{
			"bad_traffic_action": "SafePage",
		},
	}

	s.selectAndBind(payload, stream)

	if payload.Stream == nil {
		t.Fatal("expected selected stream to be set")
	}
	if payload.Stream.ActionType != "SafePage" {
		t.Fatalf("expected per-stream override action SafePage, got %q", payload.Stream.ActionType)
	}
}

func TestChooseStreamStage_SelectAndBind_PreservesSafeActionForBots(t *testing.T) {
	s := &ChooseStreamStage{BadTrafficAction: "Status404"}
	payload := &pipeline.Payload{
		RawClick: &model.RawClick{IsBot: true},
		Campaign: &model.Campaign{},
	}
	stream := &model.Stream{
		ID:         uuid.New(),
		CampaignID: uuid.New(),
		ActionType: "SafePage",
	}

	s.selectAndBind(payload, stream)

	if payload.Stream == nil {
		t.Fatal("expected selected stream to be set")
	}
	if payload.Stream.ActionType != "SafePage" {
		t.Fatalf("expected SafePage action to be preserved, got %q", payload.Stream.ActionType)
	}
}

func TestChooseStreamStage_SelectAndBind_DoesNotChangeNonBotAction(t *testing.T) {
	s := &ChooseStreamStage{BadTrafficAction: "Status404"}
	payload := &pipeline.Payload{
		RawClick: &model.RawClick{IsBot: false},
		Campaign: &model.Campaign{},
	}
	stream := &model.Stream{
		ID:         uuid.New(),
		CampaignID: uuid.New(),
		ActionType: "HttpRedirect",
	}

	s.selectAndBind(payload, stream)

	if payload.Stream == nil {
		t.Fatal("expected selected stream to be set")
	}
	if payload.Stream.ActionType != "HttpRedirect" {
		t.Fatalf("expected non-bot action to stay HttpRedirect, got %q", payload.Stream.ActionType)
	}
}
