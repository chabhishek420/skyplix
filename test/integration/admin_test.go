//go:build integration

package integration

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"testing"
	"time"

	"github.com/google/uuid"
	"github.com/redis/go-redis/v9"
	"github.com/jackc/pgx/v5"

	"github.com/skyplix/zai-tds/internal/model"
)

func TestAdminAPI(t *testing.T) {
	addr := getEnv("TDS_ADDR", "http://localhost:8080")
	pgDSN := getEnv("POSTGRES_DSN", "postgres://zai:zai_dev_pass@localhost:5432/zai_tds")
	vkAddr := getEnv("VALKEY_ADDR", "localhost:6379")

	ctx := context.Background()

	// 1. Get API Key for admin (from DB)
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

	client := &http.Client{Timeout: 5 * time.Second}

	// 2. Test Health
	resp, err := client.Get(addr + "/api/v1/health")
	if err != nil {
		t.Fatalf("health check failed: %v", err)
	}
	if resp.StatusCode != http.StatusOK {
		t.Fatalf("health check status not ok: %v", resp.StatusCode)
	}

	// 3. Create Campaign
	campaign := model.Campaign{
		Name:  "Integration Test Campaign",
		Alias: "int-test-" + uuid.New().String()[:8],
		Type:  model.CampaignTypePosition,
	}
	body, _ := json.Marshal(campaign)
	req, _ := http.NewRequest("POST", addr+"/api/v1/campaigns", bytes.NewBuffer(body))
	req.Header.Set("X-Api-Key", apiKey)
	req.Header.Set("Content-Type", "application/json")

	resp, err = client.Do(req)
	if err != nil || resp.StatusCode != http.StatusCreated {
		t.Fatalf("create campaign failed: %v, status: %v", err, resp.StatusCode)
	}

	var createdCampaign model.Campaign
	json.NewDecoder(resp.Body).Decode(&createdCampaign)
	campaignID := createdCampaign.ID

	// 4. Verify Warmup Flag in Valkey
	vk := redis.NewClient(&redis.Options{Addr: vkAddr})
	defer vk.Close()

	// Wait a moment for async flag set if needed (though it's synchronous in handler)
	exists, _ := vk.Exists(ctx, "warmup:scheduled").Result()
	if exists == 0 {
		t.Errorf("warmup flag not found in Valkey after campaign creation")
	}

	// 5. Create Stream
	stream := model.Stream{
		CampaignID: campaignID,
		Name:       "Test Stream",
		Type:       model.StreamTypeRegular,
		State:      "active",
	}
	body, _ = json.Marshal(stream)
	req, _ = http.NewRequest("POST", addr+"/api/v1/streams", bytes.NewBuffer(body))
	req.Header.Set("X-Api-Key", apiKey)
	req.Header.Set("Content-Type", "application/json")

	resp, err = client.Do(req)
	if err != nil || resp.StatusCode != http.StatusCreated {
		t.Fatalf("create stream failed: %v, status: %v", err, resp.StatusCode)
	}

	// 6. Cleanup
	req, _ = http.NewRequest("DELETE", addr+"/api/v1/campaigns/"+campaignID.String(), nil)
	req.Header.Set("X-Api-Key", apiKey)
	client.Do(req)
}
