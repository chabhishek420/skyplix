/*
 * MODIFIED: internal/pipeline/stage/20_execute_action.go
 * PURPOSE: Resolves final redirect URL (Landing > Offer > ActionPayload)
 *          and executes the action. Added diagnostic logging.
 */
package stage

import (
	"errors"
	"net/http"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/action"
	"github.com/skyplix/zai-tds/internal/macro"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// ExecuteActionStage is the final routing stage.
// It decides between Landing, Offer, or direct Stream URL.
type ExecuteActionStage struct {
	ActionEngine *action.Engine
	Logger       *zap.Logger
}

func (s *ExecuteActionStage) Name() string    { return "ExecuteAction" }
func (s *ExecuteActionStage) AlwaysRun() bool { return false }

func (s *ExecuteActionStage) Process(payload *pipeline.Payload) error {
	if payload.Abort {
		return nil
	}

	stream := payload.Stream
	if stream == nil {
		s.Logger.Error("pipeline stream is nil in ExecuteActionStage")
		payload.Abort = true
		payload.Response = &pipeline.Response{StatusCode: http.StatusInternalServerError}
		return nil
	}

	// 1. Determine target URL hierarchy (Landing > Offer > action_payload)
	targetURL := ""
	if payload.Landing != nil {
		targetURL = payload.Landing.URL
	} else if payload.Offer != nil {
		targetURL = payload.Offer.URL
	} else if stream.ActionPayload != nil {
		if url, ok := stream.ActionPayload["url"].(string); ok {
			targetURL = url
		}
	}

	// 2. Perform macro replacement
	finalURL := ""
	if targetURL != "" {
		finalURL = macro.Replace(targetURL, payload.RawClick, payload.Campaign, payload.Offer)
	}

	// 3. Execute the action
	ctx := &action.ActionContext{
		RedirectURL: finalURL,
		Click:       payload.RawClick,
		Campaign:    payload.Campaign,
		Stream:      stream,
		Ctx:         payload.Ctx,
	}

	if _, ok := s.ActionEngine.Get(stream.ActionType); !ok {
		// Log warning but let ActionEngine handle the fallback
		s.Logger.Warn("unknown action type, falling back to HttpRedirect", zap.String("type", stream.ActionType))
	}

	if payload.IsSimulation {
		payload.AddTrace("Action [%s] would be executed with URL [%s]", stream.ActionType, finalURL)
		payload.Abort = true
		return nil
	}

	err := s.ActionEngine.Execute(stream.ActionType, payload.Writer, payload.Request, ctx)
	if err != nil {
		if errors.Is(err, action.ErrRedispatch) {
			s.Logger.Debug("internal campaign redispatch requested", zap.String("alias", payload.RawClick.CampaignAlias))
			payload.ReDispatch = true
			return nil // No abort, we want to re-run the loop in Pipeline.Run
		}
		s.Logger.Error("action execution failed", zap.Error(err), zap.String("type", stream.ActionType))
		return err
	}

	s.Logger.Debug("action executed", 
		zap.String("type", stream.ActionType),
		zap.String("target_url", targetURL),
		zap.String("final_url", finalURL),
	)

	// 4. Mark payload as finished
	payload.Abort = true
	payload.RawClick.ActionType = stream.ActionType
	
	// We set a response placeholder so the pipeline knows we've handled the client
	payload.Response = &pipeline.Response{
		StatusCode: http.StatusOK, // Status set by Action.Execute
		ActionType: stream.ActionType,
	}

	return nil
}
