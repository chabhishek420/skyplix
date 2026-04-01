package server

import (
	"encoding/json"
	"net/http"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/pipeline/stage"
)

// routes wires all HTTP routes and returns the handler.
func (s *Server) routes() http.Handler {
	r := chi.NewRouter()

	// Global middleware
	r.Use(middleware.Recoverer)
	r.Use(middleware.RealIP)
	r.Use(s.requestLogger())

	// Admin / health routes
	r.Get("/api/v1/health", s.handleHealth)

	// Click traffic routes (hot path)
	r.Get("/{alias}", s.handleClick)
	r.Get("/", s.handleClick) // bare domain — gateway context

	return r
}

// handleHealth returns a JSON health check response.
func (s *Server) handleHealth(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{ //nolint:errcheck
		"status":  "ok",
		"version": s.version,
	})
}

// handleClick runs the full Level 1 click pipeline.
func (s *Server) handleClick(w http.ResponseWriter, r *http.Request) {
	start := time.Now()

	// Build the Level 1 pipeline with all 23 stages.
	// Stages 7-12, 14-19, 22 are no-ops in Phase 1.
	l := s.logger
	p := pipeline.New(
		// Wave 1: Request processing
		&stage.DomainRedirectStage{},
		&stage.CheckPrefetchStage{},
		&stage.BuildRawClickStage{},
		// Wave 2: Campaign resolution
		&stage.FindCampaignStage{DB: s.db, Logger: l},
		&stage.CheckDefaultCampaignStage{},
		// Wave 3: Geo + Device enrichment
		&stage.UpdateRawClickStage{Geo: s.geo, Device: s.device, Logger: l},
		// No-op stubs for Phase 1 (7-12, 14-19)
		stage.NewNoOp(7, "CheckParamAliases", l),
		stage.NewNoOp(8, "UpdateCampaignUniqueness", l),
		stage.NewNoOp(9, "ChooseStream", l),
		stage.NewNoOp(10, "UpdateStreamUniqueness", l),
		stage.NewNoOp(11, "ChooseLanding", l),
		stage.NewNoOp(12, "ChooseOffer", l),
		// Stage 13: Generate click token (Plan 1.3)
		stage.NewNoOp(13, "GenerateToken", l),
		stage.NewNoOp(14, "FindAffiliateNetwork", l),
		stage.NewNoOp(15, "UpdateHitLimit", l),
		stage.NewNoOp(16, "UpdateCosts", l),
		stage.NewNoOp(17, "UpdatePayout", l),
		stage.NewNoOp(18, "SaveUniquenessSession", l),
		stage.NewNoOp(19, "SetCookie", l),
		// Stage 20: Execute action (Plan 1.3)
		stage.NewNoOp(20, "ExecuteAction", l),
		stage.NewNoOp(21, "PrepareRawClickToStore", l),
		stage.NewNoOp(22, "CheckSendingToAnotherCampaign", l),
		stage.NewNoOp(23, "StoreRawClicks", l),
	)

	payload := &pipeline.Payload{
		Ctx:     r.Context(),
		Request: r,
		Writer:  w,
	}

	if err := p.Run(payload); err != nil {
		s.logger.Error("pipeline error", zap.Error(err))
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}

	// Handle abort (prefetch, 404, etc.)
	if payload.Abort {
		if payload.AbortCode > 0 {
			w.WriteHeader(payload.AbortCode)
		} else {
			w.WriteHeader(http.StatusOK)
		}
		return
	}

	// Phase 1: fallback redirect if pipeline didn't set a response.
	// Full ExecuteAction stage in Plan 1.3 will replace this.
	if payload.Campaign != nil {
		s.logger.Info("click processed",
			zap.String("alias", payload.RawClick.CampaignAlias),
			zap.String("campaign", payload.Campaign.Name),
			zap.String("country", payload.RawClick.CountryCode),
			zap.String("device", payload.RawClick.DeviceType),
			zap.Bool("is_bot", payload.RawClick.IsBot),
			zap.Duration("duration", time.Since(start)),
		)
		// Temporary: redirect to example.com until ExecuteAction is wired in Plan 1.3
		http.Redirect(w, r, "https://example.com", http.StatusFound)
	} else {
		http.NotFound(w, r)
	}
}

// requestLogger returns a chi middleware that logs each request using zap.
func (s *Server) requestLogger() func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			ww := middleware.NewWrapResponseWriter(w, r.ProtoMajor)
			next.ServeHTTP(ww, r)
			s.logger.Sugar().Infow("request",
				"method", r.Method,
				"path", r.URL.Path,
				"status", ww.Status(),
				"bytes", ww.BytesWritten(),
				"remote_addr", r.RemoteAddr,
			)
		})
	}
}
