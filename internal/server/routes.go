package server

import (
	"encoding/json"
	"net/http"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
)

// routes wires all HTTP routes and returns the handler.
func (s *Server) routes() http.Handler {
	r := chi.NewRouter()

	// Global middleware
	r.Use(middleware.Recoverer)
	r.Use(middleware.RealIP)
	r.Use(s.requestLogger())

	// Admin / health routes
	r.Get("/api/v1/health", s.handleHealth)

	// Click traffic routes (hot path)
	// /{alias} — Level 1 pipeline (campaign click)
	r.Get("/{alias}", s.handleClick)
	r.Get("/", s.handleClick) // bare domain — gateway context

	return r
}

// handleHealth returns a JSON health check response.
func (s *Server) handleHealth(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{ //nolint:errcheck
		"status":  "ok",
		"version": s.version,
	})
}

// handleClick is the Level 1 click pipeline handler — stub for Plan 1.1.
// Full implementation in Plan 1.2.
func (s *Server) handleClick(w http.ResponseWriter, r *http.Request) {
	http.Error(w, "click pipeline not yet implemented", http.StatusNotImplemented)
}

// requestLogger returns a chi-compatible middleware that logs each request using zap.
func (s *Server) requestLogger() func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			ww := middleware.NewWrapResponseWriter(w, r.ProtoMajor)
			next.ServeHTTP(ww, r)
			s.logger.Sugar().Infow("request",
				"method", r.Method,
				"path", r.URL.Path,
				"status", ww.Status(),
				"bytes", ww.BytesWritten(),
				"remote_addr", r.RemoteAddr,
			)
		})
	}
}
