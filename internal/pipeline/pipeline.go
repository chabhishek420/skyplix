package pipeline

import (
	"context"
	"fmt"
	"net/http"
	"time"

	"github.com/skyplix/zai-tds/internal/metrics"
	"github.com/skyplix/zai-tds/internal/model"
)

// Stage is implemented by every pipeline step.
// Process receives the shared Payload and may modify it.
// Return an error to abort the pipeline with 500.
type Stage interface {
	Process(payload *Payload) error
	Name() string
	// AlwaysRun returns true for stages that must execute even after Abort is set
	// (e.g., StoreRawClicks must fire even after ExecuteAction sends the HTTP redirect).
	AlwaysRun() bool
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
	Campaign         *model.Campaign
	Stream           *model.Stream
	Offer            *model.Offer
	Landing          *model.Landing
	AffiliateNetwork *model.AffiliateNetwork

	// Visitor identification
	VisitorCode string

	// Final response (set by ExecuteActionStage)
	Response *Response

	// Pipeline control
	// Abort = true stops stage iteration and sends the current response.
	Abort     bool
	AbortCode int // HTTP status code when aborting without a response body

	// Re-dispatch control (for recursion like ToCampaign)
	ReDispatch bool
	Hops       int
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
// Stops early if payload.Abort is set to true, UNLESS the stage implements
// AlwaysRun() == true (used for storage stages that must fire after redirect).
// Supports internal re-dispatch (recursive campaign entry) up to 10 hops.
func (p *Pipeline) Run(payload *Payload) error {
	for {
		for _, stage := range p.stages {
			if payload.Abort && !stage.AlwaysRun() {
				continue
			}
			startStage := time.Now()
			err := stage.Process(payload)
			metrics.PipelineStagesDuration.WithLabelValues(stage.Name()).Observe(time.Since(startStage).Seconds())
			
			if err != nil {
				return fmt.Errorf("stage %s: %w", stage.Name(), err)
			}
		}

		// Check for internal re-dispatch
		if payload.ReDispatch {
			if payload.Hops >= 10 {
				return fmt.Errorf("too many campaign hops (max 10)")
			}

			// Prepare for next iteration
			payload.Hops++
			payload.ReDispatch = false
			payload.Abort = false
			payload.Campaign = nil
			payload.Stream = nil
			payload.Offer = nil
			payload.Landing = nil
			// We DO NOT clear RawClick, as it carries the new CampaignAlias to resolve.
			continue
		}

		// Exit loop if no redispatch was requested after a full run
		break
	}
	return nil
}
