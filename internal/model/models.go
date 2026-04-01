package model

import (
	"net"
	"time"

	"github.com/google/uuid"
)

// RawClick represents all data collected from a single click request.
// It is progressively populated as it flows through the pipeline stages.
// Mirrors Keitaro's Traffic/RawClick.php (~60 fields).
type RawClick struct {
	// --- Request metadata ---
	IP        net.IP
	UserAgent string
	Referrer  string

	// --- Campaign routing ---
	CampaignAlias string
	CampaignID    uuid.UUID
	StreamID      uuid.UUID
	OfferID       uuid.UUID
	LandingID     uuid.UUID

	// --- Click token (generated in stage 13) ---
	ClickToken string

	// --- Geo (populated in stage 6 via GeoIP) ---
	CountryCode string
	City        string
	ISP         string

	// --- Device (populated in stage 6 via UA parser) ---
	DeviceType     string
	DeviceModel    string
	OS             string
	OSVersion      string
	Browser        string
	BrowserVersion string

	// --- Bot detection (populated in stage 3) ---
	IsBot   bool
	IsProxy bool

	// --- Uniqueness flags (populated in stages 8+10) ---
	IsUniqueGlobal   bool
	IsUniqueCampaign bool
	IsUniqueStream   bool

	// --- Traffic source parameters ---
	SubID1 string
	SubID2 string
	SubID3 string
	SubID4 string
	SubID5 string

	// --- Cost model ---
	Cost   float64
	Payout float64

	// --- Action result ---
	ActionType string

	// --- Timing ---
	CreatedAt time.Time
}

// Campaign represents a traffic campaign entity.
// Mirrors Keitaro's Campaign model including 3-tier stream selection fields.
type Campaign struct {
	ID              uuid.UUID
	Alias           string
	Name            string
	Type            CampaignType // POSITION or WEIGHT
	BindVisitors    bool
	State           string
	DefaultStreamID *uuid.UUID
}

// CampaignType controls stream selection mode (POSITION = sequential, WEIGHT = weighted random).
type CampaignType string

const (
	CampaignTypePosition CampaignType = "POSITION"
	CampaignTypeWeight   CampaignType = "WEIGHT"
)

// Stream represents a routing stream within a campaign.
// Type determines its role in 3-tier selection (FORCED → REGULAR → DEFAULT).
type Stream struct {
	ID            uuid.UUID
	CampaignID    uuid.UUID
	Name          string
	Type          StreamType
	Position      int
	Weight        int
	State         string
	ActionType    string
	ActionPayload map[string]interface{}
	Filters       []interface{}
}

// StreamType controls the 3-tier selection hierarchy.
type StreamType string

const (
	StreamTypeRegular StreamType = "REGULAR"
	StreamTypeForced  StreamType = "FORCED"
	StreamTypeDefault StreamType = "DEFAULT"
)

// Offer represents a target affiliate offer.
type Offer struct {
	ID                uuid.UUID
	Name              string
	URL               string
	AffiliateNetworkID *uuid.UUID
	Payout            float64
	State             string
}

// Landing represents a landing page for Level 1 → Level 2 click linking.
type Landing struct {
	ID    uuid.UUID
	Name  string
	URL   string
	State string
}
