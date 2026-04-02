package handler

import (
	"encoding/json"
	"net/http"

	"github.com/google/uuid"
	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// HandleListSources returns a paginated list of traffic sources.
func (h *Handler) HandleListSources(w http.ResponseWriter, r *http.Request) {
	limit, offset := h.parsePagination(r)

	sources, err := h.sources.List(r.Context(), limit, offset)
	if err != nil {
		h.logger.Error("list traffic sources failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list traffic sources")
		return
	}

	h.respondJSON(w, http.StatusOK, sources)
}

// HandleGetSource returns a single traffic source by ID.
func (h *Handler) HandleGetSource(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid source id")
		return
	}

	s, err := h.sources.GetByID(r.Context(), id)
	if err != nil {
		h.logger.Error("get traffic source failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "traffic source not found")
		return
	}

	h.respondJSON(w, http.StatusOK, s)
}

// HandleCreateSource creates a new traffic source.
func (h *Handler) HandleCreateSource(w http.ResponseWriter, r *http.Request) {
	var s model.TrafficSource
	if err := json.NewDecoder(r.Body).Decode(&s); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if s.Name == "" {
		h.respondError(w, http.StatusBadRequest, "name is required")
		return
	}

	if err := h.sources.Create(r.Context(), &s); err != nil {
		h.logger.Error("create traffic source failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to create traffic source")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, s)
}

// HandleUpdateSource updates an existing traffic source.
func (h *Handler) HandleUpdateSource(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid source id")
		return
	}

	var s model.TrafficSource
	if err := json.NewDecoder(r.Body).Decode(&s); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	s.ID = id

	if err := h.sources.Update(r.Context(), &s); err != nil {
		h.logger.Error("update traffic source failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to update traffic source")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, s)
}

// HandleDeleteSource deletes a traffic source.
func (h *Handler) HandleDeleteSource(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid source id")
		return
	}

	if err := h.sources.Delete(r.Context(), id); err != nil {
		h.logger.Error("delete traffic source failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to delete traffic source")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusNoContent, nil)
}

// HandleCloneSource duplicates a traffic source.
func (h *Handler) HandleCloneSource(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid source id")
		return
	}

	s, err := h.sources.GetByID(r.Context(), id)
	if err != nil {
		h.logger.Error("get traffic source failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "traffic source not found")
		return
	}

	// Clone logic
	s.ID = uuid.Nil
	s.Name = s.Name + " (copy)"
	s.State = "active"

	if err := h.sources.Create(r.Context(), s); err != nil {
		h.logger.Error("clone traffic source failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to clone traffic source")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, s)
}
