package handler

import (
	"encoding/json"
	"net/http"

	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// HandleListLandings returns a paginated list of landings.
func (h *Handler) HandleListLandings(w http.ResponseWriter, r *http.Request) {
	limit, offset := h.parsePagination(r)

	landings, err := h.landings.List(r.Context(), limit, offset)
	if err != nil {
		h.logger.Error("list landings failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list landings")
		return
	}

	h.respondJSON(w, http.StatusOK, landings)
}

// HandleGetLanding returns a single landing by ID.
func (h *Handler) HandleGetLanding(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid landing id")
		return
	}

	cl, err := h.landings.GetByID(r.Context(), id)
	if err != nil {
		h.logger.Error("get landing failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "landing not found")
		return
	}

	h.respondJSON(w, http.StatusOK, cl)
}

// HandleCreateLanding creates a new landing.
func (h *Handler) HandleCreateLanding(w http.ResponseWriter, r *http.Request) {
	var l model.Landing
	if err := json.NewDecoder(r.Body).Decode(&l); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if l.Name == "" || l.URL == "" {
		h.respondError(w, http.StatusBadRequest, "name and url are required")
		return
	}

	if err := h.landings.Create(r.Context(), &l); err != nil {
		h.logger.Error("create landing failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to create landing")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, l)
}

// HandleUpdateLanding updates an existing landing.
func (h *Handler) HandleUpdateLanding(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid landing id")
		return
	}

	var l model.Landing
	if err := json.NewDecoder(r.Body).Decode(&l); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	l.ID = id

	if err := h.landings.Update(r.Context(), &l); err != nil {
		h.logger.Error("update landing failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to update landing")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, l)
}

// HandleDeleteLanding deletes a landing.
func (h *Handler) HandleDeleteLanding(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid landing id")
		return
	}

	if err := h.landings.Delete(r.Context(), id); err != nil {
		h.logger.Error("delete landing failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to delete landing")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusNoContent, nil)
}
