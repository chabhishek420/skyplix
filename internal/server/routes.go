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
	"github.com/skyplix/zai-tds/internal/queue"
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

// handleClick runs the full Level 1 click pipeline (23 stages).
// Stages 7-12 and 14-19 are no-ops in Phase 1 — implemented in Phase 2+.
func (s *Server) handleClick(w http.ResponseWriter, r *http.Request) {
	start := time.Now()
	l := s.logger

	// Build the click write channel (nil safe — stages check for nil)
	var clickChan chan<- queue.ClickRecord
	if s.chWriter != nil {
		clickChan = s.chWriter.Chan()
	}

	p := pipeline.New(
		// Stage 1-3: Request processing
		&stage.DomainRedirectStage{},
		&stage.CheckPrefetchStage{},
		&stage.BuildRawClickStage{},

		// Stage 4-6: Campaign resolution + enrichment
		&stage.FindCampaignStage{DB: s.db, Logger: l},
		&stage.CheckDefaultCampaignStage{},
		&stage.UpdateRawClickStage{Geo: s.geo, Device: s.device, Logger: l},

		// Stages 7-12: No-op in Phase 1 (stream selection in Phase 2)
		stage.NewNoOp(7, "CheckParamAliases", l),
		stage.NewNoOp(8, "UpdateCampaignUniqueness", l),
		stage.NewNoOp(9, "ChooseStream", l),
		stage.NewNoOp(10, "UpdateStreamUniqueness", l),
		stage.NewNoOp(11, "ChooseLanding", l),
		stage.NewNoOp(12, "ChooseOffer", l),

		// Stage 13: Generate cryptographic click token
		&stage.GenerateTokenStage{},

		// Stages 14-19: No-op in Phase 1
		stage.NewNoOp(14, "FindAffiliateNetwork", l),
		stage.NewNoOp(15, "UpdateHitLimit", l),
		stage.NewNoOp(16, "UpdateCosts", l),
		stage.NewNoOp(17, "UpdatePayout", l),
		stage.NewNoOp(18, "SaveUniquenessSession", l),
		stage.NewNoOp(19, "SetCookie", l),

		// Stage 20: Execute the HTTP response action
		&stage.ExecuteActionStage{Logger: l},

		// Stages 21-22: No-op in Phase 1
		stage.NewNoOp(21, "PrepareRawClickToStore", l),
		stage.NewNoOp(22, "CheckSendingToAnotherCampaign", l),

		// Stage 23: Non-blocking push to ClickHouse write channel
		&stage.StoreRawClicksStage{ClickChan: clickChan},
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

	// Handle abort (prefetch silently 200, 404, etc.)
	if payload.Abort && payload.AbortCode > 0 && payload.Response == nil {
		// Only write status if ExecuteAction didn't already write the response
		w.WriteHeader(payload.AbortCode)
	}

	// Log click telemetry
	if payload.RawClick != nil && payload.Campaign != nil {
		l.Info("click processed",
			zap.String("alias", payload.RawClick.CampaignAlias),
			zap.String("campaign", payload.Campaign.Name),
			zap.String("token", payload.RawClick.ClickToken),
			zap.String("country", payload.RawClick.CountryCode),
			zap.String("device", payload.RawClick.DeviceType),
			zap.Bool("is_bot", payload.RawClick.IsBot),
			zap.Duration("latency", time.Since(start)),
		)
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
				"ip", r.RemoteAddr,
			)
		})
	}
}
