/*
 * MODIFIED: internal/action/action.go
 * PURPOSE: Implemented case-insensitive (Title Case) normalization for action 
 *          registration and lookups to prevent configuration-level casing errors.
 */
package action

import (
	"context"
	"net/http"
	"strings"

	"github.com/skyplix/zai-tds/internal/model"
)

// Action is the interface implemented by all TDS routing action types.
type Action interface {
	Type() string
	Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error
}

// ActionContext provides the necessary data for an action to execute.
type ActionContext struct {
	RedirectURL string // Final URL after macro replacement
	Click       *model.RawClick
	Campaign    *model.Campaign
	Stream      *model.Stream
	Ctx         context.Context
}

// Engine registry of all supported action types.
type Engine struct {
	actions map[string]Action
}

// NewEngine registers all 19 standard TDS action types.
func NewEngine() *Engine {
	e := &Engine{
		actions: make(map[string]Action),
	}

	// Register groupings
	e.register(
		// redirect.go
		&HttpRedirect{}, &MetaAction{}, &DoubleMetaAction{}, &BlankReferrerAction{},
		&JsAction{}, &JsIframeAction{}, &JsScriptAction{}, &FormSubmitAction{},
		// content.go
		&FrameAction{}, &IframeAction{}, &ShowHtmlAction{}, &ShowTextAction{}, 
		&LocalFileAction{}, &Status404Action{}, &DoNothingAction{}, &CurlAction{},
		// proxy.go
		NewRemoteProxyAction(0),
		// special.go
		&SubIdAction{}, &ToCampaignAction{},
	)

	return e
}

func (e *Engine) register(actions ...Action) {
	for _, a := range actions {
		e.actions[strings.Title(strings.ToLower(a.Type()))] = a
	}
}

// Get looks up an action by type.
func (e *Engine) Get(actionType string) (Action, bool) {
	a, ok := e.actions[strings.Title(strings.ToLower(actionType))]
	return a, ok
}

// Execute looks up and runs the specified action type.
func (e *Engine) Execute(actionType string, w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	a, ok := e.Get(actionType)
	if !ok {
		// Default fallback to HTTP 302 if action unknown
		a = &HttpRedirect{}
	}
	return a.Execute(w, r, ctx)
}
