package handler

import (
	"encoding/json"
	"net/http"

	"go.uber.org/zap"
	"golang.org/x/crypto/bcrypt"

	"github.com/skyplix/zai-tds/internal/auth"
)

type LoginRequest struct {
	Username string `json:"username"`
	Password string `json:"password"`
}

type LoginResponse struct {
	Token string `json:"token"`
}

// HandleLogin handles admin user login and returns a JWT.
func (h *Handler) HandleLogin(jwtManager *auth.JWTManager) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var req LoginRequest
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
			h.respondError(w, http.StatusBadRequest, "invalid request body")
			return
		}

		// Find user by login
		var userID string
		var passwordHash string
		var role string
		err := h.db.QueryRow(r.Context(), "SELECT id, password_hash, role FROM users WHERE login = $1 AND state = 'active'", req.Username).Scan(&userID, &passwordHash, &role)
		if err != nil {
			h.logger.Warn("login attempt failed: user not found or inactive", zap.String("username", req.Username))
			h.respondError(w, http.StatusUnauthorized, "invalid credentials")
			return
		}

		// Verify password
		if err := bcrypt.CompareHashAndPassword([]byte(passwordHash), []byte(req.Password)); err != nil {
			h.logger.Warn("login attempt failed: invalid password", zap.String("username", req.Username))
			h.respondError(w, http.StatusUnauthorized, "invalid credentials")
			return
		}

		// Security Check: Warn if using default password
		if req.Password == "admin123" && req.Username == "admin" {
			h.logger.Warn("SECURITY WARNING: Admin is still using default password 'admin123'. Change it immediately!")
		}

		// Generate JWT
		token, err := jwtManager.Generate(userID, role)
		if err != nil {
			h.logger.Error("failed to generate JWT", zap.Error(err))
			h.respondError(w, http.StatusInternalServerError, "internal error")
			return
		}

		h.respondJSON(w, http.StatusOK, LoginResponse{Token: token})
	}
}
