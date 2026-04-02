package queue

import (
	"context"
	"net"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2"
	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// ClickRecord is the serialized form of a RawClick, ready for ClickHouse batch INSERT.
// UUID fields are stored as strings and converted to [16]byte at flush time.
type ClickRecord struct {
	CreatedAt        time.Time
	CampaignID       string
	CampaignAlias    string
	StreamID         string
	OfferID          string
	LandingID        string
	IP               string
	CountryCode      string
	City             string
	ISP              string
	DeviceType       string
	DeviceModel      string
	OS               string
	OSVersion        string
	Browser          string
	BrowserVersion   string
	UserAgent        string
	Referrer         string
	IsBot            uint8
	IsUniqueGlobal   uint8
	IsUniqueCampaign uint8
	IsUniqueStream   uint8
	SubID1           string
	SubID2           string
	SubID3           string
	SubID4           string
	SubID5           string
	Cost             float64
	Payout           float64
	ActionType       string
	ClickToken       string
}

// FromRawClick converts a RawClick to a ClickRecord for ClickHouse insertion.
func FromRawClick(rc *model.RawClick) ClickRecord {
	r := ClickRecord{
		CreatedAt:      rc.CreatedAt,
		CampaignAlias:  rc.CampaignAlias,
		CountryCode:    rc.CountryCode,
		City:           rc.City,
		ISP:            rc.ISP,
		DeviceType:     rc.DeviceType,
		DeviceModel:    rc.DeviceModel,
		OS:             rc.OS,
		OSVersion:      rc.OSVersion,
		Browser:        rc.Browser,
		BrowserVersion: rc.BrowserVersion,
		UserAgent:      rc.UserAgent,
		Referrer:       rc.Referrer,
		SubID1:         rc.SubID1,
		SubID2:         rc.SubID2,
		SubID3:         rc.SubID3,
		SubID4:         rc.SubID4,
		SubID5:         rc.SubID5,
		Cost:           rc.Cost,
		Payout:         rc.Payout,
		ActionType:     rc.ActionType,
		ClickToken:     rc.ClickToken,
	}

	if rc.IsBot {
		r.IsBot = 1
	}
	if rc.IsUniqueGlobal {
		r.IsUniqueGlobal = 1
	}
	if rc.IsUniqueCampaign {
		r.IsUniqueCampaign = 1
	}
	if rc.IsUniqueStream {
		r.IsUniqueStream = 1
	}

	r.CampaignID = rc.CampaignID.String()
	r.StreamID = rc.StreamID.String()
	r.OfferID = rc.OfferID.String()
	r.LandingID = rc.LandingID.String()

	if rc.IP != nil {
		r.IP = rc.IP.String()
	}

	if r.CreatedAt.IsZero() {
		r.CreatedAt = time.Now().UTC()
	}

	return r
}

// Writer is the async ClickHouse batch writer.
// It owns a buffered channel of ClickRecords and flushes to ClickHouse
// every 500ms or when the batch reaches 5000 records.
type Writer struct {
	conn      driver.Conn
	clickChan chan ClickRecord
	logger    *zap.Logger
}

// NewWriter creates and connects a Writer to ClickHouse.
func NewWriter(addr, database string, logger *zap.Logger) (*Writer, error) {
	conn, err := clickhouse.Open(&clickhouse.Options{
		Addr: []string{addr},
		Auth: clickhouse.Auth{
			Database: database,
			Username: "default",
			Password: "",
		},
		DialTimeout:     10 * time.Second,
		MaxOpenConns:    5,
		MaxIdleConns:    2,
		ConnMaxLifetime: time.Hour,
	})
	if err != nil {
		return nil, err
	}

	if err := conn.Ping(context.Background()); err != nil {
		return nil, err
	}

	return &Writer{
		conn:      conn,
		clickChan: make(chan ClickRecord, 10_000),
		logger:    logger,
	}, nil
}

// Chan returns the write-only channel for sending click records.
func (w *Writer) Chan() chan<- ClickRecord {
	return w.clickChan
}

// Run starts the batch writer loop. Blocks until ctx is cancelled.
func (w *Writer) Run(ctx context.Context) error {
	ticker := time.NewTicker(500 * time.Millisecond)
	defer ticker.Stop()

	batch := make([]ClickRecord, 0, 5000)

	for {
		select {
		case record := <-w.clickChan:
			batch = append(batch, record)
			if len(batch) >= 5000 {
				w.flush(batch)
				batch = batch[:0]
			}

		case <-ticker.C:
			if len(batch) > 0 {
				w.flush(batch)
				batch = batch[:0]
			}

		case <-ctx.Done():
			// Drain remaining and flush before exit
			for len(w.clickChan) > 0 {
				batch = append(batch, <-w.clickChan)
			}
			if len(batch) > 0 {
				w.flush(batch)
			}
			w.logger.Info("click writer shut down", zap.Int("flushed", len(batch)))
			return nil
		}
	}
}

// flush performs the actual ClickHouse batch INSERT.
//
// KEY DESIGN DECISIONS:
// 1. Named column list — skip click_id (CH DEFAULT generateUUIDv4() handles it)
// 2. UUID columns require [16]byte — parse from string at flush time
// 3. IPv6 column requires net.IP with To16() (always 16 bytes)
// 4. FixedString(2) requires exactly 2 bytes — pad/truncate country_code
func (w *Writer) flush(records []ClickRecord) {
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	// Named INSERT: skip click_id (auto-generated), list all 31 other columns explicitly.
	// This is the critical fix — positional INSERT hit click_id (UUID) with a string.
	b, err := w.conn.PrepareBatch(ctx, `INSERT INTO clicks
		(created_at, campaign_id, campaign_alias, stream_id, offer_id, landing_id,
		 ip, country_code, city, isp, device_type, device_model, os, os_version,
		 browser, browser_version, user_agent, referrer,
		 is_bot, is_unique_global, is_unique_campaign, is_unique_stream,
		 sub_id_1, sub_id_2, sub_id_3, sub_id_4, sub_id_5,
		 cost, payout, action_type, click_token)`)
	if err != nil {
		w.logger.Error("clickhouse prepare batch failed", zap.Error(err))
		return
	}

	for _, r := range records {
		// UUID columns: clickhouse-go v2 accepts string directly via AppendRow
		// (it calls uuid.Parse internally — see column/uuid.go AppendRow case string:)
		campaignID := r.CampaignID
		if campaignID == "" {
			campaignID = "00000000-0000-0000-0000-000000000000"
		}
		streamID := r.StreamID
		if streamID == "" {
			streamID = "00000000-0000-0000-0000-000000000000"
		}
		offerID := r.OfferID
		if offerID == "" {
			offerID = "00000000-0000-0000-0000-000000000000"
		}
		landingID := r.LandingID
		if landingID == "" {
			landingID = "00000000-0000-0000-0000-000000000000"
		}

		// IPv6 column: always 16-byte form
		ip := parseIPv6(r.IP)

		// FixedString(2): must be exactly 2 bytes
		cc := fixedString2(r.CountryCode)

		if err := b.Append(
			r.CreatedAt,
			campaignID,
			r.CampaignAlias,
			streamID,
			offerID,
			landingID,
			ip,
			cc,
			r.City,
			r.ISP,
			r.DeviceType,
			r.DeviceModel,
			r.OS,
			r.OSVersion,
			r.Browser,
			r.BrowserVersion,
			r.UserAgent,
			r.Referrer,
			r.IsBot,
			r.IsUniqueGlobal,
			r.IsUniqueCampaign,
			r.IsUniqueStream,
			r.SubID1,
			r.SubID2,
			r.SubID3,
			r.SubID4,
			r.SubID5,
			r.Cost,
			r.Payout,
			r.ActionType,
			r.ClickToken,
		); err != nil {
			w.logger.Error("batch append failed", zap.Error(err), zap.String("token", r.ClickToken))
		}
	}

	if err := b.Send(); err != nil {
		w.logger.Error("clickhouse send batch failed",
			zap.Error(err),
			zap.Int("records", len(records)),
		)
		return
	}

	w.logger.Info("clicks flushed to ClickHouse", zap.Int("count", len(records)))
}

// parseUUID parses a UUID string to [16]byte. Returns fallback on parse error.
func parseUUID(s string, fallback [16]byte) [16]byte {
	id, err := uuid.Parse(s)
	if err != nil {
		return fallback
	}
	return id
}

// parseIPv6 converts an IP string to a 16-byte IPv6 net.IP.
// Returns the IPv6 zero address if parsing fails.
func parseIPv6(s string) net.IP {
	if s == "" {
		return net.IPv6zero
	}
	ip := net.ParseIP(s)
	if ip == nil {
		return net.IPv6zero
	}
	// Ensure always 16-byte IPv6 form (IPv4 addresses get IPv4-mapped)
	if ip16 := ip.To16(); ip16 != nil {
		return ip16
	}
	return net.IPv6zero
}

// fixedString2 returns a string of exactly 2 bytes for ClickHouse FixedString(2).
// Empty or unknown → two spaces ("  "). Truncates if longer.
func fixedString2(s string) string {
	switch len(s) {
	case 2:
		return s
	case 0:
		return "  "
	default:
		// Truncate to 2 bytes (should not happen for ISO country codes)
		return s[:2]
	}
}
