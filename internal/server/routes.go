package server

import (
	"encoding/json"
	"net/http"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/admin"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// routes wires all HTTP routes and returns the handler.
func (s *Server) routes() http.Handler {
	r := chi.NewRouter()

	r.Use(middleware.Recoverer)
	r.Use(middleware.RealIP)
	r.Use(s.requestLogger())

	// Public Administrative routes
	r.Get("/api/v1/health", s.handleHealth)

	// Protected Admin API routes
	r.Route("/api/v1", func(r chi.Router) {
		r.Use(admin.APIKeyAuth(s.db))

		r.Route("/campaigns", func(r chi.Router) {
			r.Get("/", s.adminHandler.HandleListCampaigns)
			r.Post("/", s.adminHandler.HandleCreateCampaign)
			r.Route("/{id}", func(r chi.Router) {
				r.Get("/", s.adminHandler.HandleGetCampaign)
				r.Put("/", s.adminHandler.HandleUpdateCampaign)
				r.Delete("/", s.adminHandler.HandleDeleteCampaign)
				r.Get("/streams", s.adminHandler.HandleListStreams)
			})
		})

		r.Route("/streams", func(r chi.Router) {
			r.Post("/", s.adminHandler.HandleCreateStream)
			r.Route("/{id}", func(r chi.Router) {
				r.Get("/", s.adminHandler.HandleGetStream)
				r.Put("/", s.adminHandler.HandleUpdateStream)
				r.Delete("/", s.adminHandler.HandleDeleteStream)
				r.Get("/offers", s.adminHandler.HandleGetStreamOffers)
				r.Post("/offers", s.adminHandler.HandleSyncStreamOffers)
				r.Get("/landings", s.adminHandler.HandleGetStreamLandings)
				r.Post("/landings", s.adminHandler.HandleSyncStreamLandings)
			})
		})

		r.Route("/offers", func(r chi.Router) {
			r.Get("/", s.adminHandler.HandleListOffers)
			r.Post("/", s.adminHandler.HandleCreateOffer)
			r.Route("/{id}", func(r chi.Router) {
				r.Get("/", s.adminHandler.HandleGetOffer)
				r.Put("/", s.adminHandler.HandleUpdateOffer)
				r.Delete("/", s.adminHandler.HandleDeleteOffer)
			})
		})

		r.Route("/landings", func(r chi.Router) {
			r.Get("/", s.adminHandler.HandleListLandings)
			r.Post("/", s.adminHandler.HandleCreateLanding)
			r.Route("/{id}", func(r chi.Router) {
				r.Get("/", s.adminHandler.HandleGetLanding)
				r.Put("/", s.adminHandler.HandleUpdateLanding)
				r.Delete("/", s.adminHandler.HandleDeleteLanding)
			})
		})

		r.Route("/affiliate_networks", func(r chi.Router) {
			r.Get("/", s.adminHandler.HandleListNetworks)
			r.Post("/", s.adminHandler.HandleCreateNetwork)
			r.Route("/{id}", func(r chi.Router) {
				r.Get("/", s.adminHandler.HandleGetNetwork)
				r.Put("/", s.adminHandler.HandleUpdateNetwork)
				r.Delete("/", s.adminHandler.HandleDeleteNetwork)
			})
		})

		r.Route("/traffic_sources", func(r chi.Router) {
			r.Get("/", s.adminHandler.HandleListSources)
			r.Post("/", s.adminHandler.HandleCreateSource)
			r.Route("/{id}", func(r chi.Router) {
				r.Get("/", s.adminHandler.HandleGetSource)
				r.Put("/", s.adminHandler.HandleUpdateSource)
				r.Delete("/", s.adminHandler.HandleDeleteSource)
			})
		})

		r.Route("/domains", func(r chi.Router) {
			r.Get("/", s.adminHandler.HandleListDomains)
			r.Post("/", s.adminHandler.HandleCreateDomain)
			r.Route("/{id}", func(r chi.Router) {
				r.Get("/", s.adminHandler.HandleGetDomain)
				r.Put("/", s.adminHandler.HandleUpdateDomain)
				r.Delete("/", s.adminHandler.HandleDeleteDomain)
			})
		})

		r.Route("/users", func(r chi.Router) {
			r.Get("/", s.adminHandler.HandleListUsers)
			r.Post("/", s.adminHandler.HandleCreateUser)
			r.Route("/{id}", func(r chi.Router) {
				r.Get("/", s.adminHandler.HandleGetUser)
				r.Put("/", s.adminHandler.HandleUpdateUser)
				r.Delete("/", s.adminHandler.HandleDeleteUser)
			})
		})

		r.Get("/settings", s.adminHandler.HandleGetSettings)
		r.Put("/settings", s.adminHandler.HandleUpdateSettings)
	})

	// Click traffic routes (hot path)
	r.Get("/lp/{token}/click", s.handleClickL2) // Level 2 (Landing → Offer)
	r.Get("/{alias}", s.handleClick)             // Level 1 (Campaign → Stream → Redirect)
	r.Get("/", s.handleClick)                    // Gateway context (bare domain)

	return r
}

func (s *Server) handleHealth(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	_ = json.NewEncoder(w).Encode(map[string]string{
		"status":  "ok",
		"version": s.version,
	})
}

// handleClick runs the full Level 1 click pipeline (23 stages).
func (s *Server) handleClick(w http.ResponseWriter, r *http.Request) {
	start := time.Now()
	l := s.logger

	// Using pre-compiled singleton pipeline for Level 1 clicks


	payload := &pipeline.Payload{
		Ctx:     r.Context(),
		Request: r,
		Writer:  w,
	}

	if err := s.pipelineL1.Run(payload); err != nil {
		s.logger.Error("pipeline error", zap.Error(err))
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}

	if payload.Abort && payload.AbortCode > 0 && payload.Response == nil {
		w.WriteHeader(payload.AbortCode)
	}

	if payload.RawClick != nil && payload.Campaign != nil {
		l.Info("click processed",
			zap.String("campaign", payload.Campaign.Name),
			zap.String("token", payload.RawClick.ClickToken),
			zap.Duration("latency", time.Since(start)),
		)
	}
}

// handleClickL2 runs the Level 2 pipeline (Landing → Offer).
func (s *Server) handleClickL2(w http.ResponseWriter, r *http.Request) {
	start := time.Now()
	token := chi.URLParam(r, "token")
	s.logger.Debug("L2 click received", zap.String("url", r.URL.String()), zap.String("token_param", token))

	payload := &pipeline.Payload{
		Ctx:     r.Context(),
		Request: r,
		Writer:  w,
	}

	if err := s.pipelineL2.Run(payload); err != nil {
		s.logger.Error("L2 pipeline error", zap.Error(err))
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}

	if payload.RawClick != nil {
		s.logger.Info("L2 click processed",
			zap.String("token", payload.RawClick.ClickToken),
			zap.Duration("latency", time.Since(start)),
		)
	}
}

func (s *Server) requestLogger() func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			ww := middleware.NewWrapResponseWriter(w, r.ProtoMajor)
			next.ServeHTTP(ww, r)
		})
	}
}
