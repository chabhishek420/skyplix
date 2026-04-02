package handler

import (
	"encoding/json"
	"net/http"

	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// HandleListCampaigns returns a paginated list of campaigns.
func (h *Handler) HandleListCampaigns(w http.ResponseWriter, r *http.Request) {
	limit, offset := h.parsePagination(r)

	campaigns, err := h.campaigns.List(r.Context(), limit, offset)
	if err != nil {
		h.logger.Error("list campaigns failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list campaigns")
		return
	}

	h.respondJSON(w, http.StatusOK, campaigns)
}

// HandleGetCampaign returns a single campaign by ID.
func (h *Handler) HandleGetCampaign(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid campaign id")
		return
	}

	c, err := h.campaigns.GetByID(r.Context(), id)
	if err != nil {
		h.logger.Error("get campaign failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "campaign not found")
		return
	}

	h.respondJSON(w, http.StatusOK, c)
}

// HandleCreateCampaign creates a new campaign.
func (h *Handler) HandleCreateCampaign(w http.ResponseWriter, r *http.Request) {
	var c model.Campaign
	if err := json.NewDecoder(r.Body).Decode(&c); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if c.Alias == "" || c.Name == "" {
		h.respondError(w, http.StatusBadRequest, "alias and name are required")
		return
	}

	if err := h.campaigns.Create(r.Context(), &c); err != nil {
		h.logger.Error("create campaign failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to create campaign")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, c)
}

// HandleUpdateCampaign updates an existing campaign.
func (h *Handler) HandleUpdateCampaign(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid campaign id")
		return
	}

	var c model.Campaign
	if err := json.NewDecoder(r.Body).Decode(&c); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	c.ID = id

	if err := h.campaigns.Update(r.Context(), &c); err != nil {
		h.logger.Error("update campaign failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to update campaign")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, c)
}

// HandleDeleteCampaign deletes a campaign.
func (h *Handler) HandleDeleteCampaign(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid campaign id")
		return
	}

	if err := h.campaigns.Delete(r.Context(), id); err != nil {
		h.logger.Error("delete campaign failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to delete campaign")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusNoContent, nil)
}
