package pipeline

import (
	"context"
	"fmt"
	"net/http"

	"github.com/skyplix/zai-tds/internal/model"
)

// Stage is implemented by every pipeline step.
// Process receives the shared Payload and may modify it.
// Return an error to abort the pipeline with 500.
type Stage interface {
	Process(payload *Payload) error
	Name() string
}

// Response holds the final HTTP response to send to the visitor.
type Response struct {
	StatusCode  int
	RedirectURL string
	Body        []byte
	Headers     map[string]string
	ActionType  string
}

// Payload is the shared context threaded through all pipeline stages.
// Stages read from and write to Payload — it is the "current click state".
type Payload struct {
	// HTTP context
	Ctx     context.Context
	Request *http.Request
	Writer  http.ResponseWriter

	// Progressive click data
	RawClick *model.RawClick

	// Resolved entities (populated from Valkey/Postgres)
	Campaign *model.Campaign
	Stream   *model.Stream
	Offer    *model.Offer
	Landing  *model.Landing

	// Final response (set by ExecuteActionStage)
	Response *Response

	// Pipeline control
	// Abort = true stops stage iteration and sends the current response.
	Abort     bool
	AbortCode int // HTTP status code when aborting without a response body
}

// Pipeline runs an ordered slice of stages against a Payload.
type Pipeline struct {
	stages []Stage
}

// New creates a Pipeline from the given ordered stages.
func New(stages ...Stage) *Pipeline {
	return &Pipeline{stages: stages}
}

// Run executes each stage in order.
// Stops early if payload.Abort is set to true.
// Returns the first error encountered (from any stage).
func (p *Pipeline) Run(payload *Payload) error {
	for _, stage := range p.stages {
		if payload.Abort {
			break
		}
		if err := stage.Process(payload); err != nil {
			return fmt.Errorf("stage %s: %w", stage.Name(), err)
		}
	}
	return nil
}
