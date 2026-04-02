package server

import (
	"encoding/json"
	"net/http"

	"github.com/go-chi/chi/v5"
	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/model"
	"go.uber.org/zap"
)

// handleAdminListCampaigns returns all campaigns from the database.
func (s *Server) handleAdminListCampaigns(w http.ResponseWriter, r *http.Request) {
	rows, err := s.db.Query(r.Context(), `
		SELECT id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id
		FROM campaigns
		ORDER BY created_at DESC
	`)
	if err != nil {
		s.errorResponse(w, r, http.StatusInternalServerError, "failed to list campaigns")
		return
	}
	defer rows.Close()

	campaigns := make([]model.Campaign, 0)
	for rows.Next() {
		var c model.Campaign
		if err := rows.Scan(&c.ID, &c.Alias, &c.Name, &c.Type, &c.BindVisitors, &c.State, &c.TrafficSourceID, &c.DefaultStreamID); err != nil {
			s.errorResponse(w, r, http.StatusInternalServerError, "failed to scan campaign")
			return
		}
		campaigns = append(campaigns, c)
	}

	s.jsonResponse(w, r, http.StatusOK, campaigns)
}

// handleAdminCreateCampaign creates a new campaign.
func (s *Server) handleAdminCreateCampaign(w http.ResponseWriter, r *http.Request) {
	var c model.Campaign
	if err := json.NewDecoder(r.Body).Decode(&c); err != nil {
		s.errorResponse(w, r, http.StatusBadRequest, "invalid request body")
		return
	}

	if c.Alias == "" || c.Name == "" {
		s.errorResponse(w, r, http.StatusBadRequest, "alias and name are required")
		return
	}

	err := s.db.QueryRow(r.Context(), `
		INSERT INTO campaigns (alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id)
		VALUES ($1, $2, $3, $4, $5, $6, $7)
		RETURNING id
	`, c.Alias, c.Name, c.Type, c.BindVisitors, c.State, c.TrafficSourceID, c.DefaultStreamID).Scan(&c.ID)

	if err != nil {
		s.logger.Error("failed to create campaign", zap.Error(err))
		s.errorResponse(w, r, http.StatusInternalServerError, "failed to create campaign")
		return
	}

	// Invalidate/Warmup cache
	if err := s.cache.Warmup(r.Context()); err != nil {
		s.logger.Error("failed to warmup cache after creation", zap.Error(err))
	}

	s.jsonResponse(w, r, http.StatusCreated, c)
}

// handleAdminGetCampaign returns a single campaign by ID.
func (s *Server) handleAdminGetCampaign(w http.ResponseWriter, r *http.Request) {
	idStr := chi.URLParam(r, "id")
	id, err := uuid.Parse(idStr)
	if err != nil {
		s.errorResponse(w, r, http.StatusBadRequest, "invalid campaign id")
		return
	}

	var c model.Campaign
	err = s.db.QueryRow(r.Context(), `
		SELECT id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id
		FROM campaigns WHERE id = $1
	`, id).Scan(&c.ID, &c.Alias, &c.Name, &c.Type, &c.BindVisitors, &c.State, &c.TrafficSourceID, &c.DefaultStreamID)

	if err != nil {
		s.errorResponse(w, r, http.StatusNotFound, "campaign not found")
		return
	}

	s.jsonResponse(w, r, http.StatusOK, c)
}

// handleAdminUpdateCampaign updates an existing campaign.
func (s *Server) handleAdminUpdateCampaign(w http.ResponseWriter, r *http.Request) {
	idStr := chi.URLParam(r, "id")
	id, err := uuid.Parse(idStr)
	if err != nil {
		s.errorResponse(w, r, http.StatusBadRequest, "invalid campaign id")
		return
	}

	var c model.Campaign
	if err := json.NewDecoder(r.Body).Decode(&c); err != nil {
		s.errorResponse(w, r, http.StatusBadRequest, "invalid request body")
		return
	}

	res, err := s.db.Exec(r.Context(), `
		UPDATE campaigns
		SET alias = $1, name = $2, type = $3, bind_visitors = $4, state = $5, traffic_source_id = $6, default_stream_id = $7, updated_at = NOW()
		WHERE id = $8
	`, c.Alias, c.Name, c.Type, c.BindVisitors, c.State, c.TrafficSourceID, c.DefaultStreamID, id)

	if err != nil {
		s.logger.Error("failed to update campaign", zap.Error(err))
		s.errorResponse(w, r, http.StatusInternalServerError, "failed to update campaign")
		return
	}

	if res.RowsAffected() == 0 {
		s.errorResponse(w, r, http.StatusNotFound, "campaign not found")
		return
	}

	// Invalidate/Warmup cache
	if err := s.cache.Warmup(r.Context()); err != nil {
		s.logger.Error("failed to warmup cache after update", zap.Error(err))
	}

	c.ID = id
	s.jsonResponse(w, r, http.StatusOK, c)
}

// handleAdminDeleteCampaign deletes a campaign.
func (s *Server) handleAdminDeleteCampaign(w http.ResponseWriter, r *http.Request) {
	idStr := chi.URLParam(r, "id")
	id, err := uuid.Parse(idStr)
	if err != nil {
		s.errorResponse(w, r, http.StatusBadRequest, "invalid campaign id")
		return
	}

	res, err := s.db.Exec(r.Context(), "DELETE FROM campaigns WHERE id = $1", id)
	if err != nil {
		s.errorResponse(w, r, http.StatusInternalServerError, "failed to delete campaign")
		return
	}

	if res.RowsAffected() == 0 {
		s.errorResponse(w, r, http.StatusNotFound, "campaign not found")
		return
	}

	// Invalidate/Warmup cache
	if err := s.cache.Warmup(r.Context()); err != nil {
		s.logger.Error("failed to warmup cache after deletion", zap.Error(err))
	}

	s.jsonResponse(w, r, http.StatusNoContent, nil)
}
