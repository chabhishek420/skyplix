package macro_test

import (
	"testing"

	"github.com/skyplix/zai-tds/internal/macro"
)

func TestGeneratePostbackURL(t *testing.T) {
	tests := []struct {
		name        string
		baseURL     string
		postbackKey string
		want        string
	}{
		{
			name:        "Standard URL and key",
			baseURL:     "https://tracker.com",
			postbackKey: "test-key",
			want:        "https://tracker.com/postback/test-key?sub_id={click_id}&payout={payout}&status={status}",
		},
		{
			name:        "URL with trailing slash",
			baseURL:     "https://tracker.com/",
			postbackKey: "test-key",
			want:        "https://tracker.com/postback/test-key?sub_id={click_id}&payout={payout}&status={status}",
		},
		{
			name:        "Empty baseURL",
			baseURL:     "",
			postbackKey: "test-key",
			want:        "https://your-tracker.com/postback/test-key?sub_id={click_id}&payout={payout}&status={status}",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := macro.GeneratePostbackURL(tt.baseURL, tt.postbackKey)
			if got != tt.want {
				t.Errorf("GeneratePostbackURL() = %v, want %v", got, tt.want)
			}
		})
	}
}

func TestReplacePostback(t *testing.T) {
	data := &macro.PostbackData{
		ClickToken: "CT123",
		Payout:     1050,
		Status:     "sale",
		ExternalID: "TX987",
		CampaignID: "CAMP-UUID",
		OfferID:    "OFFER-UUID",
		SubID1:     "sub1 val",
		SubID2:     "sub2/val",
	}

	tests := []struct {
		name     string
		template string
		data     *macro.PostbackData
		want     string
	}{
		{
			name:     "Replace all macros",
			template: "http://example.com?click={click_id}&p={payout}&s={status}&tx={external_id}&camp={campaign_id}&offer={offer_id}&s1={sub_id_1}&s2={sub_id_2}",
			data:     data,
			want:     "http://example.com?click=CT123&p=10.5000&s=sale&tx=TX987&camp=CAMP-UUID&offer=OFFER-UUID&s1=sub1+val&s2=sub2%2Fval",
		},
		{
			name:     "Alias {subid} replacement",
			template: "http://example.com?subid={subid}",
			data:     data,
			want:     "http://example.com?subid=CT123",
		},
		{
			name:     "Empty template",
			template: "",
			data:     data,
			want:     "",
		},
		{
			name:     "No macros in template",
			template: "http://example.com/no-macros",
			data:     data,
			want:     "http://example.com/no-macros",
		},
		{
			name:     "Empty values in data",
			template: "http://example.com?s3={sub_id_3}",
			data:     data,
			want:     "http://example.com?s3=",
		},
		{
			name:     "URL encoding",
			template: "http://example.com?status={status}&s1={sub_id_1}",
			data: &macro.PostbackData{
				Status: "on hold",
				SubID1: "special&char?",
			},
			want: "http://example.com?status=on+hold&s1=special%26char%3F",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := macro.ReplacePostback(tt.template, tt.data)
			if got != tt.want {
				t.Errorf("ReplacePostback() = %v, want %v", got, tt.want)
			}
		})
	}
}

