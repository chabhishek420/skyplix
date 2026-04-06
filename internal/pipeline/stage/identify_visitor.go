package stage

import (
	"crypto/rand"
	"encoding/hex"
	"fmt"

	"github.com/skyplix/zai-tds/internal/cookie"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// IdentifyVisitorStage — Pipeline Stage 0.5
// Retrieves or generates the VisitorCode.
// Order: Cookie -> Query Parameter ({visitor_code}) -> New Generation.
type IdentifyVisitorStage struct{}

func (s *IdentifyVisitorStage) Name() string    { return "IdentifyVisitor" }
func (s *IdentifyVisitorStage) AlwaysRun() bool { return false }

func (s *IdentifyVisitorStage) Process(p *pipeline.Payload) error {
	// 1. Try cookie
	code := cookie.GetVisitorCode(p.Request)

	// 2. Try query param
	if code == "" {
		code = getQueryParam(p.Request.URL.RawQuery, "visitor_code", "vc")
	}

	// 3. Generate new if missing
	if code == "" {
		b := make([]byte, 16)
		if _, err := rand.Read(b); err != nil {
			return fmt.Errorf("generate visitor code: %w", err)
		}
		code = hex.EncodeToString(b)
	}

	p.VisitorCode = code
	if p.RawClick != nil {
		p.RawClick.VisitorCode = code
	}

	return nil
}
