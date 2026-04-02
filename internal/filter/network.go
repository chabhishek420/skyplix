package filter

import (
	"net"
	"strings"

	"github.com/skyplix/zai-tds/internal/model"
)

type IpFilter struct{}
func (f *IpFilter) Type() string { return "Ip" }
func (f *IpFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIP(rc.IP, payload)
}

type Ipv6Filter struct{}
func (f *Ipv6Filter) Type() string { return "Ipv6" }
func (f *Ipv6Filter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIP(rc.IP, payload)
}

type IspFilter struct{}
func (f *IspFilter) Type() string { return "Isp" }
func (f *IspFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	return matchIncludeExclude(rc.ISP, payload)
}

type OperatorFilter struct{}
func (f *OperatorFilter) Type() string { return "Operator" }
func (f *OperatorFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	// Operator is usually ISP for mobile
	return matchIncludeExclude(rc.ISP, payload)
}

type ConnectionTypeFilter struct{}
func (f *ConnectionTypeFilter) Type() string { return "ConnectionType" }
func (f *ConnectionTypeFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	// Placeholder ConnectionType matching
	return true
}

type ProxyFilter struct{}
func (f *ProxyFilter) Type() string { return "Proxy" }
func (f *ProxyFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if val, ok := payload["is_proxy"].(bool); ok {
		return rc.IsProxy == val
	}
	return true
}

type IspBlacklistFilter struct{}
func (f *IspBlacklistFilter) Type() string { return "IspBlacklist" }
func (f *IspBlacklistFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	isps, ok := payload["isps"].([]interface{})
	if !ok || len(isps) == 0 {
		return true
	}

	target := strings.ToLower(rc.ISP + " " + rc.ASNOrg)
	for _, v := range isps {
		if s, ok := v.(string); ok && s != "" {
			if strings.Contains(target, strings.ToLower(s)) {
				return false // Blocked
			}
		}
	}
	return true
}

func matchIP(ip net.IP, payload map[string]interface{}) bool {
	if ip == nil {
		return false
	}

	if include, ok := payload["include"].([]interface{}); ok && len(include) > 0 {
		found := false
		for _, v := range include {
			if s, ok := v.(string); ok {
				if strings.Contains(s, "/") {
					_, cidr, _ := net.ParseCIDR(s)
					if cidr != nil && cidr.Contains(ip) {
						found = true
						break
					}
				} else if s == ip.String() {
					found = true
					break
				}
			}
		}
		if !found {
			return false
		}
	}

	if exclude, ok := payload["exclude"].([]interface{}); ok && len(exclude) > 0 {
		for _, v := range exclude {
			if s, ok := v.(string); ok {
				if strings.Contains(s, "/") {
					_, cidr, _ := net.ParseCIDR(s)
					if cidr != nil && cidr.Contains(ip) {
						return false
					}
				} else if s == ip.String() {
					return false
				}
			}
		}
	}

	return true
}
