package cookie

import (
	"net/http"

	"github.com/google/uuid"
)

const (
	VisitorCodeCookie = "_zai_vid"
	SessionCookie     = "_zai_sess"
)

// GetOrCreateVisitorCode reads the _zai_vid cookie from the request.
// If not present, generates a new UUID v4 visitor code.
// Returns (visitorCode string, isNew bool).
func GetOrCreateVisitorCode(r *http.Request) (string, bool) {
	cookie, err := r.Cookie(VisitorCodeCookie)
	if err == nil && cookie.Value != "" {
		// Validate that it's a UUID
		if _, err := uuid.Parse(cookie.Value); err == nil {
			return cookie.Value, false
		}
	}

	// Generate new visitor code
	return uuid.New().String(), true
}

// SetVisitorCodeCookie writes the _zai_vid cookie on the response.
// HttpOnly, Secure (if not local), SameSite=Lax, Path=/, MaxAge=2 years.
func SetVisitorCodeCookie(w http.ResponseWriter, visitorCode string) {
	http.SetCookie(w, &http.Cookie{
		Name:     VisitorCodeCookie,
		Value:    visitorCode,
		Path:     "/",
		MaxAge:   63072000, // 2 years
		HttpOnly: true,
		Secure:   false, // Set to true in production/HTTPS
		SameSite: http.SameSiteLaxMode,
	})
}

// SetSessionCookie writes the _zai_sess cookie with a session token.
// HttpOnly, Secure, SameSite=Lax, Path=/, MaxAge=30 minutes.
func SetSessionCookie(w http.ResponseWriter, sessionToken string) {
	http.SetCookie(w, &http.Cookie{
		Name:     SessionCookie,
		Value:    sessionToken,
		Path:     "/",
		MaxAge:   1800, // 30 minutes
		HttpOnly: true,
		Secure:   false,
		SameSite: http.SameSiteLaxMode,
	})
}

// GetVisitorCode reads the _zai_vid cookie. Returns "" if not found.
func GetVisitorCode(r *http.Request) string {
	cookie, err := r.Cookie(VisitorCodeCookie)
	if err != nil {
		return ""
	}
	return cookie.Value
}
