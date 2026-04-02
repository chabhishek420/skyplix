package macro

import (
	"fmt"
	"net/url"
	"strings"
	"time"

	"github.com/skyplix/zai-tds/internal/model"
)

// Replace replaces all standard Keitaro macros in a URL with click/campaign data.
func Replace(targetURL string, click *model.RawClick, campaign *model.Campaign) string {
	if targetURL == "" {
		return ""
	}

	replacements := []string{
		"{click_id}", click.ClickToken,
		"{campaign_id}", campaign.ID.String(),
		"{campaign_name}", url.QueryEscape(campaign.Name),
		"{stream_id}", click.StreamID.String(),
		"{country}", click.CountryCode,
		"{city}", url.QueryEscape(click.City),
		"{region}", url.QueryEscape(click.Region),
		"{device}", click.DeviceType,
		"{os}", click.OS,
		"{os_version}", click.OSVersion,
		"{browser}", click.Browser,
		"{browser_version}", click.BrowserVersion,
		"{ip}", click.IP.String(),
		"{isp}", url.QueryEscape(click.ISP),
		"{user_agent}", url.QueryEscape(click.UserAgent),
		"{referrer}", url.QueryEscape(click.Referrer),
		"{sub_id_1}", url.QueryEscape(click.SubID1),
		"{sub_id_2}", url.QueryEscape(click.SubID2),
		"{sub_id_3}", url.QueryEscape(click.SubID3),
		"{sub_id_4}", url.QueryEscape(click.SubID4),
		"{sub_id_5}", url.QueryEscape(click.SubID5),
		"{cost}", fmt.Sprintf("%.4f", click.Cost),
		"{payout}", fmt.Sprintf("%.4f", click.Payout),
		"{timestamp}", fmt.Sprintf("%d", time.Now().Unix()),
		"{random}", randomHex(8),
	}

	result := targetURL
	for i := 0; i < len(replacements); i += 2 {
		if strings.Contains(result, replacements[i]) {
			result = strings.ReplaceAll(result, replacements[i], replacements[i+1])
		}
	}
	return result
}

func randomHex(n int) string {
	// Simple random hex generator
	return fmt.Sprintf("%x", time.Now().UnixNano())[:n]
}
