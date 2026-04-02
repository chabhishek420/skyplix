package handler

import (
	"encoding/json"
	"net/http"

	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// HandleListOffers returns a paginated list of offers.
func (h *Handler) HandleListOffers(w http.ResponseWriter, r *http.Request) {
	limit, offset := h.parsePagination(r)

	offers, err := h.offers.List(r.Context(), limit, offset)
	if err != nil {
		h.logger.Error("list offers failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list offers")
		return
	}

	h.respondJSON(w, http.StatusOK, offers)
}

// HandleGetOffer returns a single offer by ID.
func (h *Handler) HandleGetOffer(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid offer id")
		return
	}

	o, err := h.offers.GetByID(r.Context(), id)
	if err != nil {
		h.logger.Error("get offer failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "offer not found")
		return
	}

	h.respondJSON(w, http.StatusOK, o)
}

// HandleCreateOffer creates a new offer.
func (h *Handler) HandleCreateOffer(w http.ResponseWriter, r *http.Request) {
	var o model.Offer
	if err := json.NewDecoder(r.Body).Decode(&o); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if o.Name == "" || o.URL == "" {
		h.respondError(w, http.StatusBadRequest, "name and url are required")
		return
	}

	if err := h.offers.Create(r.Context(), &o); err != nil {
		h.logger.Error("create offer failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to create offer")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, o)
}

// HandleUpdateOffer updates an existing offer.
func (h *Handler) HandleUpdateOffer(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid offer id")
		return
	}

	var o model.Offer
	if err := json.NewDecoder(r.Body).Decode(&o); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	o.ID = id

	if err := h.offers.Update(r.Context(), &o); err != nil {
		h.logger.Error("update offer failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to update offer")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, o)
}

// HandleDeleteOffer deletes an offer.
func (h *Handler) HandleDeleteOffer(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid offer id")
		return
	}

	if err := h.offers.Delete(r.Context(), id); err != nil {
		h.logger.Error("delete offer failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to delete offer")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusNoContent, nil)
}
