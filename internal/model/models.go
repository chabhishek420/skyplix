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
	RawQuery  string

	// --- Campaign routing ---
	CampaignAlias string
	CampaignID    uuid.UUID
	StreamID      uuid.UUID
	OfferID       uuid.UUID
	LandingID     uuid.UUID

	// --- Click token (generated in stage 13) ---
	ClickToken string

	// --- Geo (populated in stage 6 via GeoIP) ---
	CountryCode  string
	Region       string
	City         string
	ISP          string
	ASN          uint
	ASNOrg       string
	IsDatacenter bool

	// --- Device (populated in stage 6 via UA parser) ---
	DeviceType     string
	DeviceModel    string
	OS             string
	OSVersion      string
	Browser        string
	BrowserVersion string

	// --- Traffic ---
	Language string

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
	TrafficSourceID *uuid.UUID
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
	Filters       []StreamFilter
	DailyLimit    int64
	TotalLimit    int64
}

// StreamFilter represents a single filter condition for a stream.
type StreamFilter struct {
	Type    string                 `json:"type"`
	Payload map[string]interface{} `json:"payload"`
}

func (s Stream) GetWeight() int    { return s.Weight }
func (s Stream) GetID() uuid.UUID { return s.ID }

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

func (o Offer) GetWeight() int    { return 100 }
func (o Offer) GetID() uuid.UUID { return o.ID }

// Landing represents a landing page for Level 1 → Level 2 click linking.
type Landing struct {
	ID    uuid.UUID
	Name  string
	URL   string
	State string
}

func (l Landing) GetID() uuid.UUID { return l.ID }
func (l Landing) GetWeight() int    { return 100 }

// WeightedOffer pairs an offer with its rotation weight.
type WeightedOffer struct {
	Offer  Offer
	Weight int
}

func (wo WeightedOffer) GetWeight() int    { return wo.Weight }
func (wo WeightedOffer) GetID() uuid.UUID { return wo.Offer.ID }

// WeightedLanding pairs a landing with its rotation weight.
type WeightedLanding struct {
	Landing Landing
	Weight  int
}

func (wl WeightedLanding) GetWeight() int    { return wl.Weight }
func (wl WeightedLanding) GetID() uuid.UUID { return wl.Landing.ID }

// AffiliateNetwork represents an affiliate network entity.
type AffiliateNetwork struct {
	ID          uuid.UUID
	Name        string
	PostbackURL string
	State       string
}

// TrafficSource represents a traffic source entity.
type TrafficSource struct {
	ID          uuid.UUID
	Name        string
	PostbackURL string
	Params      map[string]string
	State       string
}

// Domain represents a campaign-to-domain binding.
type Domain struct {
	ID         uuid.UUID
	Domain     string
	CampaignID *uuid.UUID
	State      string
}

// User represents an administrative user.
type User struct {
	ID       uuid.UUID
	Login    string
	Role     string
	State    string
	ApiKey   string // Added in migration 005
}
