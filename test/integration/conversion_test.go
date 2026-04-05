//go:build integration

package integration

import (
	"context"
	"fmt"
	"net/http"
	"net/url"
	"testing"
	"time"

	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/attribution"
	"github.com/skyplix/zai-tds/internal/model"
)

func TestConversionTrackingCycle(t *testing.T) {
	chAddr := getEnv("CLICKHOUSE_URL", "localhost:9000")
	serverAddr := getEnv("SERVER_ADDR", "localhost:8080")
	vkAddr := getEnv("VALKEY_URL", "localhost:6379")

	// 1. Attribution service setup
	vkClient := setupValkey(t, vkAddr)
	defer vkClient.Close()
	attrService := attribution.New(vkClient, nil)

	// 2. Simulate a click context in Valkey
	clickToken := "test-conversion-token-" + uuid.New().String()
	wsID := uuid.MustParse("00000000-0000-4000-a000-000000000001")
	attrData := model.AttributionData{
		WorkspaceID: wsID,
		CampaignID:  uuid.New(),
		StreamID:    uuid.New(),
		OfferID:     uuid.New(),
		CountryCode: "US",
	}

	err := attrService.SaveClickAttribution(context.Background(), clickToken, attrData)
	if err != nil {
		t.Fatalf("failed to seed attribution: %v", err)
	}

	client := &http.Client{Timeout: 5 * time.Second}

	// 3. Test S2S Postback
	t.Run("S2SPostback", func(t *testing.T) {
		// postback key from seed_phase4.sql is "secret123"
		postbackURL := fmt.Sprintf("http://%s/postback/secret123?subid=%s&payout=10.50&status=sale&external_id=TX-1", serverAddr, clickToken)

		resp, err := client.Get(postbackURL)
		if err != nil {
			t.Fatalf("postback request failed: %v", err)
		}
		defer resp.Body.Close()

		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200, got %d", resp.StatusCode)
		}
	})

	// 4. Test Deduplication
	t.Run("PostbackDeduplication", func(t *testing.T) {
		postbackURL := fmt.Sprintf("http://%s/postback/secret123?subid=%s&payout=10.50&status=sale&external_id=TX-1", serverAddr, clickToken)

		resp, err := client.Get(postbackURL)
		if err != nil {
			t.Fatalf("duplicate postback request failed: %v", err)
		}
		defer resp.Body.Close()

		if resp.StatusCode != http.StatusConflict {
			t.Errorf("expected 409 Conflict for duplicate transaction, got %d", resp.StatusCode)
		}
	})

	// 5. Test Pixel Tracking
	t.Run("PixelTracking", func(t *testing.T) {
		pixelURL := fmt.Sprintf("http://%s/pixel.gif?sub_id=%s&status=lead&payout=0.50", serverAddr, clickToken)

		resp, err := client.Get(pixelURL)
		if err != nil {
			t.Fatalf("pixel request failed: %v", err)
		}
		defer resp.Body.Close()

		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200, got %d", resp.StatusCode)
		}
		if ct := resp.Header.Get("Content-Type"); ct != "image/gif" {
			t.Errorf("expected image/gif, got %s", ct)
		}
	})
}
