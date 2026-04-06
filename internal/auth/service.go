package auth

import (
	"context"
	"errors"
	"fmt"
	"net/http"
	"strings"
	"time"

	"github.com/golang-jwt/jwt/v5"
	"github.com/jackc/pgx/v5"
)

type contextKey string

const (
	UserIDKey   contextKey = "user_id"
	UserRoleKey contextKey = "user_role"
)

// Claims represents the JWT claims.
type Claims struct {
	UserID string `json:"user_id"`
	Role   string `json:"role"`
	jwt.RegisteredClaims
}

// DBInterface defines the database operations needed for auth.
type DBInterface interface {
	QueryRow(ctx context.Context, sql string, args ...any) pgx.Row
}

// Service handles authentication and JWT token management.
type Service struct {
	db     DBInterface
	secret []byte
}

// NewService creates a new auth service.
func NewService(db DBInterface, secret string) *Service {
	return &Service{
		db:     db,
		secret: []byte(secret),
	}
}

// GenerateToken creates a new JWT token for a user.
func (s *Service) GenerateToken(userID, role string) (string, error) {
	claims := Claims{
		UserID: userID,
		Role:   role,
		RegisteredClaims: jwt.RegisteredClaims{
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(24 * time.Hour)),
			IssuedAt:  jwt.NewNumericDate(time.Now()),
		},
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	return token.SignedString(s.secret)
}

// ValidateToken parses and validates a JWT token.
func (s *Service) ValidateToken(tokenStr string) (*Claims, error) {
	token, err := jwt.ParseWithClaims(tokenStr, &Claims{}, func(token *jwt.Token) (interface{}, error) {
		if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
			return nil, fmt.Errorf("unexpected signing method: %v", token.Header["alg"])
		}
		return s.secret, nil
	})

	if err != nil {
		return nil, err
	}

	if claims, ok := token.Claims.(*Claims); ok && token.Valid {
		return claims, nil
	}

	return nil, errors.New("invalid token")
}

// Login authenticates a user via API key (memory-auth style for now) and returns a JWT.
func (s *Service) Login(ctx context.Context, apiKey string) (string, error) {
	var userID, role string
	err := s.db.QueryRow(ctx, "SELECT id, role FROM users WHERE api_key = $1 AND state = 'active'", apiKey).Scan(&userID, &role)
	if err != nil {
		return "", errors.New("unauthorized")
	}

	return s.GenerateToken(userID, role)
}

// Middleware provides JWT authentication for routes.
func (s *Service) Middleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		authHeader := r.Header.Get("Authorization")
		if authHeader == "" {
			// Fallback to X-Api-Key for backward compatibility with automated tools
			apiKey := r.Header.Get("X-Api-Key")
			if apiKey != "" {
				var userID, role string
				err := s.db.QueryRow(r.Context(), "SELECT id, role FROM users WHERE api_key = $1 AND state = 'active'", apiKey).Scan(&userID, &role)
				if err == nil {
					ctx := context.WithValue(r.Context(), UserIDKey, userID)
					ctx = context.WithValue(ctx, UserRoleKey, role)
					next.ServeHTTP(w, r.WithContext(ctx))
					return
				}
			}
			http.Error(w, `{"error": "unauthorized"}`, http.StatusUnauthorized)
			return
		}

		parts := strings.Split(authHeader, " ")
		if len(parts) != 2 || strings.ToLower(parts[0]) != "bearer" {
			http.Error(w, `{"error": "invalid auth header"}`, http.StatusUnauthorized)
			return
		}

		claims, err := s.ValidateToken(parts[1])
		if err != nil {
			http.Error(w, `{"error": "invalid token"}`, http.StatusUnauthorized)
			return
		}

		ctx := context.WithValue(r.Context(), UserIDKey, claims.UserID)
		ctx = context.WithValue(ctx, UserRoleKey, claims.Role)
		next.ServeHTTP(w, r.WithContext(ctx))
	})
}
