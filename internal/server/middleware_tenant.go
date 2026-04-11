package server

import (
	"context"
	"net/http"
	"strings"

	"github.com/skyplix/zai-tds/internal/auth"
	"github.com/skyplix/zai-tds/internal/model"
)

type tenantContextKey struct{}

var requestTenantContextKey = tenantContextKey{}

const (
	// TenantIDHeader is the canonical header used to pass tenant identity.
	TenantIDHeader = "X-Tenant-ID"
)

// TenantContextMiddleware resolves tenant identity and injects it into request context.
// It rejects requests that do not carry tenant context.
func TenantContextMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		tenantID, ok := tenantIDFromRequest(r)
		if !ok {
			http.Error(w, `{"error":"missing tenant context"}`, http.StatusUnauthorized)
			return
		}

		tenant := model.TenantContext{ID: tenantID}
		ctx := context.WithValue(r.Context(), requestTenantContextKey, tenant)
		next.ServeHTTP(w, r.WithContext(ctx))
	})
}

// TenantIDFromContext reads tenant identity from request context.
func TenantIDFromContext(ctx context.Context) (string, bool) {
	if ctx == nil {
		return "", false
	}

	switch v := ctx.Value(requestTenantContextKey).(type) {
	case model.TenantContext:
		return normalizedTenantID(v.ID)
	case *model.TenantContext:
		if v == nil {
			return "", false
		}
		return normalizedTenantID(v.ID)
	case string:
		return normalizedTenantID(v)
	default:
		return "", false
	}
}

func tenantIDFromRequest(r *http.Request) (string, bool) {
	if r == nil {
		return "", false
	}

	if id, ok := normalizedTenantID(r.Header.Get(TenantIDHeader)); ok {
		return id, true
	}

	if id, ok := normalizedTenantID(r.URL.Query().Get("tenant_id")); ok {
		return id, true
	}

	if userID, ok := r.Context().Value(auth.UserIDKey).(string); ok {
		if id, ok := normalizedTenantID(userID); ok {
			return id, true
		}
	}

	return "", false
}

func normalizedTenantID(raw string) (string, bool) {
	id := strings.TrimSpace(raw)
	if id == "" {
		return "", false
	}
	return id, true
}
