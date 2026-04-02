package handler

import (
	"encoding/json"
	"net/http"
	"strconv"

	"github.com/google/uuid"
)

// respondJSON writes a JSON response with the given status.
func (h *Handler) respondJSON(w http.ResponseWriter, status int, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(data)
}

// respondError writes a formatted JSON error response.
func (h *Handler) respondError(w http.ResponseWriter, status int, message string) {
	h.respondJSON(w, status, map[string]string{"error": message})
}

// parseUUID validates and returns a UUID from a string.
func (h *Handler) parseUUID(s string) (uuid.UUID, error) {
	return uuid.Parse(s)
}

// parsePagination extracts limit and offset from the request query.
func (h *Handler) parsePagination(r *http.Request) (limit, offset int) {
	limit = 25
	offset = 0

	if lStr := r.URL.Query().Get("limit"); lStr != "" {
		if l, err := strconv.Atoi(lStr); err == nil && l > 0 {
			limit = l
		}
	}

	if oStr := r.URL.Query().Get("offset"); oStr != "" {
		if o, err := strconv.Atoi(oStr); err == nil && o >= 0 {
			offset = o
		}
	}

	return limit, offset
}
