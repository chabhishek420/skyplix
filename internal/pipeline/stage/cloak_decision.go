package stage

import (
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/cloak"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

type CloakDecision struct {
	detector *cloak.Detector
	logger   *zap.Logger
}

func NewCloakDecision(detector *cloak.Detector, logger *zap.Logger) *CloakDecision {
	return &CloakDecision{
		detector: detector,
		logger:   logger,
	}
}

func (s *CloakDecision) Name() string {
	return "cloak_decision"
}

func (s *CloakDecision) AlwaysRun() bool {
	return false
}

func (s *CloakDecision) Process(p *pipeline.Payload) error {
	if p == nil || p.RawClick == nil {
		return nil
	}

	rc := p.RawClick

	if rc.IsBot {
		s.logger.Debug("already marked as bot",
			zap.String("reason", rc.BotReason),
		)
		// Don't hard-abort - let SafePageAction handle it if configured
		return nil
	}

	vpn, reason := s.detector.CheckIPQuality(rc.IP)
	if vpn {
		rc.IsBot = true
		rc.BotReason = reason
		s.logger.Info("cloaking: vpn/tor detected",
			zap.String("ip", rc.IP.String()),
			zap.String("reason", reason),
		)
		// Don't hard-abort - let SafePageAction handle it if configured
		return nil
	}

	if s.hasCloakCookie(p) {
		s.logger.Debug("cloak cookie verified")
		return nil
	}

	return nil
}

func (s *CloakDecision) hasCloakCookie(p *pipeline.Payload) bool {
	if p == nil || p.Request == nil {
		return false
	}
	cookie := p.Request.Header.Get("Cookie")
	return len(cookie) > 0 && containsSubstring(cookie, "cloak_verified=")
}

func containsSubstring(s, substr string) bool {
	for i := 0; i <= len(s)-len(substr); i++ {
		if s[i:i+len(substr)] == substr {
			return true
		}
	}
	return false
}
