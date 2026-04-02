package action

import (
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"
)

// RemoteProxyAction — Full reverse proxy for Safe Page cloaking.
// Fetches the target URL from the server side and serves it locally.
type RemoteProxyAction struct {
	client *http.Client
}

func (a *RemoteProxyAction) Type() string { return "Remote" }

func (a *RemoteProxyAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	if a.client == nil {
		a.client = &http.Client{Timeout: 10 * time.Second}
	}

	req, err := http.NewRequestWithContext(ctx.Ctx, "GET", ctx.RedirectURL, nil)
	if err != nil {
		return err
	}

	// Copy essential headers
	req.Header.Set("User-Agent", r.UserAgent())
	req.Header.Set("Referer", r.Referer())

	resp, err := a.client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	// Copy response headers (selective)
	for k, vv := range resp.Header {
		if k == "Content-Type" || k == "Cache-Control" {
			for _, v := range vv {
				w.Header().Add(k, v)
			}
		}
	}
	w.WriteHeader(resp.StatusCode)

	// Stream response body
	_, err = io.Copy(w, resp.Body)
	return err
}

func rewriteRelativeURLs(html, baseURL string) string {
	// Simple string replacement for common relative patterns
	// Future: use a parser (golang.org/x/net/html) for real rewriting
	return strings.ReplaceAll(html, "src=\"/", fmt.Sprintf("src=\"%s/", baseURL))
}
