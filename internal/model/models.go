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
	IsBot     bool
	BotReason string
	IsProxy   bool

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

	// --- Extended Macros (Phase 19) ---
	Keyword        string
	VisitorCode    string
	ConnectionType string
	Carrier        string
	Brand          string
	ExternalID     string
	Source         string
	ExtraParam1    string
	ExtraParam2    string
	ExtraParam3    string
	ExtraParam4    string
	ExtraParam5    string
	ExtraParam6    string
	ExtraParam7    string
	ExtraParam8    string
	ExtraParam9    string
	ExtraParam10   string

	// --- Cost model ---
	Cost   int64
	Payout int64

	// --- Phase 9: TLS Fingerprinting ---
	JA3      string
	JA4      string
	TLSHost  string

	// --- Action result ---
	ActionType string

	// --- Timing ---
	CreatedAt time.Time
}

// Workspace represents a tenant/team isolation unit.
type Workspace struct {
	ID        uuid.UUID `json:"id"`
	Name      string    `json:"name"`
	OwnerID   uuid.UUID `json:"owner_id"`
	State     string    `json:"state"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

// CampaignGroup represents a folder or grouping for campaigns.
type CampaignGroup struct {
	ID          uuid.UUID `json:"id"`
	WorkspaceID uuid.UUID `json:"workspace_id"`
	Name        string    `json:"name"`
	Notes       *string   `json:"notes"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`
}

// Campaign represents a traffic campaign entity.
// Mirrors Keitaro's Campaign model including 3-tier stream selection fields.
type Campaign struct {
	ID                     uuid.UUID      `json:"id"`
	WorkspaceID            uuid.UUID      `json:"workspace_id"`
	GroupID                *uuid.UUID     `json:"group_id"`
	Alias                  string         `json:"alias"`
	Name                   string         `json:"name"`
	Type                   CampaignType   `json:"type"` // POSITION or WEIGHT
	BindVisitors           bool           `json:"bind_visitors"`
	IsOptimizationEnabled  bool           `json:"is_optimization_enabled"`
	OptimizationMetric     string         `json:"optimization_metric"` // 'CR' or 'EPC'
	OptimizationPeriodHours uint           `json:"optimization_period_hours"`
	CostModel              string         `json:"cost_model"` // CPC, CPM, CPA, RevShare
	CostValue              int64          `json:"cost_value"`
	State                  string         `json:"state"`
	TrafficSourceID        *uuid.UUID     `json:"traffic_source_id"`
	DefaultStreamID        *uuid.UUID     `json:"default_stream_id"`
	Notes                  *string        `json:"notes"`
	Tags                   []string       `json:"tags"`
	CreatedAt              time.Time      `json:"created_at"`
	UpdatedAt              time.Time      `json:"updated_at"`
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
	ID            uuid.UUID      `json:"id"`
	WorkspaceID    uuid.UUID      `json:"workspace_id"`
	CampaignID    uuid.UUID      `json:"campaign_id"`
	Name          string         `json:"name"`
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
	ID                 uuid.UUID  `json:"id"`
	WorkspaceID        uuid.UUID  `json:"workspace_id"`
	Name               string     `json:"name"`
	URL                string     `json:"url"`
	AffiliateNetworkID *uuid.UUID `json:"affiliate_network_id"`
	Payout             int64      `json:"payout"`
	DailyCap           int        `json:"daily_cap"`
	State              string     `json:"state"`
	Notes              *string    `json:"notes"`
	CreatedAt          time.Time  `json:"created_at"`
	UpdatedAt          time.Time  `json:"updated_at"`
}

func (o Offer) GetWeight() int    { return 100 }
func (o Offer) GetID() uuid.UUID { return o.ID }

// Landing represents a landing page for Level 1 → Level 2 click linking.
type Landing struct {
	ID          uuid.UUID `json:"id"`
	WorkspaceID uuid.UUID `json:"workspace_id"`
	Name        string    `json:"name"`
	URL         string    `json:"url"`
	State       string    `json:"state"`
	Notes       *string   `json:"notes"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`
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
	ID          uuid.UUID `json:"id"`
	WorkspaceID uuid.UUID `json:"workspace_id"`
	Name        string    `json:"name"`
	PostbackURL string    `json:"postback_url"`
	State       string    `json:"state"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`
}

// TrafficSource represents a traffic source entity.
type TrafficSource struct {
	ID          uuid.UUID         `json:"id"`
	WorkspaceID uuid.UUID         `json:"workspace_id"`
	Name        string            `json:"name"`
	PostbackURL string            `json:"postback_url"`
	Params      map[string]string `json:"params"`
	State       string            `json:"state"`
	CreatedAt   time.Time         `json:"created_at"`
	UpdatedAt   time.Time         `json:"updated_at"`
}

// Domain represents a campaign-to-domain binding.
type Domain struct {
	ID          uuid.UUID  `json:"id"`
	WorkspaceID uuid.UUID  `json:"workspace_id"`
	Domain      string     `json:"domain"`
	CampaignID  *uuid.UUID `json:"campaign_id"`
	State       string     `json:"state"`
	CreatedAt   time.Time  `json:"created_at"`
}

// User represents an administrative user.
type User struct {
	ID        uuid.UUID `json:"id"`
	Login     string    `json:"login"`
	Role      string    `json:"role"`
	State     string    `json:"state"`
	ApiKey    string    `json:"api_key"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}
