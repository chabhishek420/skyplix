package stage

import (
	"net/http"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// CheckPrefetchStage — Pipeline Stage 2
// Detects browser prefetch requests (Purpose: prefetch, X-Moz: prefetch).
// Prefetch requests silently return 200 — they must NOT be counted as real clicks.
type CheckPrefetchStage struct{}

func (s *CheckPrefetchStage) Name() string { return "CheckPrefetch" }

func (s *CheckPrefetchStage) Process(payload *pipeline.Payload) error {
	r := payload.Request

	purpose := r.Header.Get("Purpose")
	xMoz := r.Header.Get("X-Moz")
	secPurpose := r.Header.Get("Sec-Purpose")

	if purpose == "prefetch" || xMoz == "prefetch" || secPurpose == "prefetch" {
		payload.Abort = true
		payload.AbortCode = http.StatusOK
	}
	return nil
}
