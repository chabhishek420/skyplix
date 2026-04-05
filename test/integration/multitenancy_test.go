//go:build integration

package integration

import (
	"context"
	"testing"

	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/admin/repository"
	"github.com/skyplix/zai-tds/internal/model"
)

func TestMultiTenancyIsolation(t *testing.T) {
	dbURL := getEnv("DATABASE_URL", "postgres://postgres:postgres@localhost:5432/zai_config?sslmode=disable")

	// 1. Setup DB and repositories
	pool := setupDB(t, dbURL)
	defer pool.Close()

	repo := repository.NewCampaignRepository(pool)

	ws1 := uuid.New()
	ws2 := uuid.New()

	ctx := context.Background()

	// 2. Create campaign in Workspace 1
	c1 := &model.Campaign{
		ID: ws1, // reusing UUID for test simplicity
		WorkspaceID: ws1,
		Alias: "camp-ws1",
		Name: "Campaign WS1",
	}
	err := repo.Create(ctx, c1)
	if err != nil {
		t.Fatalf("create campaign 1: %v", err)
	}

	// 3. Create campaign in Workspace 2
	c2 := &model.Campaign{
		ID: ws2,
		WorkspaceID: ws2,
		Alias: "camp-ws2",
		Name: "Campaign WS2",
	}
	err = repo.Create(ctx, c2)
	if err != nil {
		t.Fatalf("create campaign 2: %v", err)
	}

	// 4. Verify WS1 cannot see WS2 campaign
	camps, err := repo.List(ctx, ws1, 10, 0)
	if err != nil {
		t.Fatalf("list ws1: %v", err)
	}
	if len(camps) != 1 || camps[0].Alias != "camp-ws1" {
		t.Errorf("ws1 should only see its own campaign, got %v", camps)
	}

	// 5. Verify WS2 cannot see WS1 campaign
	camps, err = repo.List(ctx, ws2, 10, 0)
	if err != nil {
		t.Fatalf("list ws2: %v", err)
	}
	if len(camps) != 1 || camps[0].Alias != "camp-ws2" {
		t.Errorf("ws2 should only see its own campaign, got %v", camps)
	}
}
