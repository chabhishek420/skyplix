package model

import (
	"time"

	"github.com/google/uuid"
)

// Conversion represents a single postback/conversion event.
// This is the model used in the application logic and ClickHouse ingestion.
type Conversion struct {
	ID                 uuid.UUID `json:"id"`
	WorkspaceID        uuid.UUID `json:"workspace_id"`
	CreatedAt          time.Time `json:"created_at"`
	ClickToken         string    `json:"click_token"`
	VisitorCode        string    `json:"visitor_code"`
	CampaignID         uuid.UUID `json:"campaign_id"`
	StreamID           uuid.UUID `json:"stream_id"`
	OfferID            uuid.UUID `json:"offer_id"`
	LandingID          uuid.UUID `json:"landing_id"`
	AffiliateNetworkID uuid.UUID `json:"affiliate_network_id"`
	SourceID           uuid.UUID `json:"source_id"`
	CountryCode        string    `json:"country_code"`
	Status             string    `json:"status"` // lead, sale, rejected, hold
	ConversionType     string    `json:"conversion_type"`
	Payout             int64     `json:"payout"`
	Revenue            int64     `json:"revenue"`
	ExternalID         string    `json:"external_id"` // Transaction ID from affiliate network
}

// AttributionData is the subset of click metadata cached in Valkey for attribution.
// Key: "attr:{click_token}"
type AttributionData struct {
	WorkspaceID        uuid.UUID `json:"workspace_id"`
	CampaignID         uuid.UUID `json:"campaign_id"`
	StreamID           uuid.UUID `json:"stream_id"`
	OfferID            uuid.UUID `json:"offer_id"`
	LandingID          uuid.UUID `json:"landing_id"`
	AffiliateNetworkID uuid.UUID `json:"affiliate_network_id"`
	SourceID           uuid.UUID `json:"source_id"`
	CountryCode        string    `json:"country_code"`
}
