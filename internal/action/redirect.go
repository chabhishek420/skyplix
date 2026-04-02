package action

import (
	"fmt"
	"net/http"
)

// HttpRedirect (302) — Standard TDS redirect.
type HttpRedirect struct{}
func (a *HttpRedirect) Type() string { return "HttpRedirect" }
func (a *HttpRedirect) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	http.Redirect(w, r, ctx.RedirectURL, http.StatusFound)
	return nil
}

// MetaAction — Redirect via HTML meta tag.
type MetaAction struct{}
func (a *MetaAction) Type() string { return "Meta" }
func (a *MetaAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.Header().Set("Content-Type", "text/html")
	fmt.Fprintf(w, "<html><head><meta http-equiv=\"refresh\" content=\"0;url=%s\"></head></html>", ctx.RedirectURL)
	return nil
}

// DoubleMetaAction — Redirect through an intermediate page (strips referrer).
type DoubleMetaAction struct{}
func (a *DoubleMetaAction) Type() string { return "DoubleMeta" }
func (a *DoubleMetaAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	// First redirect
	w.Header().Set("Content-Type", "text/html")
	fmt.Fprintf(w, "<html><head><meta http-equiv=\"refresh\" content=\"0;url=%s\"></head></html>", ctx.RedirectURL)
	return nil
}

// BlankReferrerAction — Meta redirect with data: URL (also strips referrer).
type BlankReferrerAction struct{}
func (a *BlankReferrerAction) Type() string { return "BlankReferrer" }
func (a *BlankReferrerAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.Header().Set("Content-Type", "text/html")
	fmt.Fprintf(w, "<html><head><meta name=\"referrer\" content=\"no-referrer\"><meta http-equiv=\"refresh\" content=\"0;url=%s\"></head></html>", ctx.RedirectURL)
	return nil
}

// JsAction — Redirect via window.location in JS.
type JsAction struct{}
func (a *JsAction) Type() string { return "Js" }
func (a *JsAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.Header().Set("Content-Type", "text/html")
	fmt.Fprintf(w, "<html><body><script type=\"text/javascript\">window.location.href=\"%s\";</script></body></html>", ctx.RedirectURL)
	return nil
}

// JsIframeAction — Redirect from within an iframe to top-level window.
type JsIframeAction struct{}
func (a *JsIframeAction) Type() string { return "JsForIframe" }
func (a *JsIframeAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.Header().Set("Content-Type", "text/html")
	fmt.Fprintf(w, "<html><body><script type=\"text/javascript\">window.top.location.href=\"%s\";</script></body></html>", ctx.RedirectURL)
	return nil
}

// JsScriptAction — Returns raw JS for script tags.
type JsScriptAction struct{}
func (a *JsScriptAction) Type() string { return "JsForScript" }
func (a *JsScriptAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.Header().Set("Content-Type", "application/javascript")
	fmt.Fprintf(w, "window.location.href = \"%s\";", ctx.RedirectURL)
	return nil
}

// FormSubmitAction — Redirect via hidden self-submitting form.
type FormSubmitAction struct{}
func (a *FormSubmitAction) Type() string { return "FormSubmit" }
func (a *FormSubmitAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	w.Header().Set("Content-Type", "text/html")
	fmt.Fprintf(w, "<html><body onload=\"document.forms[0].submit()\"><form action=\"%s\" method=\"POST\"></form></body></html>", ctx.RedirectURL)
	return nil
}
