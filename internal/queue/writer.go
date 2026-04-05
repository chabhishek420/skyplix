package queue

import (
	"context"
	"net"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2"
	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"github.com/google/uuid"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/metrics"
	"github.com/skyplix/zai-tds/internal/model"
)

// ClickRecord is the serialized form of a RawClick, ready for ClickHouse batch INSERT.
// UUID fields are stored as strings and converted to [16]byte at flush time.
type ClickRecord struct {
	CreatedAt        time.Time
	WorkspaceID      string
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
	Cost             int64
	Payout           int64
	ActionType       string
	ClickToken       string
	RequestID        string
	JA3              string
	JA4              string
	TLSHost          string
	BotReason        string
	BehaviorScore    uint8
}

// ConversionRecord is the serialized form of a Conversion, ready for ClickHouse batch INSERT.
type ConversionRecord struct {
	ID                 string
	WorkspaceID        string
	CreatedAt          time.Time
	ClickToken         string
	VisitorCode        string
	CampaignID         string
	StreamID           string
	OfferID            string
	LandingID          string
	AffiliateNetworkID string
	SourceID           string
	CountryCode        string
	Status             string
	ConversionType     string
	Payout             int64
	Revenue            int64
	ExternalID         string
}

// FromRawClick converts a RawClick to a ClickRecord for ClickHouse insertion.
func FromRawClick(rc *model.RawClick) ClickRecord {
	r := ClickRecord{
		CreatedAt:      rc.CreatedAt,
		WorkspaceID:    rc.WorkspaceID.String(),
		RequestID:      rc.RequestID,
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
		JA3:            rc.JA3,
		JA4:            rc.JA4,
		TLSHost:        rc.TLSHost,
		BotReason:      rc.BotReason,
		BehaviorScore:  rc.BehaviorScore,
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

// FromConversion converts a Conversion to a ConversionRecord for ClickHouse insertion.
func FromConversion(c *model.Conversion) ConversionRecord {
	return ConversionRecord{
		ID:                 c.ID.String(),
		WorkspaceID:        c.WorkspaceID.String(),
		CreatedAt:          c.CreatedAt,
		ClickToken:         c.ClickToken,
		VisitorCode:        c.VisitorCode,
		CampaignID:         c.CampaignID.String(),
		StreamID:           c.StreamID.String(),
		OfferID:            c.OfferID.String(),
		LandingID:          c.LandingID.String(),
		AffiliateNetworkID: c.AffiliateNetworkID.String(),
		SourceID:           c.SourceID.String(),
		CountryCode:        c.CountryCode,
		Status:             c.Status,
		ConversionType:     c.ConversionType,
		Payout:             c.Payout,
		Revenue:            c.Revenue,
		ExternalID:         c.ExternalID,
	}
}

// Writer is the async ClickHouse batch writer.
// It owns a buffered channel of ClickRecords and flushes to ClickHouse
// every 500ms or when the batch reaches 5000 records.
type Writer struct {
	conn      driver.Conn
	clickChan chan ClickRecord
	convChan  chan ConversionRecord
	Logger    *zap.Logger
}

// NewWriter creates and connects a Writer to ClickHouse.
func NewWriter(addr, database string, Logger *zap.Logger) (*Writer, error) {
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
		convChan:  make(chan ConversionRecord, 5_000),
		Logger:    Logger,
	}, nil
}

// ClickChan returns the write-only channel for sending click records.
func (w *Writer) ClickChan() chan<- ClickRecord {
	return w.clickChan
}

// ConvChan returns the write-only channel for sending conversion records.
func (w *Writer) ConvChan() chan<- ConversionRecord {
	return w.convChan
}

// Stats returns the current number of records waiting in the queues.
func (w *Writer) Stats() (int, int) {
	return len(w.clickChan), len(w.convChan)
}

// Run starts the batch writer loop. Blocks until ctx is cancelled.
func (w *Writer) Run(ctx context.Context) error {
	ticker := time.NewTicker(500 * time.Millisecond)
	defer ticker.Stop()

	clickBatch := make([]ClickRecord, 0, 5000)
	convBatch := make([]ConversionRecord, 0, 1000)

	for {
		select {
		case record := <-w.clickChan:
			clickBatch = append(clickBatch, record)
			if len(clickBatch) >= 5000 {
				w.flushClicks(clickBatch)
				clickBatch = clickBatch[:0]
			}

		case record := <-w.convChan:
			convBatch = append(convBatch, record)
			if len(convBatch) >= 1000 {
				w.flushConversions(convBatch)
				convBatch = convBatch[:0]
			}

		case <-ticker.C:
			metrics.ClickHouseChannelDepth.WithLabelValues("clicks").Set(float64(len(w.clickChan)))
			metrics.ClickHouseChannelDepth.WithLabelValues("conversions").Set(float64(len(w.convChan)))
			
			if len(clickBatch) > 0 {
				w.flushClicks(clickBatch)
				clickBatch = clickBatch[:0]
			}
			if len(convBatch) > 0 {
				w.flushConversions(convBatch)
				convBatch = convBatch[:0]
			}

		case <-ctx.Done():
			// Drain remaining and flush before exit
			for len(w.clickChan) > 0 {
				clickBatch = append(clickBatch, <-w.clickChan)
			}
			if len(clickBatch) > 0 {
				w.flushClicks(clickBatch)
			}

			for len(w.convChan) > 0 {
				convBatch = append(convBatch, <-w.convChan)
			}
			if len(convBatch) > 0 {
				w.flushConversions(convBatch)
			}

			w.Logger.Info("writer shut down",
				zap.Int("clicks_flushed", len(clickBatch)),
				zap.Int("convs_flushed", len(convBatch)),
			)
			return nil
		}
	}
}

// flushClicks performs the actual ClickHouse batch INSERT for clicks.
func (w *Writer) flushClicks(records []ClickRecord) {
	start := time.Now()
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
		 cost, payout, action_type, click_token, ja3, ja4, tls_host, bot_reason)`)
	if err != nil {
		w.Logger.Error("clickhouse prepare batch failed", zap.Error(err))
		metrics.ClickHouseFlushesTotal.WithLabelValues("clicks", "error").Inc()
		return
	}

	for _, r := range records {
		// UUID columns: validate before appending to ensure the batch doesn't fail due to type mismatch.
		zero := uuid.Nil
		campaignID := w.ParseUUIDVal(r.CampaignID, zero)
		streamID := w.ParseUUIDVal(r.StreamID, zero)
		offerID := w.ParseUUIDVal(r.OfferID, zero)
		landingID := w.ParseUUIDVal(r.LandingID, zero)

		// IPv6 column: always 16-byte form
		ip := ParseIPv6(r.IP)

		// FixedString(2): must be exactly 2 bytes
		cc := FixedString2(r.CountryCode)

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
			r.JA3,
			r.JA4,
			r.TLSHost,
			r.BotReason,
		); err != nil {
			w.Logger.Error("batch append failed", zap.Error(err), zap.String("token", r.ClickToken))
		}
	}

	if err := b.Send(); err != nil {
		w.Logger.Error("clickhouse send batch failed",
			zap.Error(err),
			zap.Int("records", len(records)),
		)
		metrics.ClickHouseFlushesTotal.WithLabelValues("clicks", "error").Inc()
		return
	}

	metrics.ClickHouseFlushesTotal.WithLabelValues("clicks", "success").Inc()
	metrics.ClickHouseFlushDuration.Observe(time.Since(start).Seconds())
	metrics.ClickHouseBatchSize.Observe(float64(len(records)))

	w.Logger.Info("clicks flushed to ClickHouse", zap.Int("count", len(records)))
}

// flushConversions performs the actual ClickHouse batch INSERT for conversions.
func (w *Writer) flushConversions(records []ConversionRecord) {
	start := time.Now()
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	b, err := w.conn.PrepareBatch(ctx, `INSERT INTO conversions
		(id, created_at, click_token, campaign_id, stream_id, offer_id, landing_id,
		 affiliate_network_id, source_id, country_code, status, payout, revenue, external_id)`)
	if err != nil {
		w.Logger.Error("clickhouse prepare conversions batch failed", zap.Error(err))
		metrics.ClickHouseFlushesTotal.WithLabelValues("conversions", "error").Inc()
		return
	}

	zero := uuid.Nil
	for _, r := range records {
		id := w.ParseUUIDVal(r.ID, zero)
		campID := w.ParseUUIDVal(r.CampaignID, zero)
		strID := w.ParseUUIDVal(r.StreamID, zero)
		offID := w.ParseUUIDVal(r.OfferID, zero)
		lanID := w.ParseUUIDVal(r.LandingID, zero)
		anID := w.ParseUUIDVal(r.AffiliateNetworkID, zero)
		srcID := w.ParseUUIDVal(r.SourceID, zero)

		cc := FixedString2(r.CountryCode)

		if err := b.Append(
			id,
			r.CreatedAt,
			r.ClickToken,
			campID,
			strID,
			offID,
			lanID,
			anID,
			srcID,
			cc,
			r.Status,
			r.Payout,
			r.Revenue,
			r.ExternalID,
		); err != nil {
			w.Logger.Error("conversions batch append failed", zap.Error(err), zap.String("token", r.ClickToken))
		}
	}

	if err := b.Send(); err != nil {
		w.Logger.Error("clickhouse send conversions batch failed",
			zap.Error(err),
			zap.Int("records", len(records)),
		)
		metrics.ClickHouseFlushesTotal.WithLabelValues("conversions", "error").Inc()
		return
	}

	metrics.ClickHouseFlushesTotal.WithLabelValues("conversions", "success").Inc()
	metrics.ClickHouseFlushDuration.Observe(time.Since(start).Seconds())
	metrics.ClickHouseBatchSize.Observe(float64(len(records)))

	w.Logger.Info("conversions flushed to ClickHouse", zap.Int("count", len(records)))
}

// ParseUUIDVal parses a UUID string to uuid.UUID. Returns fallback on parse error.
func (w *Writer) ParseUUIDVal(s string, fallback uuid.UUID) uuid.UUID {
	if s == "" {
		return fallback
	}
	id, err := uuid.Parse(s)
	if err != nil {
		w.Logger.Warn("invalid UUID detected — falling back", zap.String("uuid", s))
		return fallback
	}
	return id
}

// ParseIPv6 converts an IP string to a 16-byte IPv6 net.IP.
// Returns the IPv6 zero address if parsing fails.
func ParseIPv6(s string) net.IP {
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

// FixedString2 returns a string of exactly 2 bytes for ClickHouse FixedString(2).
// Empty or unknown → two spaces ("  "). Truncates if longer.
func FixedString2(s string) string {
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
