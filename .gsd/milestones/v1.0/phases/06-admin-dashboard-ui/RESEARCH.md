---
phase: 6
level: 2
researched_at: 2026-04-03
---

# Phase 6 Research

## Questions Investigated
1. What is the optimal architecture for embedding a Vite/React router SPA into a go-chi backend ensuring proper fallback for client-side routing?
2. What are the specific authentication constraints inside the React Client connecting to the Go API?
3. How to seamlessly handle local development proxying (Vite -> Go) without CORS issues?

## Findings

### Embedding SPA in go-chi
Go 1.16+ `//go:embed` is the standard approach. We can embed the `dist/` directory from Vite. For `go-chi`, we must serve the embedded files as a wildcard route `/` but since it's a Single Page Application (SPA), any route not matching an existing API endpoint or static file must return `index.html`. 

**Sources:**
- `internal/server/routes.go` structure
- standard `http.FileServer` behaviors in Go combined with SPA fallbacks

**Recommendation:** Create a custom Catch-All handler in `internal/server/embed.go` that intercepts requests. If the file exists in the embedded filesystem, it serves it. Otherwise, it serves `index.html`. Make sure this Catch-All handler is registered *after* all `/api/*` and `/postback/*` routes.

### Authentication Constraints
The API uses API Keys, not session cookies. Upon inspection of `internal/admin/middleware.go`:
```go
apiKey := r.Header.Get("X-Api-Key")
if apiKey == "" {
    http.Error(w, `{"error": "missing API key"}`, http.StatusUnauthorized)
    return
}
```

**Recommendation:** The React application client (Axios) must intercept all outgoing requests to `/api/v1/*` and inject the `X-Api-Key` header from local storage. The `LoginGuard` component will simply prompt the user to input this API key and store it.

### Local Development Proxying
To develop the React app without building the Go binary every time, Vite's dev server running on port `5173` must proxy requests to the Go backend on port `8105`.

**Recommendation:** In `vite.config.ts`, configure the `server.proxy` to forward `/api` and `/postback` to `http://localhost:8105`. This eliminates CORS issues and perfectly mimics the production environment where the UI and API share the same origin.

## Decisions Made
| Decision | Choice | Rationale |
|----------|--------|-----------|
| SPA Routing | Custom `http.Handler` for embed.FS | Required to handle React Router (client-side routing) fallbacks |
| Authentication | `X-Api-Key` header via Axios | Required by `admin.APIKeyAuth` middleware |
| API Client | Axios + TanStack Query | Standard, robust, easy caching and global error interception |
| Styling | Tailwind v4 + shadcn/ui | Developer productivity, premium aesthetic capabilities natively |
| Dev Environment | Vite Proxy | Eliminates CORS configuration overhead for local development |

## Patterns to Follow
- Pass `X-Api-Key` header on all protected API requests.
- React Router `BrowserRouter` with nested UI routes.
- Abstract UI components separated from domain-specific views (e.g. `src/components/ui/` vs `src/pages/campaigns/`).
- Consistent API error handling catching 401s globally to clear the token and force re-login.

## Anti-Patterns to Avoid
- **Hardcoding base URLs:** Avoid hardcoding `http://localhost:8105/api/v1` in Axios. Use relative paths `/api/v1` to support both Vite proxy and embedded prod runtime.
- **Handling Sessions via Cookies:** Do not build cookie session logic. Go API strictly uses `X-Api-Key`.

## Dependencies Identified
| Package | Version | Purpose |
|---------|---------|---------|
| `axios` | latest | XHR requests & interceptors |
| `@tanstack/react-query` | latest | Remote data fetching, caching, and state management |
| `react-router-dom` | latest | SPA Routing |
| `tailwindcss` | ^4.0 | Styling |
| `lucide-react` | latest | Premium SVG icons matching shadcn |

## Risks
- **Go Embed Build Errors:** If `admin-ui/dist` is missing when building `go build`, `//go:embed` throws a fatal compiler error.
  **Mitigation:** Document that `npm run build` inside `admin-ui` must be run before Go build, or use conditional compilation/makefiles.

## Ready for Planning
- [x] Questions answered
- [x] Approach selected
- [x] Dependencies identified
