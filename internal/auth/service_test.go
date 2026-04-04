package auth

import (
	"context"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/pashagolub/pgxmock/v3"
)

func TestAuthService_Middleware(t *testing.T) {
	mock, err := pgxmock.NewPool()
	if err != nil {
		t.Fatal(err)
	}
	defer mock.Close()

	secret := "test-secret"
	s := NewService(mock, secret)

	nextHandler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	})

	middleware := s.Middleware(nextHandler)

	t.Run("NoAuth", func(t *testing.T) {
		w := httptest.NewRecorder()
		r := httptest.NewRequest("GET", "/api/v1/campaigns", nil)
		middleware.ServeHTTP(w, r)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("ValidJWT", func(t *testing.T) {
		token, _ := s.GenerateToken("user-1", "admin")
		w := httptest.NewRecorder()
		r := httptest.NewRequest("GET", "/api/v1/campaigns", nil)
		r.Header.Set("Authorization", "Bearer "+token)
		middleware.ServeHTTP(w, r)
		assert.Equal(t, http.StatusOK, w.Code)
	})

	t.Run("InvalidJWT", func(t *testing.T) {
		w := httptest.NewRecorder()
		r := httptest.NewRequest("GET", "/api/v1/campaigns", nil)
		r.Header.Set("Authorization", "Bearer invalid")
		middleware.ServeHTTP(w, r)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("ValidXAPIKey", func(t *testing.T) {
		apiKey := "test-api-key"
		mock.ExpectQuery("SELECT id, role FROM users").
			WithArgs(apiKey).
			WillReturnRows(pgxmock.NewRows([]string{"id", "role"}).AddRow("user-1", "admin"))

		w := httptest.NewRecorder()
		r := httptest.NewRequest("GET", "/api/v1/campaigns", nil)
		r.Header.Set("X-Api-Key", apiKey)
		middleware.ServeHTTP(w, r)
		assert.Equal(t, http.StatusOK, w.Code)
		assert.NoError(t, mock.ExpectationsWereMet())
	})
}

func TestAuthService_Login(t *testing.T) {
	mock, err := pgxmock.NewPool()
	if err != nil {
		t.Fatal(err)
	}
	defer mock.Close()

	s := NewService(mock, "secret")
	apiKey := "test-key"

	mock.ExpectQuery("SELECT id, role FROM users").
		WithArgs(apiKey).
		WillReturnRows(pgxmock.NewRows([]string{"id", "role"}).AddRow("user-1", "admin"))

	token, err := s.Login(context.Background(), apiKey)
	assert.NoError(t, err)
	assert.NotEmpty(t, token)

	claims, err := s.ValidateToken(token)
	assert.NoError(t, err)
	assert.Equal(t, "user-1", claims.UserID)
	assert.Equal(t, "admin", claims.Role)
}
