package device

import (
	ua "github.com/mileusna/useragent"
)

// Result holds device/browser/OS detection results for a single User-Agent.
type Result struct {
	DeviceType     string // desktop, mobile, tablet, bot
	DeviceModel    string // empty in Phase 1 (robicode/device-detector in Phase 2)
	Browser        string
	BrowserVersion string
	OS             string
	OSVersion      string
}

// Detector wraps the UA parsing library.
// Phase 1 uses mileusna/useragent (pure Go, no CGo).
// Phase 2 evaluates robicode/device-detector for DeviceModel/Brand accuracy.
type Detector struct{}

// New creates a Detector. No initialization needed for mileusna/useragent.
func New() *Detector {
	return &Detector{}
}

// Parse extracts device, browser, and OS information from the User-Agent string.
func (d *Detector) Parse(userAgent string) Result {
	if userAgent == "" {
		return Result{DeviceType: "unknown"}
	}

	parsed := ua.Parse(userAgent)

	result := Result{
		Browser:        parsed.Name,
		BrowserVersion: parsed.Version,
		OS:             parsed.OS,
		OSVersion:      parsed.OSVersion,
	}

	// Map mileusna device flags to Keitaro-compatible device type strings
	switch {
	case parsed.Bot:
		result.DeviceType = "bot"
	case parsed.Mobile:
		result.DeviceType = "mobile"
	case parsed.Tablet:
		result.DeviceType = "tablet"
	case parsed.Desktop:
		result.DeviceType = "desktop"
	default:
		result.DeviceType = "unknown"
	}

	// DeviceModel: empty in Phase 1 (requires robicode/device-detector with PCRE)
	result.DeviceModel = ""

	return result
}
