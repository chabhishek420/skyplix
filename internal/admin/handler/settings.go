package handler

import (
	"encoding/json"
	"net/http"

	"go.uber.org/zap"
)

// HandleGetSettings returns general system settings.
func (h *Handler) HandleGetSettings(w http.ResponseWriter, r *http.Request) {
	settings, err := h.settings.GetAll(r.Context())
	if err != nil {
		h.logger.Error("get settings failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to get settings")
		return
	}

	h.respondJSON(w, http.StatusOK, settings)
}

// HandleUpdateSettings updates general system settings in bulk.
func (h *Handler) HandleUpdateSettings(w http.ResponseWriter, r *http.Request) {
	var input map[string]string
	if err := json.NewDecoder(r.Body).Decode(&input); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if err := h.settings.BulkUpsert(r.Context(), input); err != nil {
		h.logger.Error("update settings failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to update settings")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, map[string]string{"status": "ok"})
}
