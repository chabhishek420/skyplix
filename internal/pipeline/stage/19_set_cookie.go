package stage

import (
	"github.com/skyplix/zai-tds/internal/cookie"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// SetCookieStage sets visitor identification cookies in the HTTP response.
type SetCookieStage struct{}

func (s *SetCookieStage) Name() string      { return "SetCookie" }
func (s *SetCookieStage) AlwaysRun() bool { return false }

func (s *SetCookieStage) Process(p *pipeline.Payload) error {
	if p.VisitorCode != "" {
		cookie.SetVisitorCodeCookie(p.Writer, p.VisitorCode)
	}
	
	// Add other cookie setting logic if needed (e.g., session cookies)
	
	return nil
}
