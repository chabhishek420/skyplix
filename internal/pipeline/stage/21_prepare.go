package stage

import (
	"strings"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// PrepareRawClickToStoreStage — Pipeline Stage 21
// Performs final sanitization and normalization before ClickHouse storage.
type PrepareRawClickToStoreStage struct{}

func (s *PrepareRawClickToStoreStage) Name() string      { return "PrepareRawClickToStore" }
func (s *PrepareRawClickToStoreStage) AlwaysRun() bool   { return true } // Must run even after action abort

func (s *PrepareRawClickToStoreStage) Process(payload *pipeline.Payload) error {
	rc := payload.RawClick
	if rc == nil {
		return nil
	}

	// 1. Truncate long strings to database limits
	if len(rc.UserAgent) > 512 {
		rc.UserAgent = rc.UserAgent[:512]
	}
	if len(rc.Referrer) > 1024 {
		rc.Referrer = rc.Referrer[:1024]
	}

	// 2. Normalize country code
	rc.CountryCode = strings.ToUpper(rc.CountryCode)

	return nil
}
