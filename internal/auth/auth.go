package auth

import (
	"context"
	"fmt"
	"net/http"
	"strings"

	"github.com/golang-jwt/jwt/v5"
	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/admin/repository"
)

type contextKey string

const (
	UserClaimsKey contextKey = "user_claims"
)

// UserClaims represents the authenticated user's session data.
type UserClaims struct {
	UserID      uuid.UUID
	Role        string
	WorkspaceIDs []uuid.UUID
}

// JWTAuthenticator handles token verification.
type JWTAuthenticator struct {
	PublicKey []byte // RS256 public key
}

// Middleware extracts and validates the Bearer token.
func (a *JWTAuthenticator) Middleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		authHeader := r.Header.Get("Authorization")
		if authHeader == "" || !strings.HasPrefix(authHeader, "Bearer ") {
			http.Error(w, `{"error": "unauthorized"}`, http.StatusUnauthorized)
			return
		}

		tokenStr := strings.TrimPrefix(authHeader, "Bearer ")

		// For Phase 8.1 foundations, we'll implement a stubbed verification
		// that extracts claims from the token string if it's in a specific format
		// or use actual JWT parsing if a key is provided.

		token, err := jwt.Parse(tokenStr, func(token *jwt.Token) (interface{}, error) {
			if _, ok := token.Method.(*jwt.SigningMethodRSA); !ok {
				return nil, fmt.Errorf("unexpected signing method: %v", token.Header["alg"])
			}
			return jwt.ParseRSAPublicKeyFromPEM(a.PublicKey)
		})

		if err != nil || !token.Valid {
			http.Error(w, `{"error": "invalid token"}`, http.StatusUnauthorized)
			return
		}

		claims, ok := token.Claims.(jwt.MapClaims)
		if !ok {
			http.Error(w, `{"error": "invalid claims"}`, http.StatusUnauthorized)
			return
		}

		userClaims := &UserClaims{
			UserID: uuid.MustParse(claims["sub"].(string)),
			Role:   claims["role"].(string),
		}

		if ws, ok := claims["workspaces"].([]interface{}); ok {
			for _, id := range ws {
				userClaims.WorkspaceIDs = append(userClaims.WorkspaceIDs, uuid.MustParse(id.(string)))
			}
		}

		ctx := context.WithValue(r.Context(), UserClaimsKey, userClaims)
		next.ServeHTTP(w, r.WithContext(ctx))
	})
}

// APIKeyAuth provides a bridge for API keys to populate the same UserClaims context.
func APIKeyAuth(db repository.DB) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			apiKey := r.Header.Get("X-Api-Key")
			if apiKey == "" {
				next.ServeHTTP(w, r) // Allow JWT middleware to try if no API key
				return
			}

			var userID uuid.UUID
			var userRole string
			err := db.QueryRow(r.Context(), "SELECT id, role FROM users WHERE api_key = $1 AND state = 'active'", apiKey).Scan(&userID, &userRole)
			if err != nil {
				http.Error(w, `{"error": "unauthorized"}`, http.StatusUnauthorized)
				return
			}

			// Load allowed workspaces for this user
			rows, err := db.Query(r.Context(), "SELECT workspace_id FROM user_workspaces WHERE user_id = $1", userID)
			var wsIDs []uuid.UUID
			if err == nil {
				defer rows.Close()
				for rows.Next() {
					var id uuid.UUID
					if err := rows.Scan(&id); err == nil {
						wsIDs = append(wsIDs, id)
					}
				}
			}

			claims := &UserClaims{
				UserID:      userID,
				Role:        userRole,
				WorkspaceIDs: wsIDs,
			}

			ctx := context.WithValue(r.Context(), UserClaimsKey, claims)
			next.ServeHTTP(w, r.WithContext(ctx))
		})
	}
}
