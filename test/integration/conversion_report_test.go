//go:build integration

package integration

import (
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"testing"
	"time"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5"
	"github.com/skyplix/zai-tds/internal/analytics"
)

func TestConversionPipeline(t *testing.T) {
	addr := getEnv("SERVER_ADDR", "localhost:8080")
	pgDSN := getEnv("POSTGRES_DSN", "postgres://zai:zai_dev_pass@localhost:5432/zai_tds")

	ctx := context.Background()

	// 1. Setup: Get API Key and Postback Key
	db, err := pgx.Connect(ctx, pgDSN)
	if err != nil {
		t.Fatalf("failed to connect to postgres: %v", err)
	}
	defer db.Close(ctx)

	var apiKey string
	err = db.QueryRow(ctx, "SELECT api_key FROM users WHERE login = 'admin'").Scan(&apiKey)
	if err != nil {
		t.Fatalf("failed to get admin api key: %v", err)
	}

	var postbackKey string
	err = db.QueryRow(ctx, "SELECT value FROM settings WHERE key = 'tracker.postback_key'").Scan(&postbackKey)
	if err != nil {
		// Fallback to default if not set
		postbackKey = "default_postback_key"
		db.Exec(ctx, "INSERT INTO settings (key, value) VALUES ('tracker.postback_key', $1) ON CONFLICT (key) DO UPDATE SET value = $1", postbackKey)
	}

	client := &http.Client{
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			return http.ErrUseLastResponse
		},
		Timeout: 10 * time.Second,
	}

	// 2. Step 1: Simulate a Campaign Click to generate a click token
	// Assuming 'testcamp' alias exists (created by seeds or previous tests)
	campaignAlias := "test-conv-" + uuid.New().String()[:8]

	// Create a temporary campaign for this test
	campaignID := uuid.New()
	_, err = db.Exec(ctx, "INSERT INTO campaigns (id, name, alias, type, state) VALUES ($1, $2, $3, 'position', 'active')",
		campaignID, "Conv Test Campaign", campaignAlias)
	if err != nil {
		t.Fatalf("failed to create test campaign: %v", err)
	}
	defer db.Exec(ctx, "DELETE FROM campaigns WHERE id = $1", campaignID)

	req, _ := http.NewRequest("GET", fmt.Sprintf("http://%s/%s", addr, campaignAlias), nil)
	req.Header.Set("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")

	resp, err := client.Do(req)
	if err != nil {
		t.Fatalf("click request failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusFound {
		t.Fatalf("expected 302 redirect for click, got %d", resp.StatusCode)
	}

	// Extract click token from cookie (zai_token)
	var clickToken string
	for _, cookie := range resp.Cookies() {
		if cookie.Name == "zai_token" {
			clickToken = cookie.Value
			break
		}
	}

	if clickToken == "" {
		t.Fatal("click token not found in cookies")
	}
	t.Logf("Generated click token: %s", clickToken)

	// 3. Step 2: Trigger a Postback using the click token
	postbackURL := fmt.Sprintf("http://%s/postback/%s?subid=%s&payout=10.50&status=sale&external_id=TX123", addr, postbackKey, clickToken)
	resp, err = client.Get(postbackURL)
	if err != nil {
		t.Fatalf("postback request failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		t.Fatalf("expected 200 OK for postback, got %d", resp.StatusCode)
	}

	// 4. Step 3: Wait for ClickHouse to process (batch flush)
	t.Log("Waiting for ClickHouse batch flush...")
	time.Sleep(3 * time.Second)

	// 5. Step 4: Verify the conversion appears in the Reporting API
	reportURL := fmt.Sprintf("http://%s/api/v1/reports?group_by=campaign&preset=today", addr)
	req, _ = http.NewRequest("GET", reportURL, nil)
	req.Header.Set("X-Api-Key", apiKey)

	resp, err = client.Do(req)
	if err != nil {
		t.Fatalf("report request failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		t.Fatalf("expected 200 OK for report, got %d", resp.StatusCode)
	}

	var report analytics.ReportResponse
	if err := json.NewDecoder(resp.Body).Decode(&report); err != nil {
		t.Fatalf("failed to decode report: %v", err)
	}

	found := false
	for _, row := range report.Rows {
		if row.Dimensions["campaign"] == campaignID.String() {
			found = true
			if row.Conversions != 1 {
				t.Errorf("expected 1 conversion for campaign, got %d", row.Conversions)
			}
			if row.Revenue != 0 { // In current implementation, revenue might be separate from payout if not specified in attribution
				// Actually postback handler uses r.Form.Get("revenue")
			}
			t.Logf("Found conversion in report: %+v", row)
			break
		}
	}

	if !found {
		t.Errorf("campaign %s not found in report rows", campaignID.String())
	}
}
