//go:build integration

package integration

import (
	"context"
	"fmt"
	"net/http"
	"testing"
	"time"

	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/admin/repository"
	"github.com/skyplix/zai-tds/internal/model"
)

func TestFullCampaignLifecycle(t *testing.T) {
	dbURL := getEnv("DATABASE_URL", "postgres://postgres:postgres@localhost:5432/zai_config?sslmode=disable")
	serverAddr := getEnv("SERVER_ADDR", "localhost:8080")

	pool := setupDB(t, dbURL)
	defer pool.Close()

	campRepo := repository.NewCampaignRepository(pool)
	streamRepo := repository.NewStreamRepository(pool)
	wsID := uuid.MustParse("00000000-0000-4000-a000-000000000001")
	ctx := context.Background()

	// 1. Create a Campaign
	camp := &model.Campaign{
		WorkspaceID: wsID,
		Alias:       "lifecycle-test",
		Name:        "End to End Test",
		State:       "active",
	}
	err := campRepo.Create(ctx, camp)
	if err != nil {
		t.Fatalf("create campaign: %v", err)
	}

	// 2. Add a Safe Page Stream (Forced)
	safeStream := &model.Stream{
		WorkspaceID: wsID,
		CampaignID:  camp.ID,
		Name:        "Safe Page",
		Type:        model.StreamTypeForced,
		Position:    1,
		ActionType:  "ShowHtml",
		ActionPayload: map[string]interface{}{"html": "<h1>Safe Page</h1>"},
		Filters: []model.StreamFilter{
			{Type: "IsBot", Payload: map[string]interface{}{"is_bot": true}},
		},
		State: "active",
	}
	err = streamRepo.Create(ctx, safeStream)
	if err != nil {
		t.Fatalf("create safe stream: %v", err)
	}

	// 3. Add a Regular Stream (Money Page)
	moneyStream := &model.Stream{
		WorkspaceID: wsID,
		CampaignID:  camp.ID,
		Name:        "Money Page",
		Type:        model.StreamTypeRegular,
		Position:    2,
		ActionType:  "HttpRedirect",
		ActionPayload: map[string]interface{}{"url": "https://money.com"},
		State: "active",
	}
	err = streamRepo.Create(ctx, moneyStream)
	if err != nil {
		t.Fatalf("create money stream: %v", err)
	}

	// 4. Perform Clicks
	client := &http.Client{
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			return http.ErrUseLastResponse
		},
	}

	// A. Bot Click -> Should see Safe Page
	t.Run("BotTraffic", func(t *testing.T) {
		req, _ := http.NewRequest("GET", "http://"+serverAddr+"/lifecycle-test", nil)
		req.Header.Set("User-Agent", "Googlebot/2.1")
		resp, err := client.Do(req)
		if err != nil {
			t.Fatalf("request failed: %v", err)
		}
		defer resp.Body.Close()
		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200 for safe page, got %d", resp.StatusCode)
		}
	})

	// B. Human Click -> Should see Redirect
	t.Run("HumanTraffic", func(t *testing.T) {
		req, _ := http.NewRequest("GET", "http://"+serverAddr+"/lifecycle-test", nil)
		req.Header.Set("User-Agent", "Mozilla/5.0")
		resp, err := client.Do(req)
		if err != nil {
			t.Fatalf("request failed: %v", err)
		}
		defer resp.Body.Close()
		if resp.StatusCode != http.StatusFound {
			t.Errorf("expected 302 for money page, got %d", resp.StatusCode)
		}
		if loc := resp.Header.Get("Location"); loc != "https://money.com" {
			t.Errorf("expected redirect to money.com, got %s", loc)
		}
	})
}
