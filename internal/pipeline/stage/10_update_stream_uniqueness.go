package stage

import (
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/session"
)

// UpdateStreamUniquenessStage checks if this visitor has visited this stream before.
type UpdateStreamUniquenessStage struct {
	Session *session.Service
	Logger  *zap.Logger
}

func (s *UpdateStreamUniquenessStage) Name() string      { return "UpdateStreamUniqueness" }
func (s *UpdateStreamUniquenessStage) AlwaysRun() bool { return false }

func (s *UpdateStreamUniquenessStage) Process(p *pipeline.Payload) error {
	if p.Stream == nil || p.VisitorCode == "" {
		return nil
	}

	isUnique, err := s.Session.CheckStreamUniqueness(p.Ctx, p.VisitorCode, p.Stream.ID)
	if err != nil {
		s.Logger.Error("check stream uniqueness error", zap.Error(err), zap.String("visitor", p.VisitorCode))
		p.RawClick.IsUniqueStream = false
		return nil
	}

	p.RawClick.IsUniqueStream = isUnique
	return nil
}
