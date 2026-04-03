package macro

import (
	"fmt"
	"net/url"
	"strings"
)

// MacroDef represents a supported macro with its description.
type MacroDef struct {
	Name        string `json:"name"`
	Description string `json:"description"`
}

// PostbackMacros is a constant list of supported macros with descriptions.
var PostbackMacros = []MacroDef{
	{Name: "{click_id}", Description: "Click token / sub ID"},
	{Name: "{subid}", Description: "Alias for click_id"},
	{Name: "{payout}", Description: "Conversion payout amount"},
	{Name: "{status}", Description: "Conversion status (lead/sale/rejected/hold)"},
	{Name: "{external_id}", Description: "External transaction ID"},
	{Name: "{campaign_id}", Description: "Campaign UUID"},
	{Name: "{offer_id}", Description: "Offer UUID"},
	{Name: "{sub_id_1}", Description: "Traffic source sub ID 1"},
	{Name: "{sub_id_2}", Description: "Traffic source sub ID 2"},
	{Name: "{sub_id_3}", Description: "Traffic source sub ID 3"},
	{Name: "{sub_id_4}", Description: "Traffic source sub ID 4"},
	{Name: "{sub_id_5}", Description: "Traffic source sub ID 5"},
}

// PostbackData contains data used for postback macro replacement.
type PostbackData struct {
	ClickToken string
	Payout     float64
	Status     string
	ExternalID string
	CampaignID string
	OfferID    string
	SubID1     string
	SubID2     string
	SubID3     string
	SubID4     string
	SubID5     string
}

// GeneratePostbackURL takes the operator's tracker domain/base URL and postback key
// and returns a template URL with Keitaro-compatible macros.
func GeneratePostbackURL(baseURL string, postbackKey string) string {
	if baseURL == "" {
		baseURL = "https://your-tracker.com"
	}
	// Remove trailing slash if present
	baseURL = strings.TrimSuffix(baseURL, "/")

	return fmt.Sprintf("%s/postback/%s?sub_id={click_id}&payout={payout}&status={status}", baseURL, postbackKey)
}

// ReplacePostback replaces macros in a template string with actual values from PostbackData.
func ReplacePostback(template string, data *PostbackData) string {
	if template == "" {
		return ""
	}

	replacements := []string{
		"{click_id}", data.ClickToken,
		"{subid}", data.ClickToken,
		"{payout}", fmt.Sprintf("%.4f", data.Payout),
		"{status}", url.QueryEscape(data.Status),
		"{external_id}", url.QueryEscape(data.ExternalID),
		"{campaign_id}", url.QueryEscape(data.CampaignID),
		"{offer_id}", url.QueryEscape(data.OfferID),
		"{sub_id_1}", url.QueryEscape(data.SubID1),
		"{sub_id_2}", url.QueryEscape(data.SubID2),
		"{sub_id_3}", url.QueryEscape(data.SubID3),
		"{sub_id_4}", url.QueryEscape(data.SubID4),
		"{sub_id_5}", url.QueryEscape(data.SubID5),
	}

	result := template
	for i := 0; i < len(replacements); i += 2 {
		val := replacements[i+1]
		// If value is empty, we replace it with an empty string as per requirements
		result = strings.ReplaceAll(result, replacements[i], val)
	}

	return result
}
