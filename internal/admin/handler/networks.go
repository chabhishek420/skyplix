package handler

import (
	"encoding/json"
	"net/http"
	"strings"

	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/macro"
	"github.com/skyplix/zai-tds/internal/model"
)

// HandleListNetworks returns a paginated list of affiliate networks.
func (h *Handler) HandleListNetworks(w http.ResponseWriter, r *http.Request) {
	limit, offset := h.parsePagination(r)
	wsID := h.getWorkspaceID(r)

	networks, err := h.networks.List(r.Context(), wsID, limit, offset)
	if err != nil {
		h.logger.Error("list networks failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list networks")
		return
	}

	h.respondJSON(w, http.StatusOK, networks)
}

// HandleGetNetwork returns a single affiliate network by ID.
func (h *Handler) HandleGetNetwork(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid network id")
		return
	}
	wsID := h.getWorkspaceID(r)

	n, err := h.networks.GetByID(r.Context(), id, wsID)
	if err != nil {
		h.logger.Error("get network failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "network not found")
		return
	}

	h.respondJSON(w, http.StatusOK, n)
}

// HandleCreateNetwork creates a new affiliate network.
func (h *Handler) HandleCreateNetwork(w http.ResponseWriter, r *http.Request) {
	var n model.AffiliateNetwork
	if err := json.NewDecoder(r.Body).Decode(&n); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if n.Name == "" {
		h.respondError(w, http.StatusBadRequest, "name is required")
		return
	}
	n.WorkspaceID = h.getWorkspaceID(r)

	if err := h.networks.Create(r.Context(), &n); err != nil {
		h.logger.Error("create network failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to create network")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, n)
}

// HandleUpdateNetwork updates an existing affiliate network.
func (h *Handler) HandleUpdateNetwork(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid network id")
		return
	}
	wsID := h.getWorkspaceID(r)

	var n model.AffiliateNetwork
	if err := json.NewDecoder(r.Body).Decode(&n); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	n.ID = id
	n.WorkspaceID = wsID

	if err := h.networks.Update(r.Context(), &n); err != nil {
		h.logger.Error("update network failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to update network")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, n)
}

// HandleGeneratePostbackURL generates a postback URL template for an affiliate network.
func (h *Handler) HandleGeneratePostbackURL(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid network id")
		return
	}
	wsID := h.getWorkspaceID(r)

	n, err := h.networks.GetByID(r.Context(), id, wsID)
	if err != nil {
		h.logger.Error("get network failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "network not found")
		return
	}

	// Get global postback key
	key, err := h.settings.Get(r.Context(), "tracker.postback_key")
	if err != nil {
		h.logger.Warn("postback key lookup failed", zap.Error(err))
	}

	// Determine base URL
	baseURL := ""
	if host := r.Header.Get("X-Forwarded-Host"); host != "" {
		baseURL = "https://" + host
	} else {
		baseURL = "https://" + r.Host
	}

	postbackURL := n.PostbackURL
	if postbackURL == "" {
		postbackURL = macro.GeneratePostbackURL(baseURL, key)
	} else {
		// If custom template exists, allow replacing {key} with actual postback key
		postbackURL = strings.ReplaceAll(postbackURL, "{key}", key)
	}

	h.respondJSON(w, http.StatusOK, map[string]any{
		"postback_url": postbackURL,
		"macros":       macro.PostbackMacros,
	})
}

// HandleDeleteNetwork deletes an affiliate network.

func (h *Handler) HandleDeleteNetwork(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid network id")
		return
	}
	wsID := h.getWorkspaceID(r)

	if err := h.networks.Delete(r.Context(), id, wsID); err != nil {
		h.logger.Error("delete network failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to delete network")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusNoContent, nil)
}
