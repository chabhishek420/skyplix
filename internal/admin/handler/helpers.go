package handler

import (
	"encoding/json"
	"net/http"
	"strconv"

	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/auth"
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
// It enforces that the authenticated user has access to the requested workspace.
func (h *Handler) getWorkspaceID(r *http.Request) uuid.UUID {
	defaultID := uuid.MustParse(SystemDefaultWorkspaceID)

	// 1. Get claims from context
	claims, ok := r.Context().Value(auth.UserClaimsKey).(*auth.UserClaims)
	if !ok {
		// Fallback for public or unauthenticated (should be blocked by middleware usually)
		return defaultID
	}

	// 2. Try X-Workspace-ID header
	var requestedID uuid.UUID
	if wsIDStr := r.Header.Get("X-Workspace-ID"); wsIDStr != "" {
		if id, err := uuid.Parse(wsIDStr); err == nil {
			requestedID = id
		}
	}

	if requestedID == uuid.Nil {
		// Default to system workspace if user has access, otherwise first available
		if h.userHasWorkspace(claims, defaultID) {
			return defaultID
		}
		if len(claims.WorkspaceIDs) > 0 {
			return claims.WorkspaceIDs[0]
		}
		return defaultID
	}

	// 3. Enforce access control
	if h.userHasWorkspace(claims, requestedID) {
		return requestedID
	}

	// Fallback to first available on mismatch (or could return error in future)
	if len(claims.WorkspaceIDs) > 0 {
		return claims.WorkspaceIDs[0]
	}

	return defaultID
}

func (h *Handler) userHasWorkspace(claims *auth.UserClaims, wsID uuid.UUID) bool {
	if claims.Role == "administrator" || claims.Role == "owner" || claims.Role == "admin" {
		return true // Global access
	}
	for _, id := range claims.WorkspaceIDs {
		if id == wsID {
			return true
		}
	}
	return false
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
