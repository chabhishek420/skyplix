package handler

import (
	"context"
	"crypto/hmac"
	"crypto/sha256"
	"database/sql"
	"encoding/hex"
	"fmt"
	"net/http"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/go-chi/chi/v5"
	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/attribution"
	"github.com/skyplix/zai-tds/internal/metrics"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/queue"
)

const (
	postbackKeySetting  = "tracker.postback_key"
	postbackSaltSetting = "tracker.postback_salt"
)

type postbackKeyCache struct {
	mu       sync.Mutex
	value    string
	fetched  time.Time
	cacheTTL time.Duration
}

// SettingsReader defines the interface for reading settings.
type SettingsReader interface {
	Get(ctx context.Context, key string) (string, error)
}

// WebhookEventQueue defines minimal enqueue behavior for outbound conversion notifications.
type WebhookEventQueue interface {
	Enqueue(event model.WebhookConversionEvent) error
}

// PostbackHandler receives conversion postbacks and enqueues ConversionRecord writes.
// It is a public handler secured by a global key (`/postback/{key}`) stored in settings.
type PostbackHandler struct {
	logger       *zap.Logger
	settings     SettingsReader
	attribution  *attribution.Service
	clickhouse   driver.Conn
	convChan     chan<- queue.ConversionRecord
	webhookQueue WebhookEventQueue
	keyCache     postbackKeyCache
	saltCache    postbackKeyCache
}

func NewPostbackHandler(
	logger *zap.Logger,
	settings SettingsReader,
	attribution *attribution.Service,
	clickhouse driver.Conn,
	convChan chan<- queue.ConversionRecord,
	webhookQueue WebhookEventQueue,
) *PostbackHandler {
	return &PostbackHandler{
		logger:       logger,
		settings:     settings,
		attribution:  attribution,
		clickhouse:   clickhouse,
		convChan:     convChan,
		webhookQueue: webhookQueue,
		keyCache: postbackKeyCache{
			cacheTTL: 30 * time.Second,
		},
		saltCache: postbackKeyCache{
			cacheTTL: 30 * time.Second,
		},
	}
}

func (h *PostbackHandler) HandlePostback(w http.ResponseWriter, r *http.Request) {
	key := chi.URLParam(r, "key")
	expectedKey, err := h.getPostbackKey(r.Context())
	if err != nil {
		h.logger.Error("postback key lookup failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "error: settings_lookup_failed")
		metrics.PostbackProcessedTotal.WithLabelValues("error").Inc()
		return
	}
	if expectedKey == "" {
		h.respondError(w, http.StatusInternalServerError, "error: postback_key_missing")
		metrics.PostbackProcessedTotal.WithLabelValues("error").Inc()
		return
	}
	if key == "" || key != expectedKey {
		h.respondError(w, http.StatusUnauthorized, "error: invalid_key")
		metrics.PostbackProcessedTotal.WithLabelValues("invalid_key").Inc()
		return
	}

	if err := r.ParseForm(); err != nil {
		h.respondError(w, http.StatusBadRequest, "error: invalid_form")
		metrics.PostbackProcessedTotal.WithLabelValues("error").Inc()
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
		h.respondError(w, http.StatusBadRequest, "error: missing_token")
		metrics.PostbackProcessedTotal.WithLabelValues("error").Inc()
		return
	}

	status := strings.ToLower(firstNonEmpty(r.Form.Get("status"), r.Form.Get("type"), "lead"))
	switch status {
	case "lead", "sale", "rejected", "hold":
	default:
		status = "lead"
	}

	payoutRaw := firstNonEmpty(r.Form.Get("payout"), r.Form.Get("amount"), r.Form.Get("sum"))
	payout := parseFloat(payoutRaw)
	revenue := parseFloat(firstNonEmpty(r.Form.Get("revenue"), r.Form.Get("rev")))
	externalID := firstNonEmpty(
		r.Form.Get("external_id"),
		r.Form.Get("txid"),
		r.Form.Get("transaction_id"),
		r.Form.Get("tid"),
	)

	// HMAC Validation (Fail-closed when sig is provided)
	// Uses the canonical parsed fields so aliases like amount/sum are covered.
	signature := r.Form.Get("sig")
	if signature != "" {
		salt, err := h.getPostbackSalt(r.Context())
		if err != nil {
			h.logger.Error("postback salt lookup failed", zap.Error(err))
			h.respondError(w, http.StatusInternalServerError, "error: postback_salt_lookup_failed")
			metrics.PostbackProcessedTotal.WithLabelValues("error").Inc()
			return
		}
		if salt == "" {
			h.logger.Error("postback salt missing")
			h.respondError(w, http.StatusInternalServerError, "error: postback_salt_missing")
			metrics.PostbackProcessedTotal.WithLabelValues("error").Inc()
			return
		}
		if !h.verifySignature(signature, salt, token, status, payoutRaw) {
			h.respondError(w, http.StatusUnauthorized, "error: invalid_signature")
			metrics.PostbackProcessedTotal.WithLabelValues("invalid_signature").Inc()
			return
		}
	}

	// Valkey Deduplication
	if externalID != "" && h.attribution != nil {
		isDup, err := h.attribution.IsDuplicateExternalID(r.Context(), externalID)
		if err != nil {
			h.logger.Warn("deduplication check failed", zap.Error(err))
		} else if isDup {
			h.respondError(w, http.StatusConflict, "error: duplicate_transaction")
			metrics.PostbackProcessedTotal.WithLabelValues("duplicate").Inc()
			return
		}
	}

	attr, err := h.getAttribution(r.Context(), token)
	if err != nil {
		h.logger.Error("postback attribution lookup failed", zap.Error(err), zap.String("token", token))
		h.respondError(w, http.StatusInternalServerError, "error: attribution_lookup_failed")
		metrics.PostbackProcessedTotal.WithLabelValues("error").Inc()
		return
	}
	if attr == nil || attr.CampaignID == uuid.Nil {
		h.respondError(w, http.StatusNotFound, "error: attribution_not_found")
		metrics.PostbackProcessedTotal.WithLabelValues("not_found").Inc()
		return
	}

	if h.convChan == nil {
		h.respondError(w, http.StatusServiceUnavailable, "error: conversion_queue_unavailable")
		metrics.PostbackProcessedTotal.WithLabelValues("error").Inc()
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
		ConversionType:     "postback",
	}

	select {
	case h.convChan <- record:
		h.enqueueWebhookEvent(r, record)
		h.respondText(w, http.StatusOK, "ok")
		metrics.PostbackProcessedTotal.WithLabelValues("success").Inc()
	default:
		h.logger.Warn("conversion queue full - dropping", zap.String("token", token))
		h.respondError(w, http.StatusServiceUnavailable, "error: queue_full")
		metrics.PostbackProcessedTotal.WithLabelValues("error").Inc()
	}
}

// HandlePixel serves a 1x1 transparent GIF and records a conversion.
// This is used for client-side tracking where a postback is not possible.
func (h *PostbackHandler) HandlePixel(w http.ResponseWriter, r *http.Request) {
	if err := r.ParseForm(); err != nil {
		h.serveTransparentGif(w)
		return
	}

	token := firstNonEmpty(
		r.Form.Get("sub_id"),
		r.Form.Get("subid"),
		r.Form.Get("click_token"),
		r.Form.Get("clickid"),
		r.Form.Get("click_id"),
	)

	if token == "" {
		h.serveTransparentGif(w)
		return
	}

	attr, err := h.getAttribution(r.Context(), token)
	if err != nil || attr == nil || attr.CampaignID == uuid.Nil {
		h.serveTransparentGif(w)
		return
	}

	if h.convChan != nil {
		payout := parseFloat(r.Form.Get("payout"))
		status := strings.ToLower(firstNonEmpty(r.Form.Get("status"), "lead"))

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
			ConversionType:     "pixel",
		}

		select {
		case h.convChan <- record:
			h.enqueueWebhookEvent(r, record)
			metrics.PostbackProcessedTotal.WithLabelValues("pixel_success").Inc()
		default:
			h.logger.Warn("conversion queue full (pixel) - dropping", zap.String("token", token))
		}
	}

	h.serveTransparentGif(w)
}

func (h *PostbackHandler) serveTransparentGif(w http.ResponseWriter) {
	// 1x1 transparent GIF
	const pixel = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b"
	w.Header().Set("Content-Type", "image/gif")
	w.Header().Set("Cache-Control", "no-cache, no-store, must-revalidate")
	w.WriteHeader(http.StatusOK)
	_, _ = w.Write([]byte(pixel))
}

func (h *PostbackHandler) respondJSON(w http.ResponseWriter, status int, data interface{}) {
	respondJSON(w, status, data)
}

func (h *PostbackHandler) respondError(w http.ResponseWriter, status int, message string) {
	respondError(w, status, message)
}

func (h *PostbackHandler) respondText(w http.ResponseWriter, status int, body string) {
	w.Header().Set("Content-Type", "text/plain; charset=utf-8")
	w.WriteHeader(status)
	_, _ = w.Write([]byte(body))
}

func (h *PostbackHandler) enqueueWebhookEvent(r *http.Request, record queue.ConversionRecord) {
	if h.webhookQueue == nil || r == nil {
		return
	}

	tenantID := resolvePostbackTenantID(r)
	if tenantID == "" {
		return
	}

	event := model.WebhookConversionEvent{
		EventID:        uuid.NewString(),
		TenantID:       tenantID,
		OccurredAt:     record.CreatedAt,
		ConversionID:   record.ID,
		ClickToken:     record.ClickToken,
		CampaignID:     record.CampaignID,
		StreamID:       record.StreamID,
		OfferID:        record.OfferID,
		LandingID:      record.LandingID,
		CountryCode:    record.CountryCode,
		Status:         record.Status,
		Payout:         record.Payout,
		Revenue:        record.Revenue,
		ExternalID:     record.ExternalID,
		ConversionType: record.ConversionType,
	}
	if event.OccurredAt.IsZero() {
		event.OccurredAt = time.Now().UTC()
	}

	if err := h.webhookQueue.Enqueue(event); err != nil {
		h.logger.Warn("failed to enqueue webhook event", zap.String("event_id", event.EventID), zap.String("tenant_id", tenantID), zap.Error(err))
	}
}

func resolvePostbackTenantID(r *http.Request) string {
	if r == nil {
		return ""
	}

	if id := strings.TrimSpace(r.Header.Get(tenantIDHeader)); id != "" {
		return id
	}

	if id := strings.TrimSpace(r.Form.Get("tenant_id")); id != "" {
		return id
	}

	if id := strings.TrimSpace(r.URL.Query().Get("tenant_id")); id != "" {
		return id
	}

	return ""
}

func (h *PostbackHandler) getPostbackSalt(ctx context.Context) (string, error) {
	h.saltCache.mu.Lock()
	if !h.saltCache.fetched.IsZero() && time.Since(h.saltCache.fetched) < h.saltCache.cacheTTL {
		v := h.saltCache.value
		h.saltCache.mu.Unlock()
		return v, nil
	}
	h.saltCache.mu.Unlock()

	lookupCtx, cancel := context.WithTimeout(ctx, 1*time.Second)
	defer cancel()

	v, err := h.settings.Get(lookupCtx, postbackSaltSetting)
	if err != nil {
		return "", err
	}

	h.saltCache.mu.Lock()
	h.saltCache.value = v
	h.saltCache.fetched = time.Now()
	h.saltCache.mu.Unlock()

	return v, nil
}

func (h *PostbackHandler) verifySignature(sig, salt, token, status, payoutRaw string) bool {
	payload := fmt.Sprintf("%s|%s|%s", token, status, payoutRaw)
	mac := hmac.New(sha256.New, []byte(salt))
	mac.Write([]byte(payload))
	expectedMAC := mac.Sum(nil)

	sigBytes, err := hex.DecodeString(strings.ToLower(strings.TrimSpace(sig)))
	if err != nil {
		return false
	}

	return hmac.Equal(sigBytes, expectedMAC)
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
