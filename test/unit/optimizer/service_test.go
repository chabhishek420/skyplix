package optimizer_test

import (
	"context"
	"testing"

	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/optimizer"
)

type fixedScoreStrategy struct {
	scores map[uuid.UUID]float64
}

func (s fixedScoreStrategy) Name() string { return "fixed" }

func (s fixedScoreStrategy) Score(_ context.Context, _ optimizer.FeatureVector, candidates []optimizer.Candidate) (map[uuid.UUID]float64, error) {
	result := make(map[uuid.UUID]float64, len(candidates))
	for _, candidate := range candidates {
		result[candidate.StreamID] = s.scores[candidate.StreamID]
	}
	return result, nil
}

func TestService_ChooseStream_Disabled(t *testing.T) {
	svc := optimizer.NewDefault(zap.NewNop())

	_, err := svc.ChooseStream(context.Background(), &model.Campaign{IsOptimizationEnabled: false}, nil, "", nil)
	if err == nil {
		t.Fatal("expected error when optimization is disabled")
	}
	if err != optimizer.ErrOptimizationDisabled {
		t.Fatalf("expected ErrOptimizationDisabled, got %v", err)
	}
}

func TestService_ChooseStream_BaselineDeterministic(t *testing.T) {
	svc := optimizer.NewDefault(zap.NewNop())
	campaign := &model.Campaign{
		ID:                    uuid.New(),
		IsOptimizationEnabled: true,
		OptimizationMetric:    "EPC",
	}
	rawClick := &model.RawClick{CountryCode: "US", DeviceType: "mobile", IsUniqueCampaign: true}

	streamA := model.Stream{ID: uuid.New(), Weight: 1, Position: 2}
	streamB := model.Stream{ID: uuid.New(), Weight: 10, Position: 1}

	decision, err := svc.ChooseStream(context.Background(), campaign, rawClick, "visitor-a", []model.Stream{streamA, streamB})
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}
	if decision.SelectedStreamID != streamB.ID {
		t.Fatalf("expected streamB to be selected, got %s", decision.SelectedStreamID)
	}
	if decision.Strategy != "baseline-weighted" {
		t.Fatalf("expected baseline strategy, got %q", decision.Strategy)
	}
	if decision.Features.CampaignID != campaign.ID.String() {
		t.Fatalf("expected campaign id feature %s, got %s", campaign.ID, decision.Features.CampaignID)
	}
}

func TestService_ChooseStream_TieBreakByPosition(t *testing.T) {
	streamA := model.Stream{ID: uuid.New(), Weight: 5, Position: 5}
	streamB := model.Stream{ID: uuid.New(), Weight: 5, Position: 1}

	strategy := fixedScoreStrategy{
		scores: map[uuid.UUID]float64{
			streamA.ID: 1,
			streamB.ID: 1,
		},
	}
	svc := optimizer.New(strategy, zap.NewNop())

	campaign := &model.Campaign{ID: uuid.New(), IsOptimizationEnabled: true, OptimizationMetric: "CR"}
	decision, err := svc.ChooseStream(context.Background(), campaign, &model.RawClick{}, "visitor-a", []model.Stream{streamA, streamB})
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}
	if decision.SelectedStreamID != streamB.ID {
		t.Fatalf("expected lower position stream to win tie-break, got %s", decision.SelectedStreamID)
	}
}
