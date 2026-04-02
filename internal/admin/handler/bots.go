package handler

import (
	"encoding/json"
	"net"
	"net/http"

	"go.uber.org/zap"
)

// BotIPRequest represents the payload for Add/Exclude/Replace IP operations.
type BotIPRequest struct {
	IPs string `json:"ips"`
}

// BotIPCheckRequest represents the payload for Check IP operation.
type BotIPCheckRequest struct {
	IP string `json:"ip"`
}

// HandleGetBotIPs returns the current list of bot IP ranges.
// GET /api/v1/bots/ips
func (h *Handler) HandleGetBotIPs(w http.ResponseWriter, r *http.Request) {
	ranges := h.botDB.List()
	h.respondJSON(w, http.StatusOK, map[string]interface{}{
		"count":  len(ranges),
		"ranges": ranges,
	})
}

// HandleAddBotIPs adds new bot IP ranges.
// POST /api/v1/bots/ips
func (h *Handler) HandleAddBotIPs(w http.ResponseWriter, r *http.Request) {
	var req BotIPRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if err := h.botDB.Add(req.IPs); err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	h.respondJSON(w, http.StatusOK, map[string]interface{}{
		"count": h.botDB.Count(),
	})
}

// HandleExcludeBotIPs removes matching bot IP ranges.
// DELETE /api/v1/bots/ips
func (h *Handler) HandleExcludeBotIPs(w http.ResponseWriter, r *http.Request) {
	var req BotIPRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if err := h.botDB.Exclude(req.IPs); err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	h.respondJSON(w, http.StatusOK, map[string]interface{}{
		"count": h.botDB.Count(),
	})
}

// HandleReplaceBotIPs replaces all bot IP ranges.
// PUT /api/v1/bots/ips
func (h *Handler) HandleReplaceBotIPs(w http.ResponseWriter, r *http.Request) {
	var req BotIPRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if err := h.botDB.Replace(req.IPs); err != nil {
		h.respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	h.respondJSON(w, http.StatusOK, map[string]interface{}{
		"count": h.botDB.Count(),
	})
}

// HandleClearBotIPs removes all bot IP ranges.
// DELETE /api/v1/bots/ips/all
func (h *Handler) HandleClearBotIPs(w http.ResponseWriter, r *http.Request) {
	if err := h.botDB.Clear(); err != nil {
		h.respondError(w, http.StatusInternalServerError, "failed to clear bot ips")
		return
	}

	h.respondJSON(w, http.StatusOK, map[string]interface{}{
		"count": 0,
	})
}

// HandleCheckBotIP checks if a single IP is flagged as a bot.
// POST /api/v1/bots/ips/check
func (h *Handler) HandleCheckBotIP(w http.ResponseWriter, r *http.Request) {
	var req BotIPCheckRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	ip := net.ParseIP(req.IP)
	if ip == nil {
		h.respondError(w, http.StatusBadRequest, "invalid ip address")
		return
	}

	isBot := h.botDB.Contains(ip)
	h.logger.Debug("checking bot ip", zap.String("ip", req.IP), zap.Bool("is_bot", isBot))

	h.respondJSON(w, http.StatusOK, map[string]interface{}{
		"ip":     req.IP,
		"is_bot": isBot,
	})
}
