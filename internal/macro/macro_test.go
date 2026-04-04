package macro

import (
	"strings"
	"testing"

	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/model"
)

func TestReplaceMacros(t *testing.T) {
	campaignID := uuid.New()
	streamID := uuid.New()

	campaign := &model.Campaign{
		ID:   campaignID,
		Name: "Test Campaign",
	}

	click := &model.RawClick{
		ClickToken:     "test_token",
		StreamID:       streamID,
		VisitorCode:    "vc_123",
		ConnectionType: "wifi",
		Carrier:        "Verizon",
		Brand:          "Apple",
		DeviceModel:    "iPhone 13",
		IsBot:          false,
		IsUniqueGlobal: true,
		Keyword:        "buy now",
		ExternalID:     "ext_890",
		Source:         "google",
		ExtraParam1:    "ep1",
		ExtraParam10:   "ep10",
		Cost:           150,
	}

	targetURL := "https://example.com/?vc={visitor_code}&ct={connection_type}&brand={brand}&model={model}&bot={is_bot}&unique={is_unique}&kw={keyword}&kw8={keyword_utf8}&ext={external_id}&src={source}&e1={extra_param_1}&e10={extra_param_10}&cost={cost}"

	result := Replace(targetURL, click, campaign)

	expectedParts := []string{
		"vc=vc_123",
		"ct=wifi",
		"brand=Apple",
		"model=iPhone+13",
		"bot=false",
		"unique=true",
		"kw=buy+now",
		"kw8=buy+now",
		"ext=ext_890",
		"src=google",
		"e1=ep1",
		"e10=ep10",
		"cost=1.5000",
	}

	for _, part := range expectedParts {
		if !strings.Contains(result, part) {
			t.Errorf("Expected result to contain %q, but got %q", part, result)
		}
	}
}
