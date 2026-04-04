package stage

import (
	"net"
	"strings"
	"time"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// NormalizeIPStage — Pipeline Stage 0
// Extracts the real client IP from the request according to the logic in reference/YellowCloaker/bases/ipcountry.php:
// Order: HTTP_CF_CONNECTING_IP -> HTTP_CLIENT_IP -> HTTP_X_FORWARDED_FOR -> REMOTE_ADDR.
type NormalizeIPStage struct{}

func (s *NormalizeIPStage) AlwaysRun() bool { return false }
func (s *NormalizeIPStage) Name() string    { return "NormalizeIP" }

func (s *NormalizeIPStage) Process(payload *pipeline.Payload) error {
	r := payload.Request

	var ipStr string

	// 1. CF-Connecting-IP
	if ip := r.Header.Get("CF-Connecting-IP"); ip != "" {
		ipStr = ip
	}

	// 2. Client-IP
	if ipStr == "" {
		if ip := r.Header.Get("Client-IP"); ip != "" {
			ipStr = ip
		}
	}

	// 3. X-Forwarded-For
	if ipStr == "" {
		if xff := r.Header.Get("X-Forwarded-For"); xff != "" {
			parts := strings.Split(xff, ",")
			if len(parts) > 0 {
				ipStr = strings.TrimSpace(parts[0])
			}
		}
	}

	// 4. RemoteAddr
	if ipStr == "" {
		host, _, err := net.SplitHostPort(r.RemoteAddr)
		if err != nil {
			ipStr = r.RemoteAddr
		} else {
			ipStr = host
		}
	}

	// Parse it
	parsedIP := net.ParseIP(ipStr)

	if payload.RawClick == nil {
		// Initialize it early if it hasn't been already
		payload.RawClick = &model.RawClick{
			CreatedAt: time.Now(),
		}
	}

	payload.RawClick.IP = parsedIP

	return nil
}
