package bench

import (
	"context"
	"net"
	"net/http"
	"net/url"
	"testing"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/pipeline/stage"
)

func BenchmarkBuildRawClickStage(b *testing.B) {
	s := &stage.BuildRawClickStage{}
	req := &http.Request{
		Header: http.Header{"User-Agent": {"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}},
		URL:    &url.URL{RawQuery: "sub1=test&sub2=test2&sub3=test3"},
	}

	p := &pipeline.Payload{
		Ctx:     context.Background(),
		Request: req,
		RawClick: &model.RawClick{
			IP: net.ParseIP("8.8.8.8"),
		},
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		_ = s.Process(p)
	}
}
