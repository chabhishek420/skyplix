package stage

import (
	"time"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/geo"
	"github.com/skyplix/zai-tds/internal/device"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// UpdateRawClickStage — Pipeline Stage 6
// Enriches the RawClick with GeoIP data and UA device parsing.
// Both lookups are sub-millisecond (in-memory databases).
type UpdateRawClickStage struct {
	Geo    *geo.Resolver
	Device *device.Detector
	Logger *zap.Logger
}

func (s *UpdateRawClickStage) AlwaysRun() bool { return false }
func (s *UpdateRawClickStage) Name() string { return "UpdateRawClick" }

func (s *UpdateRawClickStage) Process(payload *pipeline.Payload) error {
	rc := payload.RawClick
	rc.CreatedAt = time.Now().UTC()

	// GeoIP resolution
	if s.Geo != nil && rc.IP != nil {
		result := s.Geo.Lookup(rc.IP)
		rc.CountryCode = result.CountryCode
		rc.City = result.City
		rc.ISP = result.ISP
	}

	// Device / UA parsing
	if rc.UserAgent != "" {
		result := s.Device.Parse(rc.UserAgent)
		rc.DeviceType = result.DeviceType
		rc.Browser = result.Browser
		rc.BrowserVersion = result.BrowserVersion
		rc.OS = result.OS
		rc.OSVersion = result.OSVersion
	}

	s.Logger.Debug("raw click enriched",
		zap.String("country", rc.CountryCode),
		zap.String("device", rc.DeviceType),
		zap.String("browser", rc.Browser),
		zap.Bool("is_bot", rc.IsBot),
		zap.String("ip", rc.IP.String()),
	)

	return nil
}
