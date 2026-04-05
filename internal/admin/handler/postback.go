package handler

import (
	"context"
	"crypto/hmac"
	"crypto/sha256"
	"database/sql"
	"encoding/hex"
	"math"
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

	// 4. HMAC Validation (Phase 5.2) - Optional for compatibility, but recommended.
	// Construction: sig=hmac_sha256(expectedKey, click_token + status + payout_str)
	if sig := r.Form.Get("sig"); sig != "" {
		payoutStr := firstNonEmpty(r.Form.Get("payout"), r.Form.Get("amount"), r.Form.Get("sum"), "0")
		mac := hmac.New(sha256.New, []byte(expectedKey))
		mac.Write([]byte(token + status + payoutStr))
		expectedSig := hex.EncodeToString(mac.Sum(nil))
		if !hmac.Equal([]byte(sig), []byte(expectedSig)) {
			h.logger.Warn("postback signature mismatch", zap.String("token", token), zap.String("got", sig), zap.String("want", expectedSig))
			h.writeText(w, http.StatusUnauthorized, "error: invalid_signature")
			return
		}
	}

	// 5. Transaction Deduplication (Phase 5.1)
	if externalID != "" {
		dup, err := h.attribution.CheckDuplicateTransaction(r.Context(), attr.WorkspaceID, externalID)
		if err != nil {
			h.logger.Error("deduplication check failed", zap.Error(err))
		}
		if dup {
			h.writeText(w, http.StatusConflict, "error: duplicate_transaction")
			return
		}
	}

	if h.convChan == nil {
		h.writeText(w, http.StatusServiceUnavailable, "error: conversion_queue_unavailable")
		return
	}

	record := queue.ConversionRecord{
		ID:                 uuid.New().String(),
		WorkspaceID:        attr.WorkspaceID.String(),
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
		Payout:             int64(math.Round(payout * 100)),
		Revenue:            int64(math.Round(revenue * 100)),
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

// HandlePixel serves a 1x1 transparent GIF and records a conversion.
// GET /pixel.gif?sub_id={sub_id}&status={status}&payout={payout}
func (h *PostbackHandler) HandlePixel(w http.ResponseWriter, r *http.Request) {
	// 1. Process conversion logic (shared with Postback)
	// For Pixels, we don't usually require the postback key in the URL path,
	// but we might check it in params or just rely on the click token.

	if err := r.ParseForm(); err != nil {
		h.writePixel(w)
		return
	}

	token := firstNonEmpty(r.Form.Get("sub_id"), r.Form.Get("subid"), r.Form.Get("click_id"))
	if token == "" {
		h.writePixel(w)
		return
	}

	// Pixels are often fired from safe pages or landings,
	// so we try to find the attribution.
	attr, err := h.getAttribution(r.Context(), token)
	if err != nil || attr == nil {
		h.writePixel(w)
		return
	}

	status := strings.ToLower(firstNonEmpty(r.Form.Get("status"), "lead"))
	payout := parseFloat(firstNonEmpty(r.Form.Get("payout"), "0"))

	if h.convChan != nil {
		record := queue.ConversionRecord{
			ID:                 uuid.New().String(),
			WorkspaceID:        attr.WorkspaceID.String(),
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
			ConversionType:     "pixel",
			Payout:             int64(math.Round(payout * 100)),
			Revenue:            int64(math.Round(payout * 100)), // For pixels, revenue usually equals payout
		}

		select {
		case h.convChan <- record:
		default:
			h.logger.Warn("pixel conversion dropped - queue full", zap.String("token", token))
		}
	}

	h.writePixel(w)
}

var transparentGif = []byte{
	0x47, 0x49, 0x46, 0x38, 0x39, 0x61, 0x01, 0x00, 0x01, 0x00, 0x80, 0x00,
	0x00, 0xFF, 0xFF, 0xFF, 0x00, 0x00, 0x00, 0x21, 0xF9, 0x04, 0x01, 0x00,
	0x00, 0x00, 0x00, 0x2C, 0x00, 0x00, 0x00, 0x00, 0x01, 0x00, 0x01, 0x00,
	0x00, 0x02, 0x02, 0x44, 0x01, 0x00, 0x3B,
}

func (h *PostbackHandler) writePixel(w http.ResponseWriter) {
	w.Header().Set("Content-Type", "image/gif")
	w.Header().Set("Cache-Control", "no-cache, no-store, must-revalidate")
	w.Header().Set("Pragma", "no-cache")
	w.Header().Set("Expires", "0")
	w.WriteHeader(http.StatusOK)
	_, _ = w.Write(transparentGif)
}
