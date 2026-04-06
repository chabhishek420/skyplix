package admin

import (
	"context"
	"net/http"
	"strings"

	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/skyplix/zai-tds/internal/auth"
)

type contextKey string

const (
	UserIDKey contextKey = "user_id"
	UserRoleKey contextKey = "user_role"
)

// APIKeyAuth is a middleware that validates either the X-Api-Key header or a JWT Bearer token.
func APIKeyAuth(db *pgxpool.Pool, jwtManager *auth.JWTManager) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			// 1. Check for API Key
			apiKey := r.Header.Get("X-Api-Key")
			if apiKey != "" {
				var userID string
				var userRole string
				err := db.QueryRow(r.Context(), "SELECT id, role FROM users WHERE api_key = $1 AND state = 'active'", apiKey).Scan(&userID, &userRole)
				if err == nil {
					ctx := context.WithValue(r.Context(), UserIDKey, userID)
					ctx = context.WithValue(ctx, UserRoleKey, userRole)
					next.ServeHTTP(w, r.WithContext(ctx))
					return
				}
			}

			// 2. Check for JWT Bearer Token
			authHeader := r.Header.Get("Authorization")
			if strings.HasPrefix(authHeader, "Bearer ") {
				tokenStr := strings.TrimPrefix(authHeader, "Bearer ")
				claims, err := jwtManager.Verify(tokenStr)
				if err == nil {
					ctx := context.WithValue(r.Context(), UserIDKey, claims.UserID)
					ctx = context.WithValue(ctx, UserRoleKey, claims.Role)
					next.ServeHTTP(w, r.WithContext(ctx))
					return
				}
			}

			http.Error(w, `{"error": "unauthorized"}`, http.StatusUnauthorized)
		})
	}
}
