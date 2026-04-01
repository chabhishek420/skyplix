package queue

import (
	"context"
	"net"
	"time"

	"github.com/ClickHouse/clickhouse-go/v2"
	"github.com/ClickHouse/clickhouse-go/v2/lib/driver"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// ClickRecord is the serialized form of a RawClick, ready for ClickHouse batch INSERT.
type ClickRecord struct {
	ClickID         string
	CreatedAt       time.Time
	CampaignID      string
	CampaignAlias   string
	StreamID        string
	OfferID         string
	LandingID       string
	IP              string
	CountryCode     string
	City            string
	ISP             string
	DeviceType      string
	DeviceModel     string
	OS              string
	OSVersion       string
	Browser         string
	BrowserVersion  string
	UserAgent       string
	Referrer        string
	IsBot           uint8
	IsUniqueGlobal  uint8
	IsUniqueCampaign uint8
	IsUniqueStream  uint8
	SubID1          string
	SubID2          string
	SubID3          string
	SubID4          string
	SubID5          string
	Cost            float64
	Payout          float64
	ActionType      string
	ClickToken      string
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

	// Booleans → UInt8 for ClickHouse
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

	// UUIDs → strings
	r.CampaignID = rc.CampaignID.String()
	r.StreamID = rc.StreamID.String()
	r.OfferID = rc.OfferID.String()
	r.LandingID = rc.LandingID.String()

	// IP → string (ClickHouse IPv6 type accepts string)
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
// Stages push to this channel; the Writer owns the receive side.
func (w *Writer) Chan() chan<- ClickRecord {
	return w.clickChan
}

// Run starts the batch writer loop. Blocks until ctx is cancelled.
// Flushes any remaining records before returning.
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
			// Drain the channel and flush remaining
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
func (w *Writer) flush(records []ClickRecord) {
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	b, err := w.conn.PrepareBatch(ctx, "INSERT INTO clicks")
	if err != nil {
		w.logger.Error("clickhouse prepare batch failed", zap.Error(err))
		return
	}

	for _, r := range records {
		var ip net.IP
		if r.IP != "" {
			ip = net.ParseIP(r.IP)
		}
		if ip == nil {
			ip = net.IPv6zero
		}

		if err := b.Append(
			r.ClickToken,        // click_id (using token for now)
			r.CreatedAt,
			r.CampaignID,
			r.CampaignAlias,
			r.StreamID,
			r.OfferID,
			r.LandingID,
			ip,
			r.CountryCode,
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
			w.logger.Error("append to batch failed", zap.Error(err))
		}
	}

	if err := b.Send(); err != nil {
		w.logger.Error("clickhouse send batch failed",
			zap.Error(err),
			zap.Int("records", len(records)),
		)
		return
	}

	w.logger.Debug("clicks flushed to ClickHouse", zap.Int("count", len(records)))
}
