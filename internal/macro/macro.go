package macro

import (
	"fmt"
	"math/rand/v2"
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

	subID := click.SubID
	if subID == "" {
		subID = click.ClickToken
	}

	replacements := []string{
		"{click_id}", click.ClickToken,
		"{subid}", subID, // Keitaro alias
		"{tid}", subID, // Keitaro alias
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
		"{visitor_code}", url.QueryEscape(click.VisitorCode),
		"{connection_type}", url.QueryEscape(click.ConnectionType),
		"{carrier}", url.QueryEscape(click.Carrier),
		"{operator}", url.QueryEscape(click.Carrier), // Keitaro alias
		"{brand}", url.QueryEscape(click.Brand),
		"{model}", url.QueryEscape(click.DeviceModel),
		"{is_bot}", fmt.Sprintf("%t", click.IsBot),
		"{is_unique}", fmt.Sprintf("%t", click.IsUniqueGlobal),
		"{keyword}", url.QueryEscape(click.Keyword),
		"{keyword_utf8}", url.QueryEscape(click.Keyword),
		"{external_id}", url.QueryEscape(click.ExternalID),
		"{source}", url.QueryEscape(click.Source),
		"{extra_param_1}", url.QueryEscape(click.ExtraParam1),
		"{extra_param_2}", url.QueryEscape(click.ExtraParam2),
		"{extra_param_3}", url.QueryEscape(click.ExtraParam3),
		"{extra_param_4}", url.QueryEscape(click.ExtraParam4),
		"{extra_param_5}", url.QueryEscape(click.ExtraParam5),
		"{extra_param_6}", url.QueryEscape(click.ExtraParam6),
		"{extra_param_7}", url.QueryEscape(click.ExtraParam7),
		"{extra_param_8}", url.QueryEscape(click.ExtraParam8),
		"{extra_param_9}", url.QueryEscape(click.ExtraParam9),
		"{extra_param_10}", url.QueryEscape(click.ExtraParam10),
		"{cost}", fmt.Sprintf("%.4f", click.Cost),
		"{payout}", fmt.Sprintf("%.4f", click.Payout),
		"{timestamp}", fmt.Sprintf("%d", time.Now().Unix()),
		"{date}", time.Now().Format("2006-01-02"), // Keitaro macro
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
	const charset = "0123456789abcdef"
	b := make([]byte, n)
	for i := range b {
		b[i] = charset[rand.IntN(len(charset))]
	}
	return string(b)
}
