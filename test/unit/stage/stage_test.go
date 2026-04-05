package stage_test

import (
	"net"
	"net/http"
	"net/url"
	"testing"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/pipeline/stage"
)

type mockBotDB struct{}
func (m *mockBotDB) Contains(ip net.IP) bool { return ip.String() == "1.2.3.4" }

func TestBuildRawClickStage(t *testing.T) {
	s := &stage.BuildRawClickStage{
		BotDB: &mockBotDB{},
	}

	t.Run("HumanRequest", func(t *testing.T) {
		req := &http.Request{
			Header: http.Header{"User-Agent": {"Mozilla/5.0"}},
			URL:    &url.URL{RawQuery: "sub1=test_val"},
		}
		p := &pipeline.Payload{
			Request: req,
			RawClick: &model.RawClick{
				IP: net.ParseIP("8.8.8.8"),
			},
		}

		err := s.Process(p)
		if err != nil {
			t.Fatalf("Process failed: %v", err)
		}

		if p.RawClick.IsBot {
			t.Error("Expected human request, got bot")
		}
		if p.RawClick.SubID1 != "test_val" {
			t.Errorf("Expected SubID1 test_val, got %s", p.RawClick.SubID1)
		}
		if p.RawClick.BehaviorScore != 0 {
			t.Errorf("Expected 0 score, got %d", p.RawClick.BehaviorScore)
		}
	})

	t.Run("BotRequest_IPMatch", func(t *testing.T) {
		req := &http.Request{
			Header: http.Header{"User-Agent": {"Mozilla/5.0"}},
			URL:    &url.URL{},
		}
		p := &pipeline.Payload{
			Request: req,
			RawClick: &model.RawClick{
				IP: net.ParseIP("1.2.3.4"),
			},
		}

		_ = s.Process(p)

		if !p.RawClick.IsBot {
			t.Error("Expected bot request (IP match), got human")
		}
		if p.RawClick.BotReason != "bot_db_ip" {
			t.Errorf("Expected reason bot_db_ip, got %s", p.RawClick.BotReason)
		}
		if p.RawClick.BehaviorScore != 100 {
			t.Errorf("Expected 100 score, got %d", p.RawClick.BehaviorScore)
		}
	})

	t.Run("BotRequest_UAPattern", func(t *testing.T) {
		req := &http.Request{
			Header: http.Header{"User-Agent": {"Googlebot/2.1"}},
			URL:    &url.URL{},
		}
		p := &pipeline.Payload{
			Request: req,
			RawClick: &model.RawClick{
				IP: net.ParseIP("8.8.8.8"),
			},
		}

		_ = s.Process(p)

		if !p.RawClick.IsBot {
			t.Error("Expected bot request (UA pattern), got human")
		}
		if p.RawClick.BehaviorScore != 90 {
			t.Errorf("Expected 90 score, got %d", p.RawClick.BehaviorScore)
		}
	})
}
