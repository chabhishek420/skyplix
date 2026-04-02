package integration

import (
	"bytes"
	"encoding/json"
	"net/http"
	"testing"

	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/model"
)

func TestAdminCampaignCRUD(t *testing.T) {
	adminURL := getEnv("ADMIN_API_BASE_URL", "http://localhost:8080/api/v1/admin")
	adminKey := getEnv("ADMIN_API_KEY", "test-secret-key")

	client := &http.Client{}

	// 1. Create a campaign
	campaign := model.Campaign{
		Alias: "test-admin-campaign",
		Name:  "Test Admin Campaign",
		Type:  model.CampaignTypePosition,
		State: "active",
	}
	body, _ := json.Marshal(campaign)
	req, _ := http.NewRequest("POST", adminURL+"/campaigns", bytes.NewBuffer(body))
	req.Header.Set("X-API-Key", adminKey)
	req.Header.Set("Content-Type", "application/json")
	resp, err := client.Do(req)
	if err != nil {
		t.Fatalf("POST /campaigns failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusCreated {
		t.Fatalf("Expected 201 Created, got %d", resp.StatusCode)
	}

	var created model.Campaign
	json.NewDecoder(resp.Body).Decode(&created)
	if created.ID == uuid.Nil {
		t.Error("Expected created campaign ID to be set")
	}

	// 2. List campaigns
	req, _ = http.NewRequest("GET", adminURL+"/campaigns", nil)
	req.Header.Set("X-API-Key", adminKey)
	resp, err = client.Do(req)
	if err != nil {
		t.Fatalf("GET /campaigns failed: %v", err)
	}
	defer resp.Body.Close()

	var list []model.Campaign
	json.NewDecoder(resp.Body).Decode(&list)
	found := false
	for _, c := range list {
		if c.ID == created.ID {
			found = true
			break
		}
	}
	if !found {
		t.Error("Created campaign not found in list")
	}

	// 3. Get campaign
	req, _ = http.NewRequest("GET", adminURL+"/campaigns/"+created.ID.String(), nil)
	req.Header.Set("X-API-Key", adminKey)
	resp, err = client.Do(req)
	if err != nil {
		t.Fatalf("GET /campaigns/{id} failed: %v", err)
	}
	defer resp.Body.Close()

	var fetched model.Campaign
	json.NewDecoder(resp.Body).Decode(&fetched)
	if fetched.Alias != "test-admin-campaign" {
		t.Errorf("Expected alias 'test-admin-campaign', got '%s'", fetched.Alias)
	}

	// 4. Update campaign
	fetched.Name = "Updated Admin Campaign"
	body, _ = json.Marshal(fetched)
	req, _ = http.NewRequest("PUT", adminURL+"/campaigns/"+created.ID.String(), bytes.NewBuffer(body))
	req.Header.Set("X-API-Key", adminKey)
	req.Header.Set("Content-Type", "application/json")
	resp, err = client.Do(req)
	if err != nil {
		t.Fatalf("PUT /campaigns/{id} failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		t.Errorf("Expected 200 OK, got %d", resp.StatusCode)
	}

	// 5. Delete campaign
	req, _ = http.NewRequest("DELETE", adminURL+"/campaigns/"+created.ID.String(), nil)
	req.Header.Set("X-API-Key", adminKey)
	resp, err = client.Do(req)
	if err != nil {
		t.Fatalf("DELETE /campaigns/{id} failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusNoContent {
		t.Errorf("Expected 204 No Content, got %d", resp.StatusCode)
	}
}
