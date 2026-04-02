package integration

import (
	"encoding/json"
	"net/http"
	"os"
	"testing"
)

func TestAdminAuth(t *testing.T) {
	// Use environment variables for the test server
	adminURL := getEnv("ADMIN_URL", "http://localhost:8080/api/v1/admin/ping")
	adminKey := getEnv("ADMIN_API_KEY", "test-secret-key")

	// 1. Test without API key (Should be 401 Unauthorized)
	// Note: In our current implementation, if debug is true and key is empty, it allows all.
	// We'll set a specific key for this test if possible, or assume it's set in the test environment.

	req, _ := http.NewRequest("GET", adminURL, nil)
	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("Failed to request admin endpoint: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusUnauthorized {
		t.Errorf("Expected 401 Unauthorized without API key, got %d", resp.StatusCode)
	}

	// 2. Test with invalid API key (Should be 401 Unauthorized)
	req, _ = http.NewRequest("GET", adminURL, nil)
	req.Header.Set("X-API-Key", "wrong-key")
	resp, err = http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("Failed to request admin endpoint: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusUnauthorized {
		t.Errorf("Expected 401 Unauthorized with wrong API key, got %d", resp.StatusCode)
	}

	// 3. Test with valid API key (Should be 200 OK)
	req, _ = http.NewRequest("GET", adminURL, nil)
	req.Header.Set("X-API-Key", adminKey)
	resp, err = http.DefaultClient.Do(req)
	if err != nil {
		t.Fatalf("Failed to request admin endpoint: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		t.Errorf("Expected 200 OK with valid API key, got %d", resp.StatusCode)
	}

	var body map[string]string
	json.NewDecoder(resp.Body).Decode(&body)
	if body["message"] != "pong" {
		t.Errorf("Expected response message 'pong', got '%s'", body["message"])
	}
}
