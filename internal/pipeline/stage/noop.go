package stage

import (
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// NoOpStage is a placeholder for pipeline stages not yet implemented.
// Used for stages 7-12 and 14-19 in Phase 1.
// Each stage logs its existence at debug level so the pipeline trace is visible.
type NoOpStage struct {
	StageName   string
	StageNumber int
	logger      *zap.Logger
}

func NewNoOp(number int, name string, logger *zap.Logger) *NoOpStage {
	return &NoOpStage{
		StageName:   name,
		StageNumber: number,
		logger:      logger,
	}
}

func (s *NoOpStage) Name() string { return s.StageName }
func (s *NoOpStage) AlwaysRun() bool { return false }

func (s *NoOpStage) Process(payload *pipeline.Payload) error {
	s.logger.Debug("stage no-op",
		zap.Int("stage", s.StageNumber),
		zap.String("name", s.StageName),
		zap.String("note", "not implemented in Phase 1"),
	)
	return nil
}
