package pipeline_test

import (
	"context"
	"net/http/httptest"
	"testing"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/pipeline"
	"github.com/skyplix/zai-tds/internal/pipeline/stage"
)

func BenchmarkPipeline_Run_Pooled(b *testing.B) {
	logger := zap.NewNop()
	p := pipeline.New(
		&stage.NormalizeIPStage{},
		&stage.DomainRedirectStage{},
		&stage.GenerateTokenStage{},
		&stage.BuildRawClickStage{},
		&stage.UpdateRawClickStage{Logger: logger},
	)

	req := httptest.NewRequest("GET", "/test-alias?sub1=abc&cost=0.01", nil)
	req.Header.Set("User-Agent", "Mozilla/5.0")
	req.RemoteAddr = "1.2.3.4:1234"

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		payload := p.GetPayload(context.Background(), req, httptest.NewRecorder())
		_ = p.Run(payload)
		p.PutPayload(payload)
	}
}
