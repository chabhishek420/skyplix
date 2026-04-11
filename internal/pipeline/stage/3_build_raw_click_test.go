package stage

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

func TestBuildRawClickStage_SourceFromSiteParam(t *testing.T) {
	s := &BuildRawClickStage{}
	req := httptest.NewRequest(http.MethodGet, "http://tracker.local/click?site=fb-network", nil)
	req.Header.Set("User-Agent", "Mozilla/5.0")

	payload := &pipeline.Payload{Request: req, RawClick: &model.RawClick{}}
	if err := s.Process(payload); err != nil {
		t.Fatalf("Process failed: %v", err)
	}

	if payload.RawClick.Source != "fb-network" {
		t.Fatalf("expected source from site alias, got %q", payload.RawClick.Source)
	}
}

func TestBuildRawClickStage_SourceFromReferrerHostFallback(t *testing.T) {
	s := &BuildRawClickStage{}
	req := httptest.NewRequest(http.MethodGet, "http://tracker.local/click", nil)
	req.Header.Set("User-Agent", "Mozilla/5.0")
	req.Header.Set("Referer", "https://news.example.com/article?id=7")

	payload := &pipeline.Payload{Request: req, RawClick: &model.RawClick{}}
	if err := s.Process(payload); err != nil {
		t.Fatalf("Process failed: %v", err)
	}

	if payload.RawClick.Source != "news.example.com" {
		t.Fatalf("expected source from referrer host, got %q", payload.RawClick.Source)
	}
}

func TestBuildRawClickStage_ReferrerOverrideUpdatesSourceFallback(t *testing.T) {
	s := &BuildRawClickStage{AllowChangeReferrer: true}
	req := httptest.NewRequest(http.MethodGet, "http://tracker.local/click?referrer=https%3A%2F%2Fads.example.net%2Fland", nil)
	req.Header.Set("User-Agent", "Mozilla/5.0")
	req.Header.Set("Referer", "https://origin.example.org")

	payload := &pipeline.Payload{Request: req, RawClick: &model.RawClick{}}
	if err := s.Process(payload); err != nil {
		t.Fatalf("Process failed: %v", err)
	}

	if payload.RawClick.Referrer != "https://ads.example.net/land" {
		t.Fatalf("expected overridden referrer, got %q", payload.RawClick.Referrer)
	}
	if payload.RawClick.Source != "ads.example.net" {
		t.Fatalf("expected source from overridden referrer host, got %q", payload.RawClick.Source)
	}
}
