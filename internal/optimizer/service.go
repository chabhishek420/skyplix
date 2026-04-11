package optimizer

import (
	"context"
	"errors"
	"sort"
	"strings"
	"time"

	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

var (
	ErrOptimizationDisabled = errors.New("optimization is disabled")
	ErrNoCandidates         = errors.New("no candidates available for optimization")
)

// Candidate is the normalized stream input for strategy scoring.
type Candidate struct {
	StreamID uuid.UUID
	Weight   int
	Position int
}

// Decision captures explainable optimization outputs.
type Decision struct {
	SelectedStreamID uuid.UUID          `json:"selected_stream_id"`
	Strategy         string             `json:"strategy"`
	Features         FeatureVector      `json:"features"`
	Scores           map[string]float64 `json:"scores"`
}

// Strategy scores candidates for a given feature vector.
type Strategy interface {
	Name() string
	Score(ctx context.Context, features FeatureVector, candidates []Candidate) (map[uuid.UUID]float64, error)
}

// Service executes optimizer decisions with deterministic tie-breaking.
type Service struct {
	strategy Strategy
	logger   *zap.Logger
	nowFn    func() time.Time
}

// New creates a new optimizer service.
func New(strategy Strategy, logger *zap.Logger) *Service {
	if strategy == nil {
		strategy = BaselineStrategy{}
	}
	return &Service{
		strategy: strategy,
		logger:   logger,
		nowFn: func() time.Time {
			return time.Now().UTC()
		},
	}
}

// NewDefault creates a service with the baseline deterministic strategy.
func NewDefault(logger *zap.Logger) *Service {
	return New(BaselineStrategy{}, logger)
}

// ChooseStream selects a stream id when optimization is enabled.
func (s *Service) ChooseStream(
	ctx context.Context,
	campaign *model.Campaign,
	rawClick *model.RawClick,
	visitorCode string,
	streams []model.Stream,
) (*Decision, error) {
	if campaign == nil || !campaign.IsOptimizationEnabled {
		return nil, ErrOptimizationDisabled
	}
	if len(streams) == 0 {
		return nil, ErrNoCandidates
	}

	features := BuildFeatureVector(campaign, rawClick, visitorCode, s.nowFn())

	candidates := make([]Candidate, 0, len(streams))
	for _, stream := range streams {
		candidates = append(candidates, Candidate{
			StreamID: stream.ID,
			Weight:   stream.Weight,
			Position: stream.Position,
		})
	}

	scores, err := s.strategy.Score(ctx, features, candidates)
	if err != nil {
		return nil, err
	}

	selected := pickBestCandidate(candidates, scores)
	scoreMap := make(map[string]float64, len(scores))
	for id, score := range scores {
		scoreMap[id.String()] = score
	}

	decision := &Decision{
		SelectedStreamID: selected.StreamID,
		Strategy:         s.strategy.Name(),
		Features:         features,
		Scores:           scoreMap,
	}

	if s.logger != nil {
		s.logger.Debug(
			"optimizer decision",
			zap.String("campaign_id", features.CampaignID),
			zap.String("strategy", decision.Strategy),
			zap.String("stream_id", decision.SelectedStreamID.String()),
		)
	}

	return decision, nil
}

// BaselineStrategy is a deterministic score baseline until model-backed scoring is introduced.
type BaselineStrategy struct{}

func (BaselineStrategy) Name() string { return "baseline-weighted" }

func (BaselineStrategy) Score(_ context.Context, features FeatureVector, candidates []Candidate) (map[uuid.UUID]float64, error) {
	scores := make(map[uuid.UUID]float64, len(candidates))
	for _, candidate := range candidates {
		base := float64(candidate.Weight)
		if base <= 0 {
			base = 1
		}

		if features.IsUniqueCampaign {
			base += 0.25
		}
		if strings.EqualFold(features.OptimizationMetric, "EPC") {
			base += 0.5
		}
		if strings.EqualFold(features.DeviceType, "mobile") {
			base += 0.05
		}
		if strings.EqualFold(features.CountryCode, "US") {
			base += 0.05
		}

		scores[candidate.StreamID] = base
	}

	return scores, nil
}

func pickBestCandidate(candidates []Candidate, scores map[uuid.UUID]float64) Candidate {
	sorted := append([]Candidate(nil), candidates...)
	sort.Slice(sorted, func(i, j int) bool {
		a := sorted[i]
		b := sorted[j]

		scoreA := scores[a.StreamID]
		scoreB := scores[b.StreamID]
		if scoreA != scoreB {
			return scoreA > scoreB
		}
		if a.Position != b.Position {
			return a.Position < b.Position
		}
		return a.StreamID.String() < b.StreamID.String()
	})

	return sorted[0]
}
