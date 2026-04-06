package attribution

import (
	"context"
	"testing"

	"github.com/alicebob/miniredis/v2"
	"github.com/google/uuid"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

func TestService_IsDuplicateExternalID(t *testing.T) {
	mr, err := miniredis.Run()
	if err != nil {
		t.Fatal(err)
	}
	defer mr.Close()

	vk := redis.NewClient(&redis.Options{
		Addr: mr.Addr(),
	})
	s := New(vk, zap.NewNop())
	ctx := context.Background()

	txID := "test-tx-123"

	// 1. First check - not duplicate
	isDup, err := s.IsDuplicateExternalID(ctx, txID)
	if err != nil {
		t.Fatalf("first check failed: %v", err)
	}
	if isDup {
		t.Error("expected first check to be false")
	}

	// 2. Second check - duplicate
	isDup, err = s.IsDuplicateExternalID(ctx, txID)
	if err != nil {
		t.Fatalf("second check failed: %v", err)
	}
	if !isDup {
		t.Error("expected second check to be true")
	}

	// 3. Different ID - not duplicate
	isDup, err = s.IsDuplicateExternalID(ctx, "other-tx")
	if err != nil {
		t.Fatalf("other check failed: %v", err)
	}
	if isDup {
		t.Error("expected other-tx to be false")
	}
}

func TestService_SaveAndGetAttribution(t *testing.T) {
	mr, err := miniredis.Run()
	if err != nil {
		t.Fatal(err)
	}
	defer mr.Close()

	vk := redis.NewClient(&redis.Options{
		Addr: mr.Addr(),
	})
	s := New(vk, zap.NewNop())
	ctx := context.Background()

	token := "click-token-123"
	data := model.AttributionData{
		CampaignID:  uuid.New(),
		StreamID:    uuid.New(),
		CountryCode: "US",
	}

	err = s.SaveClickAttribution(ctx, token, data)
	if err != nil {
		t.Fatalf("save failed: %v", err)
	}

	retrieved, err := s.GetClickAttribution(ctx, token)
	if err != nil {
		t.Fatalf("get failed: %v", err)
	}
	if retrieved == nil {
		t.Fatal("expected to retrieve data")
	}
	if retrieved.CampaignID != data.CampaignID {
		t.Errorf("expected campaign ID %s, got %s", data.CampaignID, retrieved.CampaignID)
	}
}
