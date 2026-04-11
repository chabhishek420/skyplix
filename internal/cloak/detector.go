package cloak

import (
	"context"
	"net"
	"net/http"
	"strings"
	"time"

	"github.com/skyplix/zai-tds/internal/model"
)

type Detector struct {
	vpnAPI  string
	timeout time.Duration
}

func NewDetector(vpnAPI string) *Detector {
	return &Detector{
		vpnAPI:  vpnAPI,
		timeout: 2 * time.Second,
	}
}

type FingerprintResult struct {
	IsBot       bool   `json:"is_bot"`
	Reason      string `json:"reason,omitempty"`
	Score       int    `json:"score"`
	Canvas      bool   `json:"canvas"`
	WebGL       bool   `json:"webgl"`
	Audio       bool   `json:"audio"`
	Timezone    bool   `json:"timezone"`
	Plugins     bool   `json:"plugins"`
	Languages   bool   `json:"languages"`
	WebRTC      bool   `json:"webrtc"`
	ScreenMatch bool   `json:"screen_match"`
}

func (d *Detector) CheckVPN(ip string) (bool, string) {
	if ip == "" {
		return false, ""
	}

	ctx, cancel := context.WithTimeout(context.Background(), d.timeout)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, "GET", d.vpnAPI+ip, nil)
	if err != nil {
		return false, ""
	}

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return false, ""
	}
	defer resp.Body.Close()

	if resp.StatusCode != 200 {
		return false, ""
	}

	buf := make([]byte, 10)
	n, _ := resp.Body.Read(buf)
	result := string(buf[:n])

	if result == "Y" || strings.Contains(result, "vpn") || strings.Contains(result, "tor") {
		return true, "vpn_tor"
	}

	return false, ""
}

func (d *Detector) CheckIPQuality(ip net.IP) (bool, string) {
	ipStr := ip.String()

	if isDatacenterIP(ipStr) {
		return true, "datacenter"
	}

	if vpn, reason := d.CheckVPN(ipStr); vpn {
		return true, reason
	}

	return false, ""
}

func isDatacenterIP(ip string) bool {
	datacenterRanges := []string{
		"35.192.0.0/12",  // Google Cloud
		"34.64.0.0/11",   // Google Cloud
		"8.8.0.0/16",     // Google DNS
		"52.0.0.0/8",     // AWS
		"54.0.0.0/8",     // AWS
		"13.52.0.0/16",   // AWS
		"13.54.0.0/15",   // AWS
		"13.56.0.0/15",   // AWS
		"13.58.0.0/15",   // AWS
		"13.208.0.0/12",  // AWS Asia
		"13.232.0.0/13",  // AWS Asia
		"13.228.0.0/14",  // AWS Asia
		"13.230.0.0/15",  // AWS Asia
		"13.236.0.0/14",  // AWS Asia
		"13.234.0.0/15",  // AWS Asia
		"3.0.0.0/8",      // AWS
		"18.0.0.0/8",     // AWS
		"52.0.0.0/8",     // AWS
		"54.0.0.0/8",     // AWS
		"57.0.0.0/8",     // AWS
		"99.0.0.0/8",     // AWS
		"100.24.0.0/14",  // AWS
		"107.20.0.0/14",  // AWS
		"108.128.0.0/13", // AWS
		"50.0.0.0/8",     // AWS
		"23.0.0.0/8",     // Cloudflare
		"104.16.0.0/13",  // Cloudflare
		"172.64.0.0/13",  // Cloudflare
		"131.0.0.0/8",    // Microsoft Azure
		"104.40.0.0/13",  // Microsoft Azure
		"13.64.0.0/11",   // Microsoft Azure
		"40.74.0.0/15",   // Microsoft Azure
		"40.80.0.0/12",   // Microsoft Azure
		"40.96.0.0/12",   // Microsoft Azure
		"40.112.0.0/13",  // Microsoft Azure
		"40.120.0.0/14",  // Microsoft Azure
		"40.124.0.0/16",  // Microsoft Azure
		"52.0.0.0/8",     // Microsoft Azure
		"13.64.0.0/11",   // DigitalOcean
		"64.227.0.0/16",  // DigitalOcean
		"128.199.0.0/16", // DigitalOcean
		"167.99.0.0/16",  // DigitalOcean
		"159.89.0.0/16",  // DigitalOcean
		"159.65.0.0/16",  // DigitalOcean
		"138.68.0.0/16",  // DigitalOcean
		"206.189.0.0/16", // DigitalOcean
		"134.209.0.0/16", // DigitalOcean
		"68.183.0.0/16",  // DigitalOcean
		"46.101.0.0/16",  // DigitalOcean
		"67.207.0.0/16",  // DigitalOcean
		"174.138.0.0/16", // DigitalOcean
		"165.22.0.0/16",  // DigitalOcean
		"165.227.0.0/16", // DigitalOcean
		"167.172.0.0/16", // DigitalOcean
		"170.39.0.0/16",  // DigitalOcean
		"172.104.0.0/15", // Linode
		"173.255.0.0/15", // Linode
		"45.33.0.0/16",   // Linode
		"45.56.0.0/15",   // Linode
		"45.79.0.0/16",   // Linode
		"45.33.32.0/19",  // Linode
		"50.116.0.0/15",  // Linode
		"69.164.0.0/16",  // Linode
		"74.207.0.0/16",  // Linode
		"96.126.0.0/15",  // Linode
		"97.107.0.0/16",  // Linode
		"109.73.0.0/16",  // InMotion
		"192.145.0.0/16", // InMotion
		"199.73.0.0/16",  // InMotion
		"198.46.0.0/16",  // InMotion
		"173.83.0.0/16",  // InMotion
		"205.234.0.0/16", // InMotion
		"31.220.0.0/18",  // Hostinger
		"185.136.0.0/19", // Hostinger
		"185.7.0.0/19",   // Hostinger
		"168.0.0/8",      // Vultr
		"45.32.0.0/15",   // Vultr
		"45.63.0.0/16",   // Vultr
		"45.76.0.0/15",   // Vultr
		"104.156.0.0/15", // Vultr
		"108.61.0.0/16",  // Vultr
		"139.180.0.0/16", // Vultr
		"149.28.0.0/16",  // Vultr
		"163.44.0.0/16",  // Vultr
		"170.39.0.0/16",  // Vultr
		"209.250.0.0/16", // Vultr
		"45.77.0.0/16",   // Vultr
		"45.78.0.0/15",   // Vultr
		"45.82.0.0/15",   // Vultr
		"64.176.0.0/14",  // Vultr
		"66.42.0.0/15",   // Vultr
		"66.55.0.0/16",   // Vultr
		"69.28.0.0/16",   // Vultr
		"70.32.0.0/16",   // Vultr
		"149.28.0.0/16",  // Vultr
		"192.248.0.0/16", // Vultr
		"200.0.0.0/8",    // Oracle Cloud
		"141.144.0.0/16", // Oracle Cloud
		"132.145.0.0/16", // Oracle Cloud
		"144.172.0.0/14", // Oracle Cloud
		"147.182.0.0/16", // Oracle Cloud
		"152.70.0.0/16",  // Oracle Cloud
		"158.101.0.0/16", // Oracle Cloud
		"158.140.0.0/16", // Oracle Cloud
		"158.160.0.0/16", // Oracle Cloud
		"161.129.0.0/16", // Oracle Cloud
		"164.0.0.0/8",    // Oracle Cloud
		"165.84.0.0/15",  // Oracle Cloud
		"168.138.0.0/16", // Oracle Cloud
		"192.29.0.0/16",  // Oracle Cloud
		"193.122.0.0/16", // Oracle Cloud
		"193.123.0.0/16", // Oracle Cloud
		"198.0.0.0/8",    // Oracle Cloud
		"205.204.0.0/16", // Oracle Cloud
		"129.0.0.0/8",    // Alibaba Cloud
		"39.0.0.0/8",     // Alibaba Cloud
		"42.0.0.0/8",     // Alibaba Cloud
		"47.0.0.0/8",     // Alibaba Cloud
		"106.0.0.0/8",    // Alibaba Cloud
		"119.0.0.0/8",    // Alibaba Cloud
		"120.0.0.0/8",    // Alibaba Cloud
		"121.0.0.0/8",    // Alibaba Cloud
		"139.0.0.0/8",    // Alibaba Cloud
		"140.0.0.0/8",    // Alibaba Cloud
		"202.0.0.0/8",    // Alibaba Cloud
		"203.0.0.0/8",    // Alibaba Cloud
		"10.0.0.0/8",     // RFC1918
		"172.16.0.0/12",  // RFC1918
		"192.168.0.0/16", // RFC1918
	}

	for _, cidr := range datacenterRanges {
		_, ipNet, err := net.ParseCIDR(cidr)
		if err != nil {
			continue
		}
		if ipNet.Contains(net.ParseIP(ip)) {
			return true
		}
	}

	return false
}

func (d *Detector) EvaluateFingerprint(fp FingerprintResult) (bool, string) {
	score := 0
	reasons := []string{}

	if fp.Canvas {
		score += 30
	} else {
		reasons = append(reasons, "no_canvas")
	}

	if fp.WebGL {
		score += 20
	} else {
		reasons = append(reasons, "no_webgl")
	}

	if fp.Audio {
		score += 15
	} else {
		reasons = append(reasons, "no_audio")
	}

	if fp.Timezone {
		score += 15
	} else {
		reasons = append(reasons, "tz_mismatch")
	}

	if fp.Plugins {
		score += 10
	} else {
		reasons = append(reasons, "no_plugins")
	}

	if fp.Languages {
		score += 5
	} else {
		reasons = append(reasons, "lang_mismatch")
	}

	if fp.ScreenMatch {
		score += 5
	} else {
		reasons = append(reasons, "screen_mismatch")
	}

	if score < 60 {
		isBot := true
		reason := "low_fingerprint_score"
		if len(reasons) > 0 {
			reason = reasons[0]
		}
		return isBot, reason
	}

	return false, ""
}

func EvaluateClick(rc *model.RawClick, detector *Detector) (bool, string) {
	if rc.IsBot {
		return true, rc.BotReason
	}

	if vpn, reason := detector.CheckIPQuality(rc.IP); vpn {
		return true, reason
	}

	return false, ""
}
