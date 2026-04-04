package filter

import (
	"bufio"
	"net/netip"
	"os"
	"strings"

	"go4.org/netipx"
)

// CIDRFilter holds a list of CIDRs and allows fast checking if an IP is present
type CIDRFilter struct {
	ipSet *netipx.IPSet
}

// NewCIDRFilterFromFile loads CIDRs from a text file, where each line is a CIDR (e.g. 1.2.3.4/32 or 2001:db8::/32).
func NewCIDRFilterFromFile(filepath string) (*CIDRFilter, error) {
	file, err := os.Open(filepath)
	if err != nil {
		return nil, err
	}
	defer file.Close()

	var builder netipx.IPSetBuilder

	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		if line == "" || strings.HasPrefix(line, "#") {
			continue
		}

		// Check if it has a subnet mask, if not, append /32 or /128
		if !strings.Contains(line, "/") {
			if strings.Contains(line, ":") {
				line += "/128"
			} else {
				line += "/32"
			}
		}

		prefix, err := netip.ParsePrefix(line)
		if err != nil {
			// Skip invalid lines
			continue
		}
		builder.AddPrefix(prefix)
	}

	if err := scanner.Err(); err != nil {
		return nil, err
	}

	ipSet, err := builder.IPSet()
	if err != nil {
		return nil, err
	}

	return &CIDRFilter{
		ipSet: ipSet,
	}, nil
}

// Contains checks if the given IP address is in any of the configured CIDRs.
func (f *CIDRFilter) Contains(ipStr string) bool {
	if f.ipSet == nil {
		return false
	}
	ip, err := netip.ParseAddr(ipStr)
	if err != nil {
		return false
	}
	return f.ipSet.Contains(ip)
}

// ContainsIP checks if the given netip.Addr is in any of the configured CIDRs.
func (f *CIDRFilter) ContainsIP(ip netip.Addr) bool {
	if f.ipSet == nil {
		return false
	}
	return f.ipSet.Contains(ip)
}
