package botdb

import (
	"net"
	"testing"
)

func TestContains_SingleIP(t *testing.T) {
	s := New()
	if err := s.Add("1.2.3.4"); err != nil {
		t.Fatalf("Add failed: %v", err)
	}

	if !s.Contains(net.ParseIP("1.2.3.4")) {
		t.Error("Expected 1.2.3.4 to be found")
	}
	if s.Contains(net.ParseIP("1.2.3.5")) {
		t.Error("Expected 1.2.3.5 not to be found")
	}
}

func TestContains_CIDR(t *testing.T) {
	s := New()
	if err := s.Add("192.168.1.0/24"); err != nil {
		t.Fatalf("Add failed: %v", err)
	}

	if !s.Contains(net.ParseIP("192.168.1.1")) {
		t.Error("Expected 192.168.1.1 to be found")
	}
	if !s.Contains(net.ParseIP("192.168.1.255")) {
		t.Error("Expected 192.168.1.255 to be found")
	}
	if s.Contains(net.ParseIP("192.168.2.1")) {
		t.Error("Expected 192.168.2.1 not to be found")
	}
}

func TestContains_Range(t *testing.T) {
	s := New()
	if err := s.Add("10.0.0.1-10.0.0.100"); err != nil {
		t.Fatalf("Add failed: %v", err)
	}

	if !s.Contains(net.ParseIP("10.0.0.50")) {
		t.Error("Expected 10.0.0.50 to be found")
	}
	if s.Contains(net.ParseIP("10.0.0.101")) {
		t.Error("Expected 10.0.0.101 not to be found")
	}
}

func TestContains_MultiInput(t *testing.T) {
	s := New()
	input := "1.1.1.1\n2.2.2.0/24\n3.3.3.1-3.3.3.10"
	if err := s.Add(input); err != nil {
		t.Fatalf("Add failed: %v", err)
	}

	if !s.Contains(net.ParseIP("1.1.1.1")) {
		t.Error("Expected 1.1.1.1 to be found")
	}
	if !s.Contains(net.ParseIP("2.2.2.100")) {
		t.Error("Expected 2.2.2.100 to be found")
	}
	if !s.Contains(net.ParseIP("3.3.3.5")) {
		t.Error("Expected 3.3.3.5 to be found")
	}
}

func TestMergeOverlapping(t *testing.T) {
	s := New()
	// Add first range
	if err := s.Add("10.0.0.0-10.0.0.50"); err != nil {
		t.Fatalf("Add failed: %v", err)
	}
	// Add overlapping range
	if err := s.Add("10.0.0.40-10.0.0.100"); err != nil {
		t.Fatalf("Add failed: %v", err)
	}

	if s.Count() != 1 {
		t.Fatalf("Expected 1 range after merge, got %d", s.Count())
	}
	if !s.Contains(net.ParseIP("10.0.0.75")) {
		t.Error("Expected 10.0.0.75 to be found (merged)")
	}
}

func TestExclude(t *testing.T) {
	s := New()
	if err := s.Add("10.0.0.0/8"); err != nil {
		t.Fatalf("Add failed: %v", err)
	}
	if err := s.Exclude("10.0.0.0/24"); err != nil {
		t.Fatalf("Exclude failed: %v", err)
	}

	if s.Contains(net.ParseIP("10.0.0.1")) {
		t.Error("Expected 10.0.0.1 to be excluded")
	}
	if !s.Contains(net.ParseIP("10.0.1.1")) {
		t.Error("Expected 10.0.1.1 to be present")
	}
}

func TestReplace(t *testing.T) {
	s := New()
	if err := s.Add("1.1.1.0/24"); err != nil {
		t.Fatalf("Add failed: %v", err)
	}
	if err := s.Replace("2.2.2.0/24"); err != nil {
		t.Fatalf("Replace failed: %v", err)
	}

	if s.Contains(net.ParseIP("1.1.1.1")) {
		t.Error("Expected 1.1.1.1 to be removed")
	}
	if !s.Contains(net.ParseIP("2.2.2.1")) {
		t.Error("Expected 2.2.2.1 to be present")
	}
}

func TestClear(t *testing.T) {
	s := New()
	if err := s.Add("1.1.1.1, 2.2.2.2"); err != nil {
		t.Fatalf("Add failed: %v", err)
	}
	s.Clear()
	if s.Count() != 0 {
		t.Errorf("Expected Count 0, got %d", s.Count())
	}
	if s.Contains(net.ParseIP("1.1.1.1")) {
		t.Error("Expected store to be empty")
	}
}

func TestInvalidInput(t *testing.T) {
	s := New()
	if err := s.Add("invalid-ip"); err == nil {
		t.Error("Expected error for invalid input")
	}
}

func TestEmptyStore(t *testing.T) {
	s := New()
	if s.Contains(net.ParseIP("1.2.3.4")) {
		t.Error("Empty store should not contain anything")
	}
}

func TestIPv6Ignored(t *testing.T) {
	s := New()
	// Should not error, just ignore
	if err := s.Add("2001:db8::/32"); err != nil {
		t.Fatalf("Unexpected error for IPv6: %v", err)
	}
	if s.Count() != 0 {
		t.Errorf("Expected Count 0 for IPv6-only addition, got %d", s.Count())
	}
}
