package server_test

import (
	"context"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/skyplix/zai-tds/internal/auth"
	"github.com/skyplix/zai-tds/internal/server"
)

func TestTenantContextMiddleware_ResolvesTenantContext(t *testing.T) {
	tests := []struct {
		name         string
		path         string
		headerTenant string
		authUserID   string
		wantTenant   string
		wantStatus   int
	}{
		{
			name:         "header takes precedence",
			path:         "/api/v1/campaigns?tenant_id=query-tenant",
			headerTenant: "header-tenant",
			authUserID:   "auth-tenant",
			wantTenant:   "header-tenant",
			wantStatus:   http.StatusNoContent,
		},
		{
			name:       "query tenant fallback",
			path:       "/api/v1/campaigns?tenant_id=query-tenant",
			wantTenant: "query-tenant",
			wantStatus: http.StatusNoContent,
		},
		{
			name:       "auth user fallback",
			path:       "/api/v1/campaigns",
			authUserID: "auth-tenant",
			wantTenant: "auth-tenant",
			wantStatus: http.StatusNoContent,
		},
		{
			name:         "tenant id is trimmed",
			path:         "/api/v1/campaigns",
			headerTenant: "  tenant-with-space  ",
			wantTenant:   "tenant-with-space",
			wantStatus:   http.StatusNoContent,
		},
		{
			name:       "missing tenant context is rejected",
			path:       "/api/v1/campaigns",
			wantStatus: http.StatusUnauthorized,
		},
		{
			name:         "malformed blank tenant context is rejected",
			path:         "/api/v1/campaigns?tenant_id=%20%20%20",
			headerTenant: "   ",
			authUserID:   "   ",
			wantStatus:   http.StatusUnauthorized,
		},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			called := false
			next := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
				called = true
				tenantID, ok := server.TenantIDFromContext(r.Context())
				if !ok {
					t.Fatal("expected tenant context to be present")
				}
				if tenantID != tc.wantTenant {
					t.Fatalf("expected tenant %q, got %q", tc.wantTenant, tenantID)
				}
				w.WriteHeader(http.StatusNoContent)
			})

			mw := server.TenantContextMiddleware(next)
			req := httptest.NewRequest(http.MethodGet, tc.path, nil)
			if tc.headerTenant != "" {
				req.Header.Set(server.TenantIDHeader, tc.headerTenant)
			}
			if tc.authUserID != "" {
				req = req.WithContext(context.WithValue(req.Context(), auth.UserIDKey, tc.authUserID))
			}

			resp := httptest.NewRecorder()
			mw.ServeHTTP(resp, req)

			if resp.Code != tc.wantStatus {
				t.Fatalf("expected status %d, got %d", tc.wantStatus, resp.Code)
			}

			if tc.wantStatus == http.StatusNoContent {
				if !called {
					t.Fatal("expected downstream handler to be called")
				}
				return
			}

			if called {
				t.Fatal("did not expect downstream handler to be called")
			}
			if !strings.Contains(resp.Body.String(), "missing tenant context") {
				t.Fatalf("expected missing-tenant error body, got %q", resp.Body.String())
			}
		})
	}
}

func TestTenantIDFromContext_NilContext(t *testing.T) {
	_, ok := server.TenantIDFromContext(nil)
	if ok {
		t.Fatal("expected nil context to return false")
	}
}
