package handler

import (
	"context"
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"fmt"
	"net/http"
	"net/http/httptest"
	"net/url"
	"testing"

	"github.com/alicebob/miniredis/v2"
	"github.com/go-chi/chi/v5"
	"github.com/google/uuid"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/attribution"
	"github.com/skyplix/zai-tds/internal/queue"
)

type mockSettings struct {
	settings map[string]string
	errKeys  map[string]error
}

func (m *mockSettings) Get(ctx context.Context, key string) (string, error) {
	if m.errKeys != nil {
		if err, ok := m.errKeys[key]; ok {
			return "", err
		}
	}
	return m.settings[key], nil
}

func TestPostbackHandler_HandlePostback(t *testing.T) {
	logger := zap.NewNop()
	mr, _ := miniredis.Run()
	defer mr.Close()
	vk := redis.NewClient(&redis.Options{Addr: mr.Addr()})
	attrSvc := attribution.New(vk, logger)

	convChan := make(chan queue.ConversionRecord, 10)
	settings := &mockSettings{
		settings: map[string]string{
			"tracker.postback_key":  "test-key",
			"tracker.postback_salt": "test-salt",
		},
	}

	h := NewPostbackHandler(logger, settings, attrSvc, nil, convChan)

	t.Run("ValidPostback", func(t *testing.T) {
		token := uuid.New().String()
		campID := uuid.New()

		// Seed attribution
		attrData := fmt.Sprintf(`{"campaign_id":"%s","country_code":"BR"}`, campID)
		vk.Set(context.Background(), "attr:"+token, attrData, 0)

		w := httptest.NewRecorder()
		r := httptest.NewRequest("GET", "/postback/test-key?subid="+token+"&payout=10.5", nil)

		// chi context for URL param
		rctx := chi.NewRouteContext()
		rctx.URLParams.Add("key", "test-key")
		r = r.WithContext(context.WithValue(r.Context(), chi.RouteCtxKey, rctx))

		h.HandlePostback(w, r)

		if w.Code != http.StatusOK {
			t.Errorf("expected 200, got %d. body: %s", w.Code, w.Body.String())
		}

		select {
		case record := <-convChan:
			if record.ClickToken != token {
				t.Errorf("expected token %s, got %s", token, record.ClickToken)
			}
			if record.Payout != 10.5 {
				t.Errorf("expected payout 10.5, got %v", record.Payout)
			}
		default:
			t.Error("expected record in convChan")
		}
	})

	t.Run("HMACSignature", func(t *testing.T) {
		token := uuid.New().String()
		campID := uuid.New()
		status := "sale"
		payout := "15.00"

		// Seed attribution
		attrData := fmt.Sprintf(`{"campaign_id":"%s"}`, campID)
		vk.Set(context.Background(), "attr:"+token, attrData, 0)

		// Calculate signature: token + status + payout + salt
		mac := hmac.New(sha256.New, []byte("test-salt"))
		mac.Write([]byte(token + "|" + status + "|" + payout))
		sig := hex.EncodeToString(mac.Sum(nil))

		w := httptest.NewRecorder()
		q := url.Values{}
		q.Set("subid", token)
		q.Set("status", status)
		q.Set("payout", payout)
		q.Set("sig", sig)

		r := httptest.NewRequest("GET", "/postback/test-key?"+q.Encode(), nil)
		rctx := chi.NewRouteContext()
		rctx.URLParams.Add("key", "test-key")
		r = r.WithContext(context.WithValue(r.Context(), chi.RouteCtxKey, rctx))

		h.HandlePostback(w, r)

		if w.Code != http.StatusOK {
			t.Errorf("valid sig: expected 200, got %d. body: %s", w.Code, w.Body.String())
		}

		// Invalid sig
		w2 := httptest.NewRecorder()
		r2 := httptest.NewRequest("GET", "/postback/test-key?subid="+token+"&sig=wrong", nil)
		r2 = r2.WithContext(context.WithValue(r2.Context(), chi.RouteCtxKey, rctx))
		h.HandlePostback(w2, r2)
		if w2.Code != http.StatusUnauthorized {
			t.Errorf("invalid sig: expected 401, got %d", w2.Code)
		}
	})

	t.Run("HMACSignature_AliasPayout", func(t *testing.T) {
		token := uuid.New().String()
		campID := uuid.New()
		status := "sale"
		amount := "7"

		// Seed attribution
		attrData := fmt.Sprintf(`{"campaign_id":"%s"}`, campID)
		vk.Set(context.Background(), "attr:"+token, attrData, 0)

		// Calculate signature with canonical fields.
		mac := hmac.New(sha256.New, []byte("test-salt"))
		mac.Write([]byte(token + "|" + status + "|" + amount))
		sig := hex.EncodeToString(mac.Sum(nil))

		w := httptest.NewRecorder()
		q := url.Values{}
		q.Set("subid", token)
		q.Set("status", status)
		q.Set("amount", amount)
		q.Set("sig", sig)

		r := httptest.NewRequest("GET", "/postback/test-key?"+q.Encode(), nil)
		rctx := chi.NewRouteContext()
		rctx.URLParams.Add("key", "test-key")
		r = r.WithContext(context.WithValue(r.Context(), chi.RouteCtxKey, rctx))

		h.HandlePostback(w, r)
		if w.Code != http.StatusOK {
			t.Errorf("valid alias sig: expected 200, got %d. body: %s", w.Code, w.Body.String())
		}
	})

	t.Run("HMACSignature_MissingSalt_FailsClosed", func(t *testing.T) {
		token := uuid.New().String()
		campID := uuid.New()
		status := "sale"
		payout := "1"

		attrData := fmt.Sprintf(`{"campaign_id":"%s"}`, campID)
		vk.Set(context.Background(), "attr:"+token, attrData, 0)

		mac := hmac.New(sha256.New, []byte("test-salt"))
		mac.Write([]byte(token + "|" + status + "|" + payout))
		sig := hex.EncodeToString(mac.Sum(nil))

		hNoSalt := NewPostbackHandler(logger, &mockSettings{settings: map[string]string{"tracker.postback_key": "test-key"}}, attrSvc, nil, convChan)

		w := httptest.NewRecorder()
		q := url.Values{}
		q.Set("subid", token)
		q.Set("status", status)
		q.Set("payout", payout)
		q.Set("sig", sig)

		r := httptest.NewRequest("GET", "/postback/test-key?"+q.Encode(), nil)
		rctx := chi.NewRouteContext()
		rctx.URLParams.Add("key", "test-key")
		r = r.WithContext(context.WithValue(r.Context(), chi.RouteCtxKey, rctx))

		hNoSalt.HandlePostback(w, r)
		if w.Code != http.StatusInternalServerError {
			t.Errorf("missing salt: expected 500, got %d. body: %s", w.Code, w.Body.String())
		}
	})

	t.Run("HMACSignature_SaltLookupError_FailsClosed", func(t *testing.T) {
		token := uuid.New().String()
		campID := uuid.New()
		status := "sale"
		payout := "2"

		attrData := fmt.Sprintf(`{"campaign_id":"%s"}`, campID)
		vk.Set(context.Background(), "attr:"+token, attrData, 0)

		mac := hmac.New(sha256.New, []byte("test-salt"))
		mac.Write([]byte(token + "|" + status + "|" + payout))
		sig := hex.EncodeToString(mac.Sum(nil))

		hErrSalt := NewPostbackHandler(logger, &mockSettings{
			settings: map[string]string{"tracker.postback_key": "test-key", "tracker.postback_salt": "test-salt"},
			errKeys:  map[string]error{"tracker.postback_salt": fmt.Errorf("boom")},
		}, attrSvc, nil, convChan)

		w := httptest.NewRecorder()
		q := url.Values{}
		q.Set("subid", token)
		q.Set("status", status)
		q.Set("payout", payout)
		q.Set("sig", sig)

		r := httptest.NewRequest("GET", "/postback/test-key?"+q.Encode(), nil)
		rctx := chi.NewRouteContext()
		rctx.URLParams.Add("key", "test-key")
		r = r.WithContext(context.WithValue(r.Context(), chi.RouteCtxKey, rctx))

		hErrSalt.HandlePostback(w, r)
		if w.Code != http.StatusInternalServerError {
			t.Errorf("salt lookup error: expected 500, got %d. body: %s", w.Code, w.Body.String())
		}
	})

	t.Run("Deduplication", func(t *testing.T) {
		token := uuid.New().String()
		txID := "tx-999"
		campID := uuid.New()

		// Seed attribution
		attrData := fmt.Sprintf(`{"campaign_id":"%s"}`, campID)
		vk.Set(context.Background(), "attr:"+token, attrData, 0)

		rctx := chi.NewRouteContext()
		rctx.URLParams.Add("key", "test-key")

		// First
		w1 := httptest.NewRecorder()
		r1 := httptest.NewRequest("GET", "/postback/test-key?subid="+token+"&tid="+txID, nil)
		r1 = r1.WithContext(context.WithValue(r1.Context(), chi.RouteCtxKey, rctx))
		h.HandlePostback(w1, r1)
		if w1.Code != http.StatusOK {
			t.Errorf("first tx: expected 200, got %d", w1.Code)
		}

		// Second
		w2 := httptest.NewRecorder()
		r2 := httptest.NewRequest("GET", "/postback/test-key?subid="+token+"&tid="+txID, nil)
		r2 = r2.WithContext(context.WithValue(r2.Context(), chi.RouteCtxKey, rctx))
		h.HandlePostback(w2, r2)
		if w2.Code != http.StatusConflict {
			t.Errorf("second tx: expected 409, got %d", w2.Code)
		}
	})

	t.Run("HandlePixel", func(t *testing.T) {
		// Drain channel
		for len(convChan) > 0 {
			<-convChan
		}

		token := uuid.New().String()
		campID := uuid.New()

		// Seed attribution
		attrData := fmt.Sprintf(`{"campaign_id":"%s","country_code":"US"}`, campID)
		vk.Set(context.Background(), "attr:"+token, attrData, 0)

		w := httptest.NewRecorder()
		r := httptest.NewRequest("GET", "/pixel.gif?subid="+token+"&payout=5.25&status=sale", nil)

		h.HandlePixel(w, r)

		if w.Code != http.StatusOK {
			t.Errorf("expected 200, got %d", w.Code)
		}

		if ct := w.Header().Get("Content-Type"); ct != "image/gif" {
			t.Errorf("expected image/gif, got %s", ct)
		}

		select {
		case record := <-convChan:
			if record.ClickToken != token {
				t.Errorf("expected token %s, got %s", token, record.ClickToken)
			}
			if record.Payout != 5.25 {
				t.Errorf("expected payout 5.25, got %v", record.Payout)
			}
			if record.Status != "sale" {
				t.Errorf("expected status sale, got %s", record.Status)
			}
			if record.ConversionType != "pixel" {
				t.Errorf("expected conversion type pixel, got %s", record.ConversionType)
			}
		default:
			t.Error("expected record in convChan")
		}
	})
}
