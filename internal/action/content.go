package action

import (
	"fmt"
	"net/http"
)

// FrameAction — Display the redirect URL inside a full-screen iframe.
type FrameAction struct{}
func (a *FrameAction) Type() string { return "Frame" }
func (a *FrameAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.Header().Set("Content-Type", "text/html")
	fmt.Fprintf(w, "<style>body{margin:0;padding:0}iframe{width:100%%;height:100vh;border:none}</style><iframe src=\"%s\"></iframe>", ctx.RedirectURL)
	return nil
}

// IframeAction — Same as FrameAction (alias).
type IframeAction struct{}
func (a *IframeAction) Type() string { return "Iframe" }
func (a *IframeAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	return (&FrameAction{}).Execute(w, r, ctx)
}

// ShowHtmlAction — Serve custom raw HTML from the stream's action_payload.
type ShowHtmlAction struct{}
func (a *ShowHtmlAction) Type() string { return "ShowHtml" }
func (a *ShowHtmlAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.Header().Set("Content-Type", "text/html")
	if html, ok := ctx.Stream.ActionPayload["html"].(string); ok {
		fmt.Fprint(w, html)
	}
	return nil
}

// ShowTextAction — Serve plain text.
type ShowTextAction struct{}
func (a *ShowTextAction) Type() string { return "ShowText" }
func (a *ShowTextAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.Header().Set("Content-Type", "text/plain")
	if text, ok := ctx.Stream.ActionPayload["text"].(string); ok {
		fmt.Fprint(w, text)
	}
	return nil
}

// LocalFileAction — Serve a file from local disk.
type LocalFileAction struct{}
func (a *LocalFileAction) Type() string { return "LocalFile" }
func (a *LocalFileAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	if path, ok := ctx.Stream.ActionPayload["path"].(string); ok {
		http.ServeFile(w, r, path)
	}
	return nil
}

// Status404Action — Returns a 404 response.
type Status404Action struct{}
func (a *Status404Action) Type() string { return "Status404" }
func (a *Status404Action) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	http.NotFound(w, r)
	return nil
}

// DoNothingAction — Returns 200 OK with empty body.
type DoNothingAction struct{}
func (a *DoNothingAction) Type() string { return "DoNothing" }
func (a *DoNothingAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.WriteHeader(http.StatusOK)
	return nil
}

// CurlAction — Remote fetch and serve (simplified).
type CurlAction struct{}
func (a *CurlAction) Type() string { return "Curl" }
func (a *CurlAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	// Synchronous external fetch. Reserved for safe page usage.
	resp, err := http.Get(ctx.RedirectURL)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	for k, v := range resp.Header {
		w.Header()[k] = v
	}
	w.WriteHeader(resp.StatusCode)
	fmt.Fprint(w, resp.Body)
	return nil
}
