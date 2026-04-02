package botdb

import (
	"encoding/binary"
	"fmt"
	"net"
	"sort"
	"strings"
	"sync"
)

// IPRange represents a closed interval [Min, Max] of IPv4 addresses.
type IPRange struct {
	Min uint32
	Max uint32
	Raw string
}

// Store is a thread-safe container for sorted, non-overlapping IP ranges.
type Store struct {
	mu     sync.RWMutex
	ranges []IPRange
}

// New creates a new IP range store.
func New() *Store {
	return &Store{
		ranges: make([]IPRange, 0),
	}
}

// Contains checks if the given IP address is within any of the stored ranges.
// It uses binary search for O(log n) lookup performance.
func (s *Store) Contains(ip net.IP) bool {
	ip4 := ip.To4()
	if ip4 == nil {
		return false
	}
	ipUint := binary.BigEndian.Uint32(ip4)

	s.mu.RLock()
	defer s.mu.RUnlock()

	if len(s.ranges) == 0 {
		return false
	}

	// Use binary search to find the first range where Min > ipUint.
	// The range that could contain ipUint is the one immediately before it.
	idx := sort.Search(len(s.ranges), func(i int) bool {
		return s.ranges[i].Min > ipUint
	})

	if idx > 0 {
		if s.ranges[idx-1].Max >= ipUint {
			return true
		}
	}

	return false
}

// Add parses the input string (single IP, CIDR, or range) and adds the ranges to the store.
// Overlapping intervals are merged automatically.
func (s *Store) Add(input string) error {
	newRanges, err := s.parseInput(input)
	if err != nil {
		return err
	}

	if len(newRanges) == 0 {
		return nil
	}

	s.mu.Lock()
	defer s.mu.Unlock()

	s.ranges = append(s.ranges, newRanges...)
	s.ranges = s.mergeOverlapping(s.ranges)
	return nil
}

// Exclude parses the input string and removes the matching ranges from the store.
func (s *Store) Exclude(input string) error {
	excludeRanges, err := s.parseInput(input)
	if err != nil {
		return err
	}

	if len(excludeRanges) == 0 {
		return nil
	}

	s.mu.Lock()
	defer s.mu.Unlock()

	var result []IPRange
	for _, src := range s.ranges {
		result = append(result, s.cropRanges(src, excludeRanges)...)
	}
	s.ranges = s.mergeOverlapping(result)
	return nil
}

// Replace clears the store and adds the new ranges.
func (s *Store) Replace(input string) error {
	newRanges, err := s.parseInput(input)
	if err != nil {
		return err
	}

	s.mu.Lock()
	defer s.mu.Unlock()

	s.ranges = s.mergeOverlapping(newRanges)
	return nil
}

// Clear removes all ranges from the store.
func (s *Store) Clear() {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.ranges = make([]IPRange, 0)
}

// List returns a snapshot of all sorted ranges in the store.
func (s *Store) List() []IPRange {
	s.mu.RLock()
	defer s.mu.RUnlock()
	res := make([]IPRange, len(s.ranges))
	copy(res, s.ranges)
	return res
}

// Count returns the number of non-overlapping ranges in the store.
func (s *Store) Count() int {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return len(s.ranges)
}

// --- Internal Helpers ---

func (s *Store) parseInput(content string) ([]IPRange, error) {
	var res []IPRange
	// Split by newline and comma
	lines := strings.FieldsFunc(content, func(r rune) bool {
		return r == '\n' || r == '\r' || r == ','
	})

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" {
			continue
		}
		entry, err := s.parseEntry(line)
		if err != nil {
			// Skip invalid entries or return error?
			// The task says "return errors gracefully", so we return for now.
			return nil, err
		}
		// If parseEntry returned a zero range (e.g. IPv6), skip it.
		if entry.Min == 0 && entry.Max == 0 && entry.Raw == "" {
			continue
		}
		res = append(res, entry)
	}
	return res, nil
}

func (s *Store) parseEntry(entry string) (IPRange, error) {
	if strings.Contains(entry, "/") {
		return s.parseCIDR(entry)
	}
	if strings.Contains(entry, "-") {
		return s.parseRange(entry)
	}
	return s.parseSingle(entry)
}

func (s *Store) parseCIDR(cidr string) (IPRange, error) {
	_, ipnet, err := net.ParseCIDR(cidr)
	if err != nil {
		return IPRange{}, fmt.Errorf("invalid CIDR %q: %w", cidr, err)
	}
	if ipnet.IP.To4() == nil {
		return IPRange{}, nil // Skip IPv6
	}

	min := binary.BigEndian.Uint32(ipnet.IP.To4())
	// Calculate max IP from mask
	mask := binary.BigEndian.Uint32(ipnet.Mask)
	max := min | (^mask)

	return IPRange{Min: min, Max: max, Raw: cidr}, nil
}

func (s *Store) parseRange(rang string) (IPRange, error) {
	parts := strings.Split(rang, "-")
	if len(parts) != 2 {
		return IPRange{}, fmt.Errorf("invalid range format %q", rang)
	}
	ipStartStr := strings.TrimSpace(parts[0])
	ipEndStr := strings.TrimSpace(parts[1])

	ipStart := net.ParseIP(ipStartStr)
	ipEnd := net.ParseIP(ipEndStr)

	if ipStart == nil || ipEnd == nil {
		return IPRange{}, fmt.Errorf("invalid IP in range %q", rang)
	}

	s4 := ipStart.To4()
	e4 := ipEnd.To4()
	if s4 == nil || e4 == nil {
		return IPRange{}, nil // Skip IPv6
	}

	min := binary.BigEndian.Uint32(s4)
	max := binary.BigEndian.Uint32(e4)

	if min > max {
		min, max = max, min
	}

	return IPRange{Min: min, Max: max, Raw: rang}, nil
}

func (s *Store) parseSingle(ipStr string) (IPRange, error) {
	ip := net.ParseIP(ipStr)
	if ip == nil {
		return IPRange{}, fmt.Errorf("invalid IP %q", ipStr)
	}
	ip4 := ip.To4()
	if ip4 == nil {
		return IPRange{}, nil // Skip IPv6
	}

	val := binary.BigEndian.Uint32(ip4)
	return IPRange{Min: val, Max: val, Raw: ipStr}, nil
}

func (s *Store) mergeOverlapping(ranges []IPRange) []IPRange {
	if len(ranges) == 0 {
		return ranges
	}

	// Sort by Min
	sort.Slice(ranges, func(i, j int) bool {
		return ranges[i].Min < ranges[j].Min
	})

	merged := make([]IPRange, 0, len(ranges))
	current := ranges[0]

	for i := 1; i < len(ranges); i++ {
		// If current range overlaps or is adjacent to next range
		// (min(next) <= max(current) + 1)
		if ranges[i].Min <= current.Max || (current.Max < 0xFFFFFFFF && ranges[i].Min == current.Max+1) {
			if ranges[i].Max > current.Max {
				current.Max = ranges[i].Max
				current.Raw = "" // Combined range loses its specific raw value
			}
		} else {
			merged = append(merged, current)
			current = ranges[i]
		}
	}
	merged = append(merged, current)
	return merged
}

func (s *Store) cropRanges(src IPRange, excludes []IPRange) []IPRange {
	result := []IPRange{src}

	for _, ex := range excludes {
		var nextResult []IPRange
		for _, r := range result {
			// If no overlap
			if ex.Max < r.Min || ex.Min > r.Max {
				nextResult = append(nextResult, r)
				continue
			}

			// Partial or full overlap
			// 1. Left part: r.Min < ex.Min
			if r.Min < ex.Min {
				nextResult = append(nextResult, IPRange{Min: r.Min, Max: ex.Min - 1, Raw: r.Raw})
			}
			// 2. Right part: r.Max > ex.Max
			if r.Max > ex.Max {
				nextResult = append(nextResult, IPRange{Min: ex.Max + 1, Max: r.Max, Raw: r.Raw})
			}
			// Inner part (ex.Min to ex.Max) is removed
		}
		result = nextResult
	}

	return result
}
