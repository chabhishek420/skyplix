package queue

import (
	"net"
	"testing"
	"time"

	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

func TestParseUUIDVal_ValidUUID(t *testing.T) {
	logger := zap.NewNop()
	w := &Writer{logger: logger}

	validUUID := "550e8400-e29b-41d4-a716-446655440000"
	fallback := uuid.Nil

	result := w.parseUUIDVal(validUUID, fallback)

	if result.String() != validUUID {
		t.Errorf("expected %s, got %s", validUUID, result.String())
	}
}

func TestParseUUIDVal_EmptyString(t *testing.T) {
	logger := zap.NewNop()
	w := &Writer{logger: logger}

	fallback := uuid.MustParse("550e8400-e29b-41d4-a716-446655440000")

	result := w.parseUUIDVal("", fallback)

	if result != fallback {
		t.Errorf("expected fallback %s, got %s", fallback, result)
	}
}

func TestParseUUIDVal_InvalidUUID(t *testing.T) {
	logger := zap.NewNop()
	w := &Writer{logger: logger}

	invalidUUID := "not-a-valid-uuid"
	fallback := uuid.Nil

	result := w.parseUUIDVal(invalidUUID, fallback)

	if result != fallback {
		t.Errorf("expected fallback %s, got %s", fallback, result)
	}
}

func TestParseIPv6_ValidIPv4(t *testing.T) {
	result := parseIPv6("192.168.1.1")

	if result == nil {
		t.Fatal("expected non-nil result")
	}
	if result.To4() == nil {
		t.Error("expected IPv4-mapped IPv6 address")
	}
}

func TestParseIPv6_ValidIPv6(t *testing.T) {
	result := parseIPv6("2001:0db8:85a3:0000:0000:8a2e:0370:7334")

	if result == nil {
		t.Fatal("expected non-nil result")
	}
	if result.To4() != nil {
		t.Error("expected pure IPv6 address")
	}
}

func TestParseIPv6_EmptyString(t *testing.T) {
	result := parseIPv6("")

	if !result.Equal(net.IPv6zero) {
		t.Errorf("expected IPv6zero, got %s", result)
	}
}

func TestParseIPv6_InvalidIP(t *testing.T) {
	result := parseIPv6("not-an-ip-address")

	if !result.Equal(net.IPv6zero) {
		t.Errorf("expected IPv6zero for invalid IP, got %s", result)
	}
}

func TestFixedString2_TwoChars(t *testing.T) {
	result := fixedString2("US")

	if result != "US" {
		t.Errorf("expected 'US', got %q", result)
	}
	if len(result) != 2 {
		t.Errorf("expected length 2, got %d", len(result))
	}
}

func TestFixedString2_Empty(t *testing.T) {
	result := fixedString2("")

	if result != "  " {
		t.Errorf("expected '  ', got %q", result)
	}
	if len(result) != 2 {
		t.Errorf("expected length 2, got %d", len(result))
	}
}

func TestFixedString2_TooLong(t *testing.T) {
	result := fixedString2("USA")

	if result != "US" {
		t.Errorf("expected 'US', got %q", result)
	}
	if len(result) != 2 {
		t.Errorf("expected length 2, got %d", len(result))
	}
}

func TestFromRawClick_Conversion(t *testing.T) {
	now := time.Now().UTC()
	testUUID := uuid.New()

	rc := &model.RawClick{
		CreatedAt:      now,
		CampaignID:     testUUID,
		CampaignAlias:  "test-campaign",
		StreamID:       testUUID,
		OfferID:        testUUID,
		LandingID:      testUUID,
		IP:             net.ParseIP("192.168.1.1"),
		CountryCode:    "US",
		City:           "San Francisco",
		ISP:            "Comcast",
		DeviceType:     "desktop",
		DeviceModel:    "Chrome",
		OS:             "macOS",
		OSVersion:      "14.0",
		Browser:        "Chrome",
		BrowserVersion: "120.0",
		UserAgent:      "Mozilla/5.0",
		Referrer:       "https://google.com",
		IsBot:          false,
		IsUniqueGlobal: true,
		SubID1:         "sub1",
		SubID2:         "sub2",
		Cost:           0.05,
		Payout:         0.10,
		ActionType:     "click",
		ClickToken:     "abc123def456",
	}

	record := FromRawClick(rc)

	if record.CampaignID != testUUID.String() {
		t.Errorf("CampaignID: expected %s, got %s", testUUID.String(), record.CampaignID)
	}
	if record.StreamID != testUUID.String() {
		t.Errorf("StreamID: expected %s, got %s", testUUID.String(), record.StreamID)
	}
	if record.OfferID != testUUID.String() {
		t.Errorf("OfferID: expected %s, got %s", testUUID.String(), record.OfferID)
	}
	if record.LandingID != testUUID.String() {
		t.Errorf("LandingID: expected %s, got %s", testUUID.String(), record.LandingID)
	}
	if record.CountryCode != "US" {
		t.Errorf("CountryCode: expected 'US', got %s", record.CountryCode)
	}
	if record.IsBot != 0 {
		t.Errorf("IsBot: expected 0, got %d", record.IsBot)
	}
	if record.IsUniqueGlobal != 1 {
		t.Errorf("IsUniqueGlobal: expected 1, got %d", record.IsUniqueGlobal)
	}
	if record.ClickToken != "abc123def456" {
		t.Errorf("ClickToken: expected 'abc123def456', got %s", record.ClickToken)
	}
}
