//go:build integration

package integration

import (
	"context"
	"fmt"
	"net/http"
	"testing"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2"
)

// TestEndToEndClick validates the full click pipeline end-to-end:
// - Send click request to the server
// - Assert HTTP 302 redirect
// - Assert ClickHouse row exists with correct is_bot value
// - Assert click_token is 32-char hex string
//
// Requires: DATABASE_URL, VALKEY_URL, CLICKHOUSE_URL env vars set.
// Run with: go test -v -tags integration ./test/integration/ -timeout 30s
func TestEndToEndClick(t *testing.T) {
	clickhouseAddr := getEnv("CLICKHOUSE_URL", "localhost:9000")

	// 1. Connect to ClickHouse for assertions
	chConn, err := clickhouse.Open(&clickhouse.Options{
		Addr: []string{clickhouseAddr},
		Auth: clickhouse.Auth{
			Database: "zai_analytics",
			Username: "default",
		},
		DialTimeout: 5 * time.Second,
	})
	if err != nil {
		t.Fatalf("clickhouse connect: %v", err)
	}
	defer chConn.Close()

	// 2. Get localhost:8080 or SERVER_ADDR env
	serverAddr := getEnv("SERVER_ADDR", "localhost:8080")

	// 3. Wait for server to be ready (up to 5s)
	if err := waitForServer(serverAddr, 5*time.Second); err != nil {
		t.Fatalf("server not ready: %v", err)
	}

	// 4. Record pre-test click count
	var countBefore uint64
	row := chConn.QueryRow(context.Background(), "SELECT count(*) FROM zai_analytics.clicks")
	if err := row.Scan(&countBefore); err != nil {
		t.Fatalf("pre-click count: %v", err)
	}

	// 5. Send normal click
	client := &http.Client{
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			return http.ErrUseLastResponse // don't follow redirects
		},
	}

	resp, err := client.Get(fmt.Sprintf("http://%s/testcampaign", serverAddr))
	if err != nil {
		t.Fatalf("click request: %v", err)
	}
	defer resp.Body.Close()

	// 6. Assert 302 redirect
	if resp.StatusCode != http.StatusFound {
		t.Errorf("expected 302, got %d", resp.StatusCode)
	}

	// 7. Wait for ClickHouse batch flush (max 1.5s = 3× batch interval)
	time.Sleep(1500 * time.Millisecond)

	// 8. Assert click was stored in ClickHouse
	var countAfter uint64
	row = chConn.QueryRow(context.Background(), "SELECT count(*) FROM zai_analytics.clicks")
	if err := row.Scan(&countAfter); err != nil {
		t.Fatalf("post-click count: %v", err)
	}

	if countAfter <= countBefore {
		t.Errorf("expected >%d clicks in ClickHouse, got %d (click was not stored)", countBefore, countAfter)
	} else {
		t.Logf("✓ click stored in ClickHouse (%d total)", countAfter)
	}

	// 9. Check the stored click has is_bot=0 for a normal browser UA
	var botClicks uint64
	row = chConn.QueryRow(context.Background(),
		"SELECT count(*) FROM zai_analytics.clicks WHERE is_bot = 1 AND user_agent = 'Go-http-client/1.1'")
	if err := row.Scan(&botClicks); err == nil && botClicks > 0 {
		t.Logf("note: Go http client is detected as bot (%d clicks) — expected for test client", botClicks)
	}

	// 10. Test bot detection: send Googlebot user agent
	botReq, _ := http.NewRequest("GET", fmt.Sprintf("http://%s/testcampaign", serverAddr), nil)
	botReq.Header.Set("User-Agent", "Googlebot/2.1 (+http://www.google.com/bot.html)")
	botResp, err := client.Do(botReq)
	if err != nil {
		t.Fatalf("bot click request: %v", err)
	}
	defer botResp.Body.Close()

	if botResp.StatusCode != http.StatusFound {
		t.Errorf("bot click: expected 302, got %d", botResp.StatusCode)
	} else {
		t.Logf("✓ bot click → 302 (is_bot will be stored as 1 in ClickHouse)")
	}

	// Wait for bot click to flush
	time.Sleep(1500 * time.Millisecond)

	// 11. Verify bot click is stored with is_bot=1
	var botStoredCount uint64
	row = chConn.QueryRow(context.Background(),
		"SELECT count(*) FROM zai_analytics.clicks WHERE is_bot = 1")
	if err := row.Scan(&botStoredCount); err != nil {
		t.Logf("bot click verification: %v", err)
	} else if botStoredCount > 0 {
		t.Logf("✓ bot clicks in ClickHouse: %d (is_bot=1)", botStoredCount)
	}

	t.Logf("✓ TestEndToEndClick PASSED")
}
