package cloak

import (
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"net/http"
	"strings"
	"time"

	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"
)

type Handler struct {
	detector *Detector
	logger   *zap.Logger
	jsPath   string
}

func NewHandler(detector *Detector, logger *zap.Logger, jsPath string) *Handler {
	return &Handler{
		detector: detector,
		logger:   logger,
		jsPath:   jsPath,
	}
}

type ChallengeRequest struct {
	Token        string `json:"token,omitempty"`
	Domain       string `json:"domain,omitempty"`
	IsBot        bool   `json:"is_bot"`
	Reason       string `json:"reason,omitempty"`
	Score        int    `json:"score"`
	Canvas       bool   `json:"canvas"`
	WebGL        bool   `json:"webgl"`
	Audio        bool   `json:"audio"`
	Timezone     bool   `json:"timezone"`
	Plugins      bool   `json:"plugins"`
	Languages    bool   `json:"languages"`
	WebRTC       bool   `json:"webrtc"`
	ScreenMatch  bool   `json:"screen_match"`
	CanvasHash   string `json:"canvasHash,omitempty"`
	WebGLHash    string `json:"webglHash,omitempty"`
	AudioHash    string `json:"audioHash,omitempty"`
	UserAgent    string `json:"userAgent,omitempty"`
	Platform     string `json:"platform,omitempty"`
	ScreenWidth  int    `json:"screenWidth,omitempty"`
	ScreenHeight int    `json:"screenHeight,omitempty"`
	WebDriver    bool   `json:"webdriver"`
}

type ChallengeResponse struct {
	Action    string `json:"action"`
	URL       string `json:"url,omitempty"`
	Challenge bool   `json:"challenge,omitempty"`
	Token     string `json:"token,omitempty"`
}

func (h *Handler) ServeCloakJS(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/javascript")
	w.Header().Set("Cache-Control", "no-store, no-cache, must-revalidate")
	w.Header().Set("Pragma", "no-cache")

	domain := chi.URLParam(r, "domain")
	if domain == "" {
		domain = r.Host
	}

	config := map[string]interface{}{
		"domain":    domain,
		"timeout":   3000,
		"tzStart":   -720,
		"tzEnd":     840,
		"challenge": true,
		"token":     h.generateToken(r),
	}

	w.WriteHeader(http.StatusOK)
	_, _ = w.Write([]byte(fmt.Sprintf(`
(function() {
    var config = %s;
    var domain = config.domain;
    var token = config.token;
    
    // Check if already verified
    if (document.cookie.indexOf('cloak_verified=' + token) !== -1) {
        return; // Already passed challenge
    }
    
    // Load BotDetector if not present
    if (!window.BotDetector) {
        var script = document.createElement('script');
        script.src = '/js/cloak.js';
        script.onload = function() { runChallenge(config); };
        document.head.appendChild(script);
    } else {
        runChallenge(config);
    }
    
    function runChallenge(cfg) {
        var detector = new BotDetector({
            timeout: cfg.timeout || 3000,
            tzStart: cfg.tzStart || -720,
            tzEnd: cfg.tzEnd || 840,
            callback: function(result) {
                // Add token to result
                result.token = token;
                result.domain = domain;
                
                // Send to server
                fetch('/js/challenge', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(result)
                }).then(function(response) {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.json();
                    }
                }).then(function(data) {
                    if (data && data.action === 'safe') {
                        // Set cookie and continue
                        document.cookie = 'cloak_verified=' + token + '; path=/; max-age=3600';
                        window.location.reload();
                    } else if (data && data.action === 'redirect') {
                        window.location.href = data.url;
                    }
                }).catch(function(err) {
                    console.error('Cloak error:', err);
                });
            }
        });
        detector.monitor();
    }
})();
`, h.jsonMarshal(config))))
}

func (h *Handler) HandleChallenge(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req ChallengeRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.logger.Debug("challenge decode error", zap.Error(err))
		http.Error(w, "Invalid request", http.StatusBadRequest)
		return
	}

	h.logger.Debug("challenge received",
		zap.Bool("is_bot", req.IsBot),
		zap.Int("score", req.Score),
		zap.String("reason", req.Reason),
		zap.String("ua", req.UserAgent),
	)

	if req.IsBot || req.Score < 60 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusOK)
		_ = json.NewEncoder(w).Encode(ChallengeResponse{
			Action: "block",
			URL:    "",
		})
		return
	}

	h.logger.Info("challenge passed",
		zap.String("ua", req.UserAgent),
		zap.Int("score", req.Score),
	)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	_ = json.NewEncoder(w).Encode(ChallengeResponse{
		Action: "safe",
		Token:  req.Token,
	})
}

func (h *Handler) serveSafePage(w http.ResponseWriter, r *http.Request, reason string) {
	h.logger.Info("serving safe page", zap.String("reason", reason))

	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.WriteHeader(http.StatusOK)

	safeHTML := `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 40px; background: #f5f5f5; color: #333; }
        .container { max-width: 600px; margin: 0 auto; text-align: center; }
        h1 { font-size: 72px; margin: 0; color: #666; }
        p { font-size: 18px; color: #666; }
        .error { margin: 20px 0; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <div class="error">
            <p>The page you were looking for could not be found.</p>
        </div>
    </div>
</body>
</html>`

	_, _ = w.Write([]byte(safeHTML))
}

func (h *Handler) generateToken(r *http.Request) string {
	data := fmt.Sprintf("%s:%s:%d",
		r.RemoteAddr,
		r.UserAgent(),
		time.Now().Unix()/300,
	)
	hash := sha256.Sum256([]byte(data))
	return hex.EncodeToString(hash[:8])
}

func (h *Handler) jsonMarshal(v interface{}) string {
	data, _ := json.Marshal(v)
	return string(data)
}

func (h *Handler) CheckCloakCookie(r *http.Request) bool {
	cookies := r.Header.Get("Cookie")
	return strings.Contains(cookies, "cloak_verified=")
}
