package server

import (
	"io/fs"
	"net/http"
	"strings"

	adminui "github.com/skyplix/zai-tds/admin-ui"
)

// handleSPA returns an http.Handler that serves the embedded React SPA.
// It falls back to serving index.html for unknown routes to support client-side routing.
func (s *Server) handleSPA() http.Handler {
	// The embed.FS contains a "dist" directory at its root
	distFS, err := fs.Sub(adminui.FS, "dist")
	if err != nil {
		s.logger.Warn("Failed to load embedded admin-ui dist directory (it may be empty). UI will not be served.")
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			http.Error(w, "Admin UI not compiled into binary", http.StatusNotFound)
		})
	}

	fileServer := http.FileServer(http.FS(distFS))

	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		p := r.URL.Path

		// Clean the path to look it up in the FS
		p = strings.TrimPrefix(p, "/admin")
		if !strings.HasPrefix(p, "/") {
			p = "/" + p
		}

		// If viewing the root of the admin UI, serve index.html directly
		if p == "/" {
			r.URL.Path = "/"
			fileServer.ServeHTTP(w, r)
			return
		}

		// Try to stat the requested file
		_, err := fs.Stat(distFS, strings.TrimPrefix(p, "/"))
		if err != nil {
			// If file does not exist (like /admin/campaigns), rewrite path to / so index.html is served
			r.URL.Path = "/"
		} else {
			// File exists (like /assets/index.js), serve it
			r.URL.Path = p
		}

		fileServer.ServeHTTP(w, r)
	})
}
