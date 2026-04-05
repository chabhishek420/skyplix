package admin

import (
	"net/http"

	"github.com/skyplix/zai-tds/internal/admin/repository"
	"github.com/skyplix/zai-tds/internal/auth"
)

// APIKeyAuth is a proxy to the refined auth package implementation.
func APIKeyAuth(db repository.DB) func(http.Handler) http.Handler {
	return auth.APIKeyAuth(db)
}
