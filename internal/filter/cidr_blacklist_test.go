package filter

import (
	"os"
	"path/filepath"
	"testing"
)

func TestCIDRFilter(t *testing.T) {
	// Create a temporary file with some test CIDRs
	tempDir := t.TempDir()
	filepath := filepath.Join(tempDir, "bots.txt")

	content := `
# A comment
1.2.3.0/24
192.168.1.100/32
10.0.0.0/8
2001:db8::/32
invalid_ip
172.16.0.1
`
	if err := os.WriteFile(filepath, []byte(content), 0644); err != nil {
		t.Fatalf("Failed to write temp file: %v", err)
	}

	filter, err := NewCIDRFilterFromFile(filepath)
	if err != nil {
		t.Fatalf("Failed to create filter: %v", err)
	}

	tests := []struct {
		ip       string
		expected bool
	}{
		{"1.2.3.10", true},
		{"1.2.3.255", true},
		{"1.2.4.1", false},
		{"192.168.1.100", true},
		{"192.168.1.101", false},
		{"10.255.255.255", true},
		{"11.0.0.1", false},
		{"2001:db8::1", true},
		{"2001:db9::1", false},
		{"172.16.0.1", true}, // no subnet mask should default to /32
		{"172.16.0.2", false},
	}

	for _, tc := range tests {
		if got := filter.Contains(tc.ip); got != tc.expected {
			t.Errorf("IP %s: expected %v, got %v", tc.ip, tc.expected, got)
		}
	}
}

func BenchmarkCIDRFilter(b *testing.B) {
	// Create a temporary file with some test CIDRs
	tempDir := b.TempDir()
	filePath := filepath.Join(tempDir, "bots.txt")

	content := `1.2.3.0/24
192.168.1.100/32
10.0.0.0/8
2001:db8::/32`
	if err := os.WriteFile(filePath, []byte(content), 0644); err != nil {
		b.Fatalf("Failed to write temp file: %v", err)
	}

	filter, err := NewCIDRFilterFromFile(filePath)
	if err != nil {
		b.Fatalf("Failed to create filter: %v", err)
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		filter.Contains("10.12.34.56") // Should hit 10.0.0.0/8
	}
}
