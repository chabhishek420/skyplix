package action

import (
	"bytes"
	"crypto/sha256"
	"encoding/hex"
	"fmt"
	"io"
	"net/http"
	"strings"
	"sync"
	"time"
)

const (
	MaxBodySize = 10 * 1024 * 1024 // 10MB limit
	DefaultTTL  = 60 * time.Second
)

type cacheEntry struct {
	body        []byte
	contentType string
	statusCode  int
	fetchedAt   time.Time
}

// RemoteProxyAction — Full reverse proxy for Safe Page cloaking.
// Fetches the target URL from the server side and serves it locally, with TTL caching.
type RemoteProxyAction struct {
	client *http.Client
	cache  sync.Map
	ttl    time.Duration
}

func NewRemoteProxyAction(ttl time.Duration) *RemoteProxyAction {
	if ttl <= 0 {
		ttl = DefaultTTL
	}
	return &RemoteProxyAction{
		client: &http.Client{Timeout: 10 * time.Second},
		ttl:    ttl,
	}
}

func (a *RemoteProxyAction) Type() string { return "Remote" }

func (a *RemoteProxyAction) Execute(w http.ResponseWriter, r *http.Request, ctx *ActionContext) error {
	url := ctx.RedirectURL
	key := a.cacheKey(url)

	// 1. Check cache
	if entry, ok := a.getCached(key); ok {
		a.serveCached(w, entry)
		return nil
	}

	// 2. Cache miss — fetch remote
	entry, err := a.fetchRemote(r, url)
	if err != nil {
		// 3. Graceful degradation: serve stale if exists
		if stale, ok := a.cache.Load(key); ok {
			a.serveCached(w, stale.(*cacheEntry))
			return nil
		}
		return err
	}

	// 4. Store and serve
	a.cache.Store(key, entry)
	a.serveCached(w, entry)
	return nil
}

func (a *RemoteProxyAction) cacheKey(url string) string {
	sum := sha256.Sum256([]byte(url))
	return hex.EncodeToString(sum[:])
}

func (a *RemoteProxyAction) getCached(key string) (*cacheEntry, bool) {
	val, ok := a.cache.Load(key)
	if !ok {
		return nil, false
	}
	entry := val.(*cacheEntry)
	if time.Since(entry.fetchedAt) > a.ttl {
		return nil, false
	}
	return entry, true
}

func (a *RemoteProxyAction) fetchRemote(r *http.Request, url string) (*cacheEntry, error) {
	req, err := http.NewRequestWithContext(r.Context(), "GET", url, nil)
	if err != nil {
		return nil, err
	}

	// Copy browser headers to look like a real user
	req.Header.Set("User-Agent", r.UserAgent())
	req.Header.Set("Referer", r.Referer())
	req.Header.Set("Accept-Language", r.Header.Get("Accept-Language"))

	resp, err := a.client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	// Limit body size to prevent OOM
	lr := &io.LimitedReader{R: resp.Body, N: MaxBodySize}
	body, err := io.ReadAll(lr)
	if err != nil && err != io.EOF {
		return nil, err
	}

	// Basic URL rewriting for relative paths
	if strings.HasPrefix(resp.Header.Get("Content-Type"), "text/html") {
		body = []byte(rewriteRelativeURLs(string(body), url))
	}

	return &cacheEntry{
		body:        body,
		contentType: resp.Header.Get("Content-Type"),
		statusCode:  resp.StatusCode,
		fetchedAt:   time.Now(),
	}, nil
}

func (a *RemoteProxyAction) serveCached(w http.ResponseWriter, entry *cacheEntry) {
	if entry.contentType != "" {
		w.Header().Set("Content-Type", entry.contentType)
	}
	// Copy useful cache-control/no-cache headers if needed
	w.Header().Set("X-Cache-Status", "HIT")
	w.WriteHeader(entry.statusCode)
	_, _ = io.Copy(w, bytes.NewReader(entry.body))
}

func rewriteRelativeURLs(html, baseURL string) string {
	// Find the base domain including protocol
	idx := strings.Index(baseURL[8:], "/")
	var domain string
	if idx != -1 {
		domain = baseURL[:idx+8]
	} else {
		domain = baseURL
	}

	// Rewrite src="/ and href="/
	html = strings.ReplaceAll(html, "src=\"/", fmt.Sprintf("src=\"%s/", domain))
	html = strings.ReplaceAll(html, "href=\"/", fmt.Sprintf("href=\"%s/", domain))
	html = strings.ReplaceAll(html, "src='/", fmt.Sprintf("src='%s/", domain))
	html = strings.ReplaceAll(html, "href='/", fmt.Sprintf("href='%s/", domain))
	return html
}
