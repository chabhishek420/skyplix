package handler

import (
	"encoding/json"
	"net/http"

	"github.com/google/uuid"
	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// HandleListDomains returns a paginated list of domains.
func (h *Handler) HandleListDomains(w http.ResponseWriter, r *http.Request) {
	limit, offset := h.parsePagination(r)

	domains, err := h.domains.List(r.Context(), limit, offset)
	if err != nil {
		h.logger.Error("list domains failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list domains")
		return
	}

	h.respondJSON(w, http.StatusOK, domains)
}

// HandleGetDomain returns a single domain by ID.
func (h *Handler) HandleGetDomain(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid domain id")
		return
	}

	d, err := h.domains.GetByID(r.Context(), id)
	if err != nil {
		h.logger.Error("get domain failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "domain not found")
		return
	}

	h.respondJSON(w, http.StatusOK, d)
}

// HandleCreateDomain creates a new domain.
func (h *Handler) HandleCreateDomain(w http.ResponseWriter, r *http.Request) {
	var d model.Domain
	if err := json.NewDecoder(r.Body).Decode(&d); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if d.Domain == "" {
		h.respondError(w, http.StatusBadRequest, "domain name is required")
		return
	}

	if err := h.domains.Create(r.Context(), &d); err != nil {
		h.logger.Error("create domain failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to create domain")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, d)
}

// HandleUpdateDomain updates an existing domain.
func (h *Handler) HandleUpdateDomain(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid domain id")
		return
	}

	var d model.Domain
	if err := json.NewDecoder(r.Body).Decode(&d); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	d.ID = id

	if err := h.domains.Update(r.Context(), &d); err != nil {
		h.logger.Error("update domain failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to update domain")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, d)
}

// HandleDeleteDomain deletes a domain.
func (h *Handler) HandleDeleteDomain(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid domain id")
		return
	}

	if err := h.domains.Delete(r.Context(), id); err != nil {
		h.logger.Error("delete domain failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to delete domain")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusNoContent, nil)
}

// HandleListDeletedDomains returns a paginated list of archived domains.
func (h *Handler) HandleListDeletedDomains(w http.ResponseWriter, r *http.Request) {
	limit, offset := h.parsePagination(r)

	domains, err := h.domains.ListDeleted(r.Context(), limit, offset)
	if err != nil {
		h.logger.Error("list deleted domains failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list deleted domains")
		return
	}

	h.respondJSON(w, http.StatusOK, domains)
}

// HandleRestoreDomain unarchives a domain.
func (h *Handler) HandleRestoreDomain(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid domain id")
		return
	}

	if err := h.domains.Restore(r.Context(), id); err != nil {
		h.logger.Error("restore domain failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to restore domain")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusOK, map[string]string{"status": "restored"})
}

// HandleCloneDomain duplicates a domain.
func (h *Handler) HandleCloneDomain(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid domain id")
		return
	}

	d, err := h.domains.GetByID(r.Context(), id)
	if err != nil {
		h.logger.Error("get domain failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "domain not found")
		return
	}

	// Clone logic
	d.ID = uuid.Nil
	d.Domain = d.Domain + " (copy)"
	d.State = "active"

	if err := h.domains.Create(r.Context(), d); err != nil {
		h.logger.Error("clone domain failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to clone domain")
		return
	}

	h.cache.ScheduleWarmup()
	h.respondJSON(w, http.StatusCreated, d)
}

// HandleCheckDomain triggers DNS validation for a domain (stubbed logic for v1).
func (h *Handler) HandleCheckDomain(w http.ResponseWriter, r *http.Request) {
	// For Phase 3, this is stubbed to return success status.
	// Actual DNS resolution would use net.LookupHost.
	h.respondJSON(w, http.StatusOK, map[string]string{"status": "verified"})
}
