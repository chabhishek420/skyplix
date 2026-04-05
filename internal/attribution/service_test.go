package attribution

import (
	"context"
	"testing"

	"github.com/alicebob/miniredis/v2"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"
)

func TestService_IsDuplicate(t *testing.T) {
	mr, err := miniredis.Run()
	if err != nil {
		t.Fatal(err)
	}
	defer mr.Close()

	client := redis.NewClient(&redis.Options{
		Addr: mr.Addr(),
	})

	s := New(client, zap.NewNop())
	ctx := context.Background()

	// 1. First time - should not be duplicate
	isDup, err := s.IsDuplicate(ctx, "tx-123")
	if err != nil {
		t.Errorf("unexpected error: %v", err)
	}
	if isDup {
		t.Error("expected tx-123 to be new, but marked as duplicate")
	}

	// 2. Second time - should be duplicate
	isDup, err = s.IsDuplicate(ctx, "tx-123")
	if err != nil {
		t.Errorf("unexpected error: %v", err)
	}
	if !isDup {
		t.Error("expected tx-123 to be duplicate, but marked as new")
	}

	// 3. Different ID - should not be duplicate
	isDup, err = s.IsDuplicate(ctx, "tx-456")
	if err != nil {
		t.Errorf("unexpected error: %v", err)
	}
	if isDup {
		t.Error("expected tx-456 to be new, but marked as duplicate")
	}

	// 4. Empty ID - should never be duplicate
	isDup, err = s.IsDuplicate(ctx, "")
	if err != nil {
		t.Errorf("unexpected error: %v", err)
	}
	if isDup {
		t.Error("expected empty txid to never be duplicate")
	}
}
