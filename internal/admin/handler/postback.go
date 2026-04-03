package handler

import (
	"context"
	"database/sql"
	"net/http"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/go-chi/chi/v5"
	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/admin/repository"
	"github.com/skyplix/zai-tds/internal/attribution"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/queue"
)

const postbackKeySetting = "tracker.postback_key"

type postbackKeyCache struct {
	mu       sync.Mutex
	value    string
	fetched  time.Time
	cacheTTL time.Duration
}

// PostbackHandler receives conversion postbacks and enqueues ConversionRecord writes.
// It is a public handler secured by a global key (`/postback/{key}`) stored in settings.
type PostbackHandler struct {
	logger      *zap.Logger
	settings    *repository.SettingsRepository
	attribution *attribution.Service
	clickhouse  driver.Conn
	convChan    chan<- queue.ConversionRecord
	keyCache    postbackKeyCache
}

func NewPostbackHandler(
	logger *zap.Logger,
	settings *repository.SettingsRepository,
	attribution *attribution.Service,
	clickhouse driver.Conn,
	convChan chan<- queue.ConversionRecord,
) *PostbackHandler {
	return &PostbackHandler{
		logger:      logger,
		settings:    settings,
		attribution: attribution,
		clickhouse:  clickhouse,
		convChan:    convChan,
		keyCache: postbackKeyCache{
			cacheTTL: 30 * time.Second,
		},
	}
}

func (h *PostbackHandler) HandlePostback(w http.ResponseWriter, r *http.Request) {
	key := chi.URLParam(r, "key")
	expectedKey, err := h.getPostbackKey(r.Context())
	if err != nil {
		h.logger.Error("postback key lookup failed", zap.Error(err))
		h.writeText(w, http.StatusInternalServerError, "error: settings_lookup_failed")
		return
	}
	if expectedKey == "" {
		h.writeText(w, http.StatusInternalServerError, "error: postback_key_missing")
		return
	}
	if key == "" || key != expectedKey {
		h.writeText(w, http.StatusUnauthorized, "error: invalid_key")
		return
	}

	if err := r.ParseForm(); err != nil {
		h.writeText(w, http.StatusBadRequest, "error: invalid_form")
		return
	}

	token := firstNonEmpty(
		r.Form.Get("sub_id"),
		r.Form.Get("subid"),
		r.Form.Get("click_token"),
		r.Form.Get("clickid"),
		r.Form.Get("click_id"),
		r.Form.Get("sub_id_1"),
		r.Form.Get("subid1"),
		r.Form.Get("sub1"),
	)
	if token == "" {
		h.writeText(w, http.StatusBadRequest, "error: missing_token")
		return
	}

	status := strings.ToLower(firstNonEmpty(r.Form.Get("status"), r.Form.Get("type"), "lead"))
	switch status {
	case "lead", "sale", "rejected", "hold":
	default:
		status = "lead"
	}

	payout := parseFloat(firstNonEmpty(r.Form.Get("payout"), r.Form.Get("amount"), r.Form.Get("sum")))
	revenue := parseFloat(firstNonEmpty(r.Form.Get("revenue"), r.Form.Get("rev")))
	externalID := firstNonEmpty(
		r.Form.Get("external_id"),
		r.Form.Get("txid"),
		r.Form.Get("transaction_id"),
		r.Form.Get("tid"),
	)

	attr, err := h.getAttribution(r.Context(), token)
	if err != nil {
		h.logger.Error("postback attribution lookup failed", zap.Error(err), zap.String("token", token))
		h.writeText(w, http.StatusInternalServerError, "error: attribution_lookup_failed")
		return
	}
	if attr == nil || attr.CampaignID == uuid.Nil {
		h.writeText(w, http.StatusNotFound, "error: attribution_not_found")
		return
	}

	if h.convChan == nil {
		h.writeText(w, http.StatusServiceUnavailable, "error: conversion_queue_unavailable")
		return
	}

	record := queue.ConversionRecord{
		ID:                 uuid.New().String(),
		CreatedAt:          time.Now().UTC(),
		ClickToken:         token,
		CampaignID:         attr.CampaignID.String(),
		StreamID:           attr.StreamID.String(),
		OfferID:            attr.OfferID.String(),
		LandingID:          attr.LandingID.String(),
		AffiliateNetworkID: attr.AffiliateNetworkID.String(),
		SourceID:           attr.SourceID.String(),
		CountryCode:        attr.CountryCode,
		Status:             status,
		Payout:             payout,
		Revenue:            revenue,
		ExternalID:         externalID,
	}

	select {
	case h.convChan <- record:
		h.writeText(w, http.StatusOK, "ok")
	default:
		h.logger.Warn("conversion queue full - dropping", zap.String("token", token))
		h.writeText(w, http.StatusServiceUnavailable, "error: queue_full")
	}
}

func (h *PostbackHandler) writeText(w http.ResponseWriter, status int, body string) {
	w.Header().Set("Content-Type", "text/plain; charset=utf-8")
	w.WriteHeader(status)
	_, _ = w.Write([]byte(body))
}

func (h *PostbackHandler) getPostbackKey(ctx context.Context) (string, error) {
	h.keyCache.mu.Lock()
	if !h.keyCache.fetched.IsZero() && time.Since(h.keyCache.fetched) < h.keyCache.cacheTTL {
		v := h.keyCache.value
		h.keyCache.mu.Unlock()
		return v, nil
	}
	h.keyCache.mu.Unlock()

	lookupCtx, cancel := context.WithTimeout(ctx, 1*time.Second)
	defer cancel()

	v, err := h.settings.Get(lookupCtx, postbackKeySetting)
	if err != nil {
		return "", err
	}

	h.keyCache.mu.Lock()
	h.keyCache.value = v
	h.keyCache.fetched = time.Now()
	h.keyCache.mu.Unlock()

	return v, nil
}

func (h *PostbackHandler) getAttribution(ctx context.Context, token string) (*model.AttributionData, error) {
	if h.attribution != nil {
		attr, err := h.attribution.GetClickAttribution(ctx, token)
		if err != nil {
			return nil, err
		}
		if attr != nil {
			return attr, nil
		}
	}

	if h.clickhouse == nil {
		return nil, nil
	}

	qctx, cancel := context.WithTimeout(ctx, 300*time.Millisecond)
	defer cancel()

	var campaignID uuid.UUID
	var streamID uuid.UUID
	var offerID uuid.UUID
	var landingID uuid.UUID
	var countryCode string
	err := h.clickhouse.
		QueryRow(qctx, `SELECT campaign_id, stream_id, offer_id, landing_id, toString(country_code)
FROM clicks
WHERE click_token = ?
ORDER BY created_at DESC
LIMIT 1`, token).
		Scan(&campaignID, &streamID, &offerID, &landingID, &countryCode)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, nil
		}
		return nil, err
	}

	return &model.AttributionData{
		CampaignID:  campaignID,
		StreamID:    streamID,
		OfferID:     offerID,
		LandingID:   landingID,
		CountryCode: strings.TrimSpace(countryCode),
	}, nil
}

func firstNonEmpty(values ...string) string {
	for _, v := range values {
		if v = strings.TrimSpace(v); v != "" {
			return v
		}
	}
	return ""
}

func parseFloat(s string) float64 {
	if s == "" {
		return 0
	}
	v, err := strconv.ParseFloat(s, 64)
	if err != nil {
		return 0
	}
	return v
}
