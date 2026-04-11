package filter

import (
	"strings"

	"github.com/skyplix/zai-tds/internal/model"
)

type IsBotFilter struct{}

func (f *IsBotFilter) Type() string { return "IsBot" }
func (f *IsBotFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if val, ok := payload["is_bot"].(bool); ok {
		return rc.IsBot == val
	}
	if val, ok := payload["is_bot"].(string); ok {
		expected := strings.ToLower(val) == "true"
		return rc.IsBot == expected
	}
	return !rc.IsBot
}

type HideClickDetectFilter struct{}

func (f *HideClickDetectFilter) Type() string { return "HideClickDetect" }
func (f *HideClickDetectFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if rc.IsBot || rc.IsProxy {
		return false
	}
	if detected, ok := payload["detected"].(bool); ok && detected {
		return false
	}
	if detected, ok := payload["detected"].(string); ok && strings.ToLower(detected) == "true" {
		return false
	}
	if hc, ok := payload["hideclick"].(bool); ok && hc {
		return false
	}
	if hc, ok := payload["hideclick"].(string); ok && strings.ToLower(hc) == "true" {
		return false
	}
	return true
}

type ImkloDetectFilter struct{}

func (f *ImkloDetectFilter) Type() string { return "ImkloDetect" }
func (f *ImkloDetectFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if rc.IsBot {
		return false
	}
	if score, ok := payload["bot_score"].(float64); ok && score > 0.7 {
		return false
	}
	if isBot, ok := payload["is_bot"].(bool); ok && isBot {
		return false
	}
	if isBot, ok := payload["is_bot"].(string); ok && strings.ToLower(isBot) == "true" {
		return false
	}
	if detected, ok := payload["imklo_detected"].(bool); ok && detected {
		return false
	}
	if detected, ok := payload["imklo_detected"].(string); ok && strings.ToLower(detected) == "true" {
		return false
	}
	return true
}

type JsFingerprintFilter struct{}

func (f *JsFingerprintFilter) Type() string { return "JsFingerprint" }
func (f *JsFingerprintFilter) Match(rc *model.RawClick, payload map[string]interface{}) bool {
	if rc.IsBot {
		return false
	}
	if score, ok := payload["fingerprint_score"].(float64); ok && score > 0.8 {
		return false
	}
	if detected, ok := payload["js_detected"].(bool); ok && detected {
		return false
	}
	if detected, ok := payload["js_detected"].(string); ok && strings.ToLower(detected) == "true" {
		return false
	}
	if behavioral, ok := payload["behavioral_score"].(float64); ok && behavioral > 0.75 {
		return false
	}
	return true
}
