package handler

import (
	"encoding/json"
	"net"
	"net/http"

	"github.com/go-chi/chi/v5"
	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/admin/repository"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
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

// HandleSimulateCampaign runs a virtual click through the pipeline and returns the decision trace.
// GET /api/v1/campaigns/{id}/simulate?ip=...&ua=...&ref=...
func (h *Handler) HandleSimulateCampaign(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid campaign id")
		return
	}

	campaign, err := h.campaigns.GetByID(r.Context(), id)
	if err != nil {
		h.respondError(w, http.StatusNotFound, "campaign not found")
		return
	}

	// Build a mock payload
	payload := &pipeline.Payload{
		Ctx:     r.Context(),
		Request: r,
		Writer:  nil, // Simulations don't write to network
		Trace:   make([]string, 0),
		RawClick: &model.RawClick{
			CampaignID:    campaign.ID,
			CampaignAlias: campaign.Alias,
			IP:            net.ParseIP(r.URL.Query().Get("ip")),
			UserAgent:     r.URL.Query().Get("ua"),
			Referrer:      r.URL.Query().Get("ref"),
		},
	}
	if payload.RawClick.IP == nil {
		payload.RawClick.IP = net.ParseIP("1.2.3.4")
	}
	if payload.RawClick.UserAgent == "" {
		payload.RawClick.UserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
	}

	// Run simulation (we use a custom pipeline that skips storage)
	// For now, we'll just run the standard L1 pipeline but we'd normally want a 'safe' version
	// We'll mark the payload to avoid side effects if stages respect it.

	payload.IsSimulation = true

	if err := h.pipelineL1.Run(payload); err != nil {
		h.logger.Error("simulation failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "simulation failed")
		return
	}

	h.respondJSON(w, http.StatusOK, map[string]any{
		"campaign": campaign.Name,
		"trace":    payload.Trace,
	})
}
