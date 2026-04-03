package handler

import (
	"encoding/json"
	"net/http"

	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// HandleListNetworks returns a paginated list of affiliate networks.
func (h *Handler) HandleListNetworks(w http.ResponseWriter, r *http.Request) {
	limit, offset := h.parsePagination(r)

	networks, err := h.networks.List(r.Context(), limit, offset)
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

	n, err := h.networks.GetByID(r.Context(), id)
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

	var n model.AffiliateNetwork
	if err := json.NewDecoder(r.Body).Decode(&n); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	n.ID = id

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

	n, err := h.networks.GetByID(r.Context(), id)
	if err != nil {
		h.logger.Error("get network failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "network not found")
		return
	}

	// Get global postback key
	key := ""
	if s, err := h.settings.Get(r.Context(), "postback_key"); err == nil {
		key = s.Value
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
		// If custom template exists, ensure it has the correct key
		// This is a simple replacement for illustration
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

	if err := h.networks.Delete(r.Context(), id); err != nil {
		h.logger.Error("delete network failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to delete network")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusNoContent, nil)
}
