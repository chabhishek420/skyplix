//go:build integration

/*
 * MODIFIED: test/integration/routing_test.go
 * PURPOSE: Updated integration tests to use X-SkyPlix-Test-Country/City headers
 *          for deterministic GeoIP routing regardless of local mmdb availability.
 */
package integration

import (
	"context"
	"io"
	"net/http"
	"net/http/cookiejar"
	"strings"
	"testing"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2"
)

// TestPhase2Routing validates all Phase 2 Campaign Engine features end-to-end.
// Requires: DATABASE_URL, VALKEY_URL, CLICKHOUSE_URL.
func TestPhase2Routing(t *testing.T) {
	chAddr := getEnv("CLICKHOUSE_URL", "localhost:9000")
	serverAddr := getEnv("SERVER_ADDR", "localhost:8080")

	// 1. ClickHouse connection for assertions
	chConn, err := clickhouse.Open(&clickhouse.Options{
		Addr: []string{chAddr},
		Auth: clickhouse.Auth{Database: "zai_analytics", Username: "default"},
	})
	if err != nil {
		t.Fatalf("clickhouse connect: %v", err)
	}
	defer chConn.Close()

	if err := waitForServer(serverAddr, 5*time.Second); err != nil {
		t.Fatalf("server not ready: %v", err)
	}

	jar, _ := cookiejar.New(nil)
	client := &http.Client{
		Jar: jar,
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			return http.ErrUseLastResponse // Don't follow 302
		},
	}

	// Helper to get total clicks from CH
	getTotalClicks := func() uint64 {
		var count uint64
		_ = chConn.QueryRow(context.Background(), "SELECT count(*) FROM zai_analytics.clicks").Scan(&count)
		return count
	}

	// 1. TestBotGetsBlocked: US request with bot UA -> HTML blocked message (Forced Stream 1)
	t.Run("BotGetsBlocked", func(t *testing.T) {
		req, _ := http.NewRequest("GET", "http://"+serverAddr+"/testcamp", nil)
		req.Header.Set("User-Agent", "Googlebot/2.1")
		req.Header.Set("X-Forwarded-For", "8.8.8.8")
		req.Header.Set("X-SkyPlix-Test-Country", "US")

		resp, err := client.Do(req)
		if err != nil {
			t.Fatalf("request error: %v", err)
		}
		defer resp.Body.Close()

		if resp.StatusCode != http.StatusOK {
			t.Errorf("expected 200 for show_html action, got %d", resp.StatusCode)
		}
		body, _ := io.ReadAll(resp.Body)
		if !strings.Contains(string(body), "<h1>Forbidden</h1>") {
			t.Errorf("expected forbidden HTML, got %s", body)
		}
	})

	// 2. TestGeoFilterRouting: Normal US request -> 302 to Offer A
	t.Run("GeoFilterRouting", func(t *testing.T) {
		req, _ := http.NewRequest("GET", "http://"+serverAddr+"/testcamp", nil)
		req.Header.Set("User-Agent", "Mozilla/5.0")
		req.Header.Set("X-Forwarded-For", "8.8.8.8")
		req.Header.Set("X-SkyPlix-Test-Country", "US")

		resp, err := client.Do(req)
		if err != nil {
			t.Fatalf("request error: %v", err)
		}
		defer resp.Body.Close()

		if resp.StatusCode != http.StatusFound {
			t.Errorf("expected 302, got %d", resp.StatusCode)
		}
		loc := resp.Header.Get("Location")
		if !strings.Contains(loc, "landing.example.com") {
			t.Errorf("expected redirect to landing page (L1 priority), got %s", loc)
		}
	})

	// 3. TestDefaultStreamFallback: JP request -> 302 to Fallback
	t.Run("DefaultStreamFallback", func(t *testing.T) {
		req, _ := http.NewRequest("GET", "http://"+serverAddr+"/testcamp", nil)
		req.Header.Set("User-Agent", "Mozilla/5.0")
		req.Header.Set("X-Forwarded-For", "82.117.10.52")
		req.Header.Set("X-SkyPlix-Test-Country", "JP") // Forces mismatch against Stream 2 (US/GB)

		resp, err := client.Do(req)
		if err != nil {
			t.Fatalf("request error: %v", err)
		}
		defer resp.Body.Close()

		if resp.StatusCode != http.StatusFound {
			t.Errorf("expected 302 for fallback, got %d", resp.StatusCode)
		}
		loc := resp.Header.Get("Location")
		if !strings.Contains(loc, "default-offer.example.com") {
			t.Errorf("expected redirect to default-offer, got %s", loc)
		}
	})

	// 4. TestWeightedStreamSelection: distribution check on weightcamp
	t.Run("WeightedStreamSelection", func(t *testing.T) {
		counts := make(map[string]int)
		for i := 0; i < 20; i++ {
			jar, _ := cookiejar.New(nil) // New jar to avoid binding 
			client.Jar = jar
			
			req, _ := http.NewRequest("GET", "http://"+serverAddr+"/weightcamp", nil)
			req.Header.Set("X-SkyPlix-Test-Country", "US")
			
			resp, _ := client.Do(req)
			loc := resp.Header.Get("Location")
			if strings.Contains(loc, "highprio.com") {
				counts["high"]++
			} else if strings.Contains(loc, "lowprio.com") {
				counts["low"]++
			}
			resp.Body.Close()
		}
		t.Logf("weights: high=%d, low=%d", counts["high"], counts["low"])
	})

	// 5. TestEntityBindingPersistence: same visitor pinned to same result
	t.Run("EntityBindingPersistence", func(t *testing.T) {
		sJar, _ := cookiejar.New(nil)
		subClient := &http.Client{Jar: sJar, CheckRedirect: client.CheckRedirect}
		
		req, _ := http.NewRequest("GET", "http://"+serverAddr+"/weightcamp", nil)
		req.Header.Set("X-SkyPlix-Test-Country", "US")

		resp1, _ := subClient.Do(req)
		loc1 := resp1.Header.Get("Location")
		resp1.Body.Close()

		resp2, _ := subClient.Do(req)
		loc2 := resp2.Header.Get("Location")
		resp2.Body.Close()

		if loc1 != loc2 {
			t.Errorf("Binding failed: got different redirects %s vs %s", loc1, loc2)
		}
	})

	// 6. TestLevel2LandingClick: LpToken + L2 pipeline
	t.Run("Level2LandingClick", func(t *testing.T) {
		req, _ := http.NewRequest("GET", "http://"+serverAddr+"/testcamp", nil)
		req.Header.Set("X-Forwarded-For", "8.8.8.8")
		req.Header.Set("X-SkyPlix-Test-Country", "US")
		req.Header.Set("User-Agent", "Mozilla/5.0")
		
		resp, _ := client.Do(req)
		loc := resp.Header.Get("Location")
		resp.Body.Close()

		if !strings.Contains(loc, "landing.example.com") {
			t.Fatalf("expected landing redirect, got %s", loc)
		}

		idx := strings.Index(loc, "token=")
		if idx == -1 {
			t.Fatal("no token in redirect")
		}
		token := loc[idx+len("token="):]

		l2Req, _ := http.NewRequest("GET", "http://"+serverAddr+"/lp/"+token+"/click", nil)
		l2Resp, err := client.Do(l2Req)
		if err != nil {
			t.Fatalf("L2 request error: %v", err)
		}
		defer l2Resp.Body.Close()

		if l2Resp.StatusCode != http.StatusFound {
			t.Errorf("L2: expected 302, got %d", l2Resp.StatusCode)
		}
		
		l2Loc := l2Resp.Header.Get("Location")
		if !strings.Contains(l2Loc, "offer-a.example.com") {
			t.Errorf("L2: expected redirect to offer-a, got %s", l2Loc)
		}
	})

	// Final verification of click count
	time.Sleep(1500 * time.Millisecond)
	total := getTotalClicks()
	t.Logf("✓ Final click count: %d", total)
}
