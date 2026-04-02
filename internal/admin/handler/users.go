package handler

import (
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"net/http"

	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"
	"golang.org/x/crypto/bcrypt"

	"github.com/skyplix/zai-tds/internal/model"
)

// HandleListUsers returns a paginated list of users.
func (h *Handler) HandleListUsers(w http.ResponseWriter, r *http.Request) {
	limit, offset := h.parsePagination(r)

	users, err := h.users.List(r.Context(), limit, offset)
	if err != nil {
		h.logger.Error("list users failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to list users")
		return
	}

	h.respondJSON(w, http.StatusOK, users)
}

// HandleGetUser returns a single user by ID.
func (h *Handler) HandleGetUser(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid user id")
		return
	}

	u, err := h.users.GetByID(r.Context(), id)
	if err != nil {
		h.logger.Error("get user failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusNotFound, "user not found")
		return
	}

	h.respondJSON(w, http.StatusOK, u)
}

// HandleCreateUser creates a new user.
func (h *Handler) HandleCreateUser(w http.ResponseWriter, r *http.Request) {
	var input struct {
		model.User
		Password string `json:"password"`
	}
	if err := json.NewDecoder(r.Body).Decode(&input); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}

	if input.Login == "" || input.Password == "" {
		h.respondError(w, http.StatusBadRequest, "login and password are required")
		return
	}

	// Hash password with bcrypt (cost 12)
	hashedBytes, err := bcrypt.GenerateFromPassword([]byte(input.Password), 12)
	if err != nil {
		h.logger.Error("password hash failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to process password")
		return
	}
	passwordHash := string(hashedBytes)

	if input.ApiKey == "" {
		// Generate a cryptographically secure API key
		keyBytes := make([]byte, 24)
		if _, err := rand.Read(keyBytes); err != nil {
			h.logger.Error("api key generation failed", zap.Error(err))
			h.respondError(w, http.StatusInternalServerError, "failed to generate api key")
			return
		}
		input.ApiKey = "sk_" + hex.EncodeToString(keyBytes)
	}

	if err := h.users.Create(r.Context(), &input.User, passwordHash); err != nil {
		h.logger.Error("create user failed", zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to create user")
		return
	}

	h.respondJSON(w, http.StatusCreated, input.User)
}

// HandleUpdateUser updates an existing user.
func (h *Handler) HandleUpdateUser(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid user id")
		return
	}

	var u model.User
	if err := json.NewDecoder(r.Body).Decode(&u); err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid request body")
		return
	}
	u.ID = id

	if err := h.users.Update(r.Context(), &u); err != nil {
		h.logger.Error("update user failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to update user")
		return
	}

	h.respondJSON(w, http.StatusOK, u)
}

// HandleDeleteUser deletes a user.
func (h *Handler) HandleDeleteUser(w http.ResponseWriter, r *http.Request) {
	id, err := h.parseUUID(chi.URLParam(r, "id"))
	if err != nil {
		h.respondError(w, http.StatusBadRequest, "invalid user id")
		return
	}

	if err := h.users.Delete(r.Context(), id); err != nil {
		h.logger.Error("delete user failed", zap.String("id", id.String()), zap.Error(err))
		h.respondError(w, http.StatusInternalServerError, "failed to delete user")
		return
	}

	h.respondJSON(w, http.StatusNoContent, nil)
}
