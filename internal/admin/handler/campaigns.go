package handler

import (
	"encoding/json"
	"fmt"
	"net/http"
	"strings"

	"github.com/go-chi/chi/v5"
	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/admin/repository"
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
	if err := normalizeCampaignOptimization(&c); err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
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
	if err := normalizeCampaignOptimization(&c); err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

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

// HandleCloneCampaign duplicates a campaign and all its associated streams/offers/landings.
func (h *Handler) HandleCloneCampaign(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid campaign id")
		return
	}

	ctx := r.Context()
	tx, err := h.db.Begin(ctx)
	if err != nil {
		h.logger.Error("begin tx failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to start transaction")
		return
	}
	defer tx.Rollback(ctx)

	// Create temporary repositories bound to the transaction
	txCampaigns := repository.NewCampaignRepository(tx)
	txStreams := repository.NewStreamRepository(tx)

	// 1. Get Source Campaign (for naming)
	source, err := h.campaigns.GetByID(ctx, id)
	if err != nil {
		h.respondError(w, http.StatusNotFound, "source campaign not found")
		return
	}

	// 2. Clone Campaign Record
	newCampaignID := uuid.New()
	newName := source.Name + " (Copy)"
	newAlias := source.Alias + "_copy"

	newCampaign, err := txCampaigns.Clone(ctx, id, newCampaignID, newName, newAlias)
	if err != nil {
		h.logger.Error("clone campaign repo call failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to clone campaign")
		return
	}

	// 3. Clone Streams
	streams, err := h.streams.ListByCampaign(ctx, id)
	if err != nil {
		h.logger.Error("clone campaign list streams failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list source streams")
		return
	}

	for _, s := range streams {
		if _, err := txStreams.Clone(ctx, s.ID, uuid.New(), newCampaign.ID, s.Name, s.Position); err != nil {
			h.logger.Error("clone stream repo call failed", zap.Error(err))
			h.respondError(w, http.StatusInternalServerError, "failed to clone campaign streams")
			return
		}
	}

	if err := tx.Commit(ctx); err != nil {
		h.logger.Error("clone campaign commit failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to commit clone")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, newCampaign)
}

func normalizeCampaignOptimization(c *model.Campaign) error {
	if c == nil {
		return nil
	}

	metric := strings.ToUpper(strings.TrimSpace(c.OptimizationMetric))
	if metric == "" {
		metric = "CR"
	}
	if metric != "CR" && metric != "EPC" {
		return fmt.Errorf("optimization_metric must be CR or EPC")
	}
	c.OptimizationMetric = metric

	if c.OptimizationPeriodHours == 0 {
		c.OptimizationPeriodHours = 24
	}
	if c.OptimizationPeriodHours > 24*30 {
		return fmt.Errorf("optimization_period_hours must be <= 720")
	}

	return nil
}
