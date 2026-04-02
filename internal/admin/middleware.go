package admin

import (
	"context"
	"net/http"

	"github.com/jackc/pgx/v5/pgxpool"
)

type contextKey string

const (
	UserIDKey contextKey = "user_id"
	UserRoleKey contextKey = "user_role"
)

// APIKeyAuth is a middleware that validates the X-Api-Key header.
func APIKeyAuth(db *pgxpool.Pool) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			apiKey := r.Header.Get("X-Api-Key")
			if apiKey == "" {
				http.Error(w, `{"error": "missing API key"}`, http.StatusUnauthorized)
				return
			}

			var userID string
			var userRole string
			err := db.QueryRow(r.Context(), "SELECT id, role FROM users WHERE api_key = $1 AND state = 'active'", apiKey).Scan(&userID, &userRole)
			if err != nil {
				http.Error(w, `{"error": "unauthorized"}`, http.StatusUnauthorized)
				return
			}

			ctx := context.WithValue(r.Context(), UserIDKey, userID)
			ctx = context.WithValue(ctx, UserRoleKey, userRole)
			next.ServeHTTP(w, r.WithContext(ctx))
		})
	}
}
