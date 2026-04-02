package server

import (
	"encoding/json"
	"net/http"

	"go.uber.org/zap"
)

// adminAuth middleware verifies the X-API-Key header against configuration.
func (s *Server) adminAuth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		apiKey := r.Header.Get("X-API-Key")

		// In debug mode, if AdminAPIKey is empty, allow all (for development)
		if s.cfg.System.AdminAPIKey == "" && s.cfg.System.Debug {
			next.ServeHTTP(w, r)
			return
		}

		if apiKey == "" || apiKey != s.cfg.System.AdminAPIKey {
			s.errorResponse(w, r, http.StatusUnauthorized, "invalid or missing API key")
			return
		}

		next.ServeHTTP(w, r)
	})
}

// jsonResponse sends a JSON response with the given status code.
func (s *Server) jsonResponse(w http.ResponseWriter, r *http.Request, status int, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	if data != nil {
		if err := json.NewEncoder(w).Encode(data); err != nil {
			s.logger.Error("failed to encode json response", zap.Error(err))
		}
	}
}

// errorResponse sends a JSON error response.
func (s *Server) errorResponse(w http.ResponseWriter, r *http.Request, status int, message string) {
	s.jsonResponse(w, r, status, map[string]string{"error": message})
}
