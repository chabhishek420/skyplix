package stage

import (
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/cookie"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/session"
)

// UpdateGlobalUniquenessStage checks if this visitor has EVER visited the TDS before.
type UpdateGlobalUniquenessStage struct {
	Session *session.Service
	Logger  *zap.Logger
}

func (s *UpdateGlobalUniquenessStage) Name() string    { return "UpdateGlobalUniqueness" }
func (s *UpdateGlobalUniquenessStage) AlwaysRun() bool { return false }

func (s *UpdateGlobalUniquenessStage) Process(p *pipeline.Payload) error {
	// Ensure VisitorCode is set
	if p.VisitorCode == "" {
		code, _ := cookie.GetOrCreateVisitorCode(p.Request)
		p.VisitorCode = code
	}

	isUnique, err := s.Session.CheckGlobalUniqueness(p.Ctx, p.VisitorCode)
	if err != nil {
		s.Logger.Error("check global uniqueness error", zap.Error(err), zap.String("visitor", p.VisitorCode))
		p.RawClick.IsUniqueGlobal = false
		return nil
	}

	p.RawClick.IsUniqueGlobal = isUnique
	return nil
}
