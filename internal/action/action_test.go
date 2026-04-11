package action

import "testing"

func TestEngineGetSupportsCanonicalAliases(t *testing.T) {
	e := NewEngine()

	tests := map[string]string{
		"BlankReferrer":  "BlankReferrer",
		"blank_referrer": "BlankReferrer",
		"blank-referrer": "BlankReferrer",
		"blank referrer": "BlankReferrer",
		"http_redirect":  "HttpRedirect",
		"http":           "HttpRedirect",
		"location":       "HttpRedirect",
		"campaign":       "ToCampaign",
		"group":          "ToCampaign",
		"status_404":     "Status404",
		"safe_page":      "SafePage",
	}

	for input, expectedType := range tests {
		a, ok := e.Get(input)
		if !ok {
			t.Fatalf("expected action lookup to succeed for %q", input)
		}
		if a.Type() != expectedType {
			t.Fatalf("expected action type %q for %q, got %q", expectedType, input, a.Type())
		}
	}
}

func TestIsRedirectActionType(t *testing.T) {
	redirectCases := []string{"http", "HttpRedirect", "blank_referrer", "js_for_iframe", "remote", "curl", "frame"}
	for _, actionType := range redirectCases {
		if !IsRedirectActionType(actionType) {
			t.Fatalf("expected %q to be treated as redirect action", actionType)
		}
	}

	nonRedirectCases := []string{"status404", "do_nothing", "campaign", "sub_id", "show_text"}
	for _, actionType := range nonRedirectCases {
		if IsRedirectActionType(actionType) {
			t.Fatalf("expected %q to be treated as non-redirect action", actionType)
		}
	}
}

func TestEngineGetUnknownAction(t *testing.T) {
	e := NewEngine()
	if _, ok := e.Get("this_does_not_exist"); ok {
		t.Fatal("expected unknown action lookup to fail")
	}
}
