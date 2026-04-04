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

const SystemDefaultWorkspaceID = "00000000-0000-4000-a000-000000000001"

// getWorkspaceID extracts the current workspace ID from the request context or headers.
// For Phase 1, it returns a fixed default UUID or a header value if present.
func (h *Handler) getWorkspaceID(r *http.Request) uuid.UUID {
	// 1. Try X-Workspace-ID header
	if wsIDStr := r.Header.Get("X-Workspace-ID"); wsIDStr != "" {
		if id, err := uuid.Parse(wsIDStr); err == nil {
			return id
		}
	}

	// 2. Fallback to System Default
	return uuid.MustParse(SystemDefaultWorkspaceID)
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
