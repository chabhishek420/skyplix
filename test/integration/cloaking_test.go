//go:build integration

package integration

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"testing"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2"
	"github.com/jackc/pgx/v5"
)

// TestCloaking validates the full Phase 4 cloaking and bot detection system.
// - Seed Postgres with a cloaked campaign (Human stream + Bot stream)
// - Test Human access (redirect to offer)
// - Test Bot access (UA, IP, custom UA, Rate limit)
// - Verify ClickHouse records
func TestCloaking(t *testing.T) {
	postgresDSN := getEnv("POSTGRES_DSN", getEnv("DATABASE_URL", "postgres://zai:zai_dev_pass@localhost:5432/zai_tds?sslmode=disable"))
	clickhouseAddr := getEnv("CLICKHOUSE_URL", "localhost:9000")
	serverAddr := getEnv("SERVER_ADDR", "localhost:8080")
	apiKey := getEnv("ADMIN_API_KEY", "")

	// 1. Setup connections
	fmt.Println("connecting to postgres...")
	ctx := context.Background()
	pgConn, err := pgx.Connect(ctx, postgresDSN)
	if err != nil {
		t.Fatalf("postgres connect: %v", err)
	}
	defer pgConn.Close(ctx)

	if apiKey == "" {
		if err := pgConn.QueryRow(ctx, "SELECT api_key FROM users WHERE login = 'admin'").Scan(&apiKey); err != nil {
			t.Fatalf("load admin api key from postgres: %v", err)
		}
	}

	fmt.Println("connecting to clickhouse...")
	chConn, err := clickhouse.Open(&clickhouse.Options{
		Addr:        []string{clickhouseAddr},
		DialTimeout: 5 * time.Second,
		Auth:        clickhouse.Auth{Database: "zai_analytics", Username: "default"},
	})
	if err != nil {
		t.Fatalf("clickhouse connect: %v", err)
	}
	defer chConn.Close()

	fmt.Println("waiting for server...")
	if err := waitForServer(serverAddr, 5*time.Second); err != nil {
		t.Fatalf("server not ready: %v", err)
	}

	fmt.Println("seeding data...")
	seedSQL, err := os.ReadFile("testdata/seed_phase4.sql")
	if err != nil {
		t.Fatalf("read seed file: %v", err)
	}
	if _, err := pgConn.Exec(ctx, string(seedSQL)); err != nil {
		t.Fatalf("seed postgres: %v", err)
	}
	t.Log("✓ database seeded for Phase 4")

	client := &http.Client{
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			return http.ErrUseLastResponse
		},
	}

	// Helper for sending clicks
	sendClick := func(ua string, ip string) *http.Response {
		req, _ := http.NewRequest("GET", fmt.Sprintf("http://%s/cloaked-test", serverAddr), nil)
		if ua != "" {
			req.Header.Set("User-Agent", ua)
		}
		if ip != "" {
			req.Header.Set("X-Forwarded-For", ip)
		}
		resp, err := client.Do(req)
		if err != nil {
			t.Fatalf("click request (%s): %v", ua, err)
		}
		return resp
	}

	// --- Case 1: Human (Normal Browser) ---
	t.Run("HumanGetsOffer", func(t *testing.T) {
		// Use a residential EU IP (not a datacenter/cloud ASN)
		resp := sendClick("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36", "82.117.10.50")
		defer resp.Body.Close()
		if resp.StatusCode != http.StatusFound {
			body, _ := io.ReadAll(resp.Body)
			t.Errorf("expected 302 redirect for human, got %d (body: %s)", resp.StatusCode, string(body))
		}
		if loc := resp.Header.Get("Location"); loc != "https://real-offer.com" {
			t.Errorf("expected redirect to offer, got %q", loc)
		}
	})

	// --- Case 2: Known Bot (Googlebot UA) ---
	t.Run("GooglebotGetsSafePage", func(t *testing.T) {
		resp := sendClick("Googlebot/2.1 (+http://www.google.com/bot.html)", "66.249.66.1")
		defer resp.Body.Close()
		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200 for bot, got %d", resp.StatusCode)
		}
		body, _ := io.ReadAll(resp.Body)
		if !bytes.Contains(body, []byte("Welcome to our safe page")) {
			t.Error("safe page content missing")
		}
	})

	// --- Case 3: Empty UA ---
	t.Run("EmptyUAGetsSafePage", func(t *testing.T) {
		resp := sendClick(" ", "2.2.2.2")
		defer resp.Body.Close()
		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200 for empty UA, got %d", resp.StatusCode)
		}
	})

	// --- Case 4: Bot IP (via Admin API) ---
	t.Run("BotIPGetsSafePage", func(t *testing.T) {
		botIP := "192.168.100.100"
		// 1. Add IP to botdb via API
		payload, _ := json.Marshal(map[string]string{"ips": botIP})
		req, _ := http.NewRequest("POST", fmt.Sprintf("http://%s/api/v1/bots/ips", serverAddr), bytes.NewBuffer(payload))
		req.Header.Set("X-Api-Key", apiKey)
		req.Header.Set("Content-Type", "application/json")
		apiResp, err := client.Do(req)
		if err != nil || apiResp.StatusCode != http.StatusOK {
			t.Fatalf("failed to add bot ip via API: %v (status %d)", err, apiResp.StatusCode)
		}
		apiResp.Body.Close()

		// 2. Access from that IP
		resp := sendClick("Mozilla/5.0", botIP)
		defer resp.Body.Close()
		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200 for bot IP, got %d", resp.StatusCode)
		}
	})

	// --- Case 5: Custom UA Signature (via Admin API) ---
	t.Run("CustomUAGetsSafePage", func(t *testing.T) {
		uaSig := "mysecretcrawler"
		// 1. Add UA to botdb via API
		payload, _ := json.Marshal(map[string]string{"patterns": uaSig})
		req, _ := http.NewRequest("POST", fmt.Sprintf("http://%s/api/v1/bots/ua", serverAddr), bytes.NewBuffer(payload))
		req.Header.Set("X-Api-Key", apiKey)
		req.Header.Set("Content-Type", "application/json")
		apiResp, err := client.Do(req)
		if err != nil || apiResp.StatusCode != http.StatusOK {
			t.Fatalf("failed to add custom ua via API: %v (status %d)", err, apiResp.StatusCode)
		}
		apiResp.Body.Close()

		// 2. Access with that UA (use a non-datacenter IP)
		resp := sendClick("Mozilla/5.0 ("+uaSig+"/1.0)", "82.117.10.51")
		defer resp.Body.Close()
		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200 for custom UA, got %d", resp.StatusCode)
		}
	})

	// --- Case 6: Rate Limiting ---
	t.Run("RateLimitedGetsSafePage", func(t *testing.T) {
		spamIP := "4.4.4.4"
		// Send 65 requests quickly (limit is 60/min)
		lastStatus := 0
		for i := 0; i < 65; i++ {
			// Use a long browser UA to avoid pattern-matching, and non-datacenter IP
			resp := sendClick("Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36", spamIP)
			lastStatus = resp.StatusCode
			resp.Body.Close()
			if lastStatus == http.StatusOK {
				break // Hit the rate limit!
			}
		}
		if lastStatus != http.StatusOK {
			t.Errorf("rate limiting did not trigger safe page (last status %d)", lastStatus)
		}
	})

	// --- Case 7: Remote Proxy Action ---
	t.Run("RemoteProxyActionWorks", func(t *testing.T) {
		// Use a bot UA to trigger stream 1 (Remote action to /api/v1/health)
		u := fmt.Sprintf("http://%s/cloaked-proxy-test", serverAddr)
		req, _ := http.NewRequest("GET", u, nil)
		req.Header.Set("User-Agent", "googlebot") // Trigger bot filter
		req.Header.Set("X-Forwarded-For", "66.249.66.1") // Correctly set the IP

		resp, err := client.Do(req)
		if err != nil {
			t.Fatalf("failed to request remote proxy: %v", err)
		}
		defer resp.Body.Close()

		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200 for remote proxy, got %d", resp.StatusCode)
		}
		body, _ := io.ReadAll(resp.Body)
		if !bytes.Contains(body, []byte(`"status":"ok"`)) {
			t.Errorf("expected health check response from proxy, got: %s", string(body))
		}
		if resp.Header.Get("X-Cache-Status") != "HIT" {
			// Request twice to verify HIT.
			resp2, _ := client.Do(req)
			defer resp2.Body.Close()
			if resp2.Header.Get("X-Cache-Status") != "HIT" {
				t.Errorf("expected X-Cache-Status: HIT on second request, got %s", resp2.Header.Get("X-Cache-Status"))
			}
		}
	})

	// --- Case 8: Curl Action ---
	t.Run("CurlActionWorks", func(t *testing.T) {
		// Just normal UA (no special filter) -> fallback to stream 2 (Curl)
		u := fmt.Sprintf("http://%s/cloaked-proxy-test", serverAddr)
		req, _ := http.NewRequest("GET", u, nil)
		req.Header.Set("User-Agent", "curl/7.68.0") // Substring match botUAPatterns -> trigger stream 2

		resp, err := client.Do(req)
		if err != nil {
			t.Fatalf("failed to request curl action: %v", err)
		}
		defer resp.Body.Close()

		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200 for curl action, got %d", resp.StatusCode)
		}
		body, _ := io.ReadAll(resp.Body)
		if !bytes.Contains(body, []byte(`"status":"ok"`)) {
			t.Errorf("expected health check response from curl, got: %s", string(body))
		}
	})

	// 3. Final ClickHouse Verification
	t.Run("ClickHouseVerification", func(t *testing.T) {
		time.Sleep(1500 * time.Millisecond) // Wait for flush
		
		var botClicks uint64
		row := chConn.QueryRow(ctx, "SELECT count(*) FROM zai_analytics.clicks WHERE is_bot = 1 AND campaign_alias = 'cloaked-test'")
		if err := row.Scan(&botClicks); err != nil {
			t.Fatalf("clickhouse query: %v", err)
		}
		if botClicks < 5 { // Case 2, 3, 4, 5, 6 (at least 5 bot clicks)
			t.Errorf("expected at least 5 bot clicks in ClickHouse, got %d", botClicks)
		}
		t.Logf("✓ verified %d bot clicks in ClickHouse", botClicks)
	})
}
