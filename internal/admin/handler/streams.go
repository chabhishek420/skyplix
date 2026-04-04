package handler

import (
	"encoding/json"
	"net/http"

	"github.com/go-chi/chi/v5"
	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/admin/repository"
	"github.com/skyplix/zai-tds/internal/model"
)

// HandleListStreams returns all streams for a specific campaign.
func (h *Handler) HandleListStreams(w http.ResponseWriter, r *http.Request) {
	campaignID, err := h.parseUUID(chi.URLParam(r, "campaign_id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid campaign id")
		return
	}
	wsID := h.getWorkspaceID(r)

	streams, err := h.streams.ListByCampaign(r.Context(), campaignID, wsID)
	if err != nil {
		h.logger.Error("list streams failed", zap.String("campaign_id", campaignID.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list streams")
		return
	}

	h.respondJSON(w, http.StatusOK, streams)
}

// HandleGetStream returns a single stream by ID.
func (h *Handler) HandleGetStream(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid stream id")
		return
	}
	wsID := h.getWorkspaceID(r)

	s, err := h.streams.GetByID(r.Context(), id, wsID)
	if err != nil {
		h.logger.Error("get stream failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "stream not found")
		return
	}

	h.respondJSON(w, http.StatusOK, s)
}

// HandleCreateStream creates a new stream.
func (h *Handler) HandleCreateStream(w http.ResponseWriter, r *http.Request) {
	var s model.Stream
	if err := json.NewDecoder(r.Body).Decode(&s); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if s.Name == "" || s.CampaignID == uuid.Nil {
		h.respondError(w, http.StatusBadRequest, "name and campaign_id are required")
		return
	}

	if s.ActionPayload == nil {
		s.ActionPayload = make(map[string]interface{})
	}
	if s.Filters == nil {
		s.Filters = make([]model.StreamFilter, 0)
	}
	if s.ActionType == "" {
		s.ActionType = "HttpRedirect"
	}
	s.WorkspaceID = h.getWorkspaceID(r)

	if err := h.streams.Create(r.Context(), &s); err != nil {
		h.logger.Error("create stream failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to create stream")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, s)
}

// HandleUpdateStream updates an existing stream.
func (h *Handler) HandleUpdateStream(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid stream id")
		return
	}
	wsID := h.getWorkspaceID(r)

	var s model.Stream
	if err := json.NewDecoder(r.Body).Decode(&s); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	s.ID = id
	s.WorkspaceID = wsID

	if s.ActionPayload == nil {
		s.ActionPayload = make(map[string]interface{})
	}
	if s.Filters == nil {
		s.Filters = make([]model.StreamFilter, 0)
	}
	if s.ActionType == "" {
		s.ActionType = "HttpRedirect"
	}

	if err := h.streams.Update(r.Context(), &s); err != nil {
		h.logger.Error("update stream failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to update stream")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, s)
}

// HandleDeleteStream deletes a stream.
func (h *Handler) HandleDeleteStream(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid stream id")
		return
	}
	wsID := h.getWorkspaceID(r)

	if err := h.streams.Delete(r.Context(), id, wsID); err != nil {
		h.logger.Error("delete stream failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to delete stream")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusNoContent, nil)
}

// HandleGetStreamOffers returns all offers for a stream.
func (h *Handler) HandleGetStreamOffers(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid stream id")
		return
	}
	wsID := h.getWorkspaceID(r)

	offers, err := h.streams.GetOffers(r.Context(), id, wsID)
	if err != nil {
		h.logger.Error("get stream offers failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to get stream offers")
		return
	}

	h.respondJSON(w, http.StatusOK, offers)
}

// HandleSyncStreamOffers replaces all offers for a stream.
func (h *Handler) HandleSyncStreamOffers(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid stream id")
		return
	}
	wsID := h.getWorkspaceID(r)

	var offers []model.WeightedOffer
	if err := json.NewDecoder(r.Body).Decode(&offers); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if err := h.streams.SyncOffers(r.Context(), id, wsID, offers); err != nil {
		h.logger.Error("sync stream offers failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to sync stream offers")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, map[string]string{"status": "ok"})
}

// HandleGetStreamLandings returns all landings for a stream.
func (h *Handler) HandleGetStreamLandings(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid stream id")
		return
	}
	wsID := h.getWorkspaceID(r)

	landings, err := h.streams.GetLandings(r.Context(), id, wsID)
	if err != nil {
		h.logger.Error("get stream landings failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to get stream landings")
		return
	}

	h.respondJSON(w, http.StatusOK, landings)
}

// HandleSyncStreamLandings replaces all landings for a stream.
func (h *Handler) HandleSyncStreamLandings(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid stream id")
		return
	}
	wsID := h.getWorkspaceID(r)

	var landings []model.WeightedLanding
	if err := json.NewDecoder(r.Body).Decode(&landings); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if err := h.streams.SyncLandings(r.Context(), id, wsID, landings); err != nil {
		h.logger.Error("sync stream landings failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to sync stream landings")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, map[string]string{"status": "ok"})
}

// HandleCloneStream duplicates a stream and its associated offers/landings.
func (h *Handler) HandleCloneStream(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid stream id")
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

	// Create temporary repository bound to the transaction
	txStreams := repository.NewStreamRepository(tx)

	wsID := h.getWorkspaceID(r)

	// 1. Get Source Stream (for naming/position)
	source, err := h.streams.GetByID(ctx, id, wsID)
	if err != nil {
		h.respondError(w, http.StatusNotFound, "source stream not found")
		return
	}

	// 2. Clone Stream
	newName := source.Name + " (Copy)"
	newPosition := source.Position + 1
	newStream, err := txStreams.Clone(ctx, id, wsID, uuid.New(), source.CampaignID, newName, newPosition)
	if err != nil {
		h.logger.Error("clone stream repo call failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to clone stream")
		return
	}

	if err := tx.Commit(ctx); err != nil {
		h.logger.Error("clone stream commit failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to commit clone")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, newStream)
}
