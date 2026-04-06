/*
 * MODIFIED: internal/pipeline/stage/l2_find_campaign.go
 * PURPOSE: Resolves Level 1 context from LP token. Added deep diagnostics
 *          and fixed click token population for L2 processing.
 */
package stage

import (
	"fmt"
	"strings"

	"github.com/go-chi/chi/v5"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/lptoken"
	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// L2FindCampaignStage resolves campaign/stream from an LP token (L2 click).
type L2FindCampaignStage struct {
	LPToken *lptoken.Service
	Cache   *cache.Cache
	Logger  *zap.Logger
}

func (s *L2FindCampaignStage) Name() string    { return "L2FindCampaign" }
func (s *L2FindCampaignStage) AlwaysRun() bool { return false }

func (s *L2FindCampaignStage) Process(p *pipeline.Payload) error {
	// 1. Determine LP token from URL or Query
	token := chi.URLParam(p.Request, "token")
	if token == "" {
		// Fallback 1: Query parameters (common for JS integration)
		rawQuery := p.Request.URL.RawQuery
		token = getQueryParam(rawQuery, "sub_id", "click_token")
		if token == "" {
			token = getQueryParam(rawQuery, "clickid", "click_id")
		}
	}
	if token == "" {
		// Fallback 2: Brute force parse from /lp/{token}/click
		parts := strings.Split(p.Request.URL.Path, "/")
		if len(parts) >= 3 && parts[1] == "lp" {
			token = parts[2]
		}
	}
	
	if token == "" {
		s.Logger.Warn("L2 click missing token in URL or query", zap.String("path", p.Request.URL.Path))
		p.Abort = true
		p.AbortCode = 400
		return nil
	}

	fmt.Printf(">>> L2 CLICK: token=%s path=%s\n", token, p.Request.URL.Path)

	// 1. Resolve token from Valkey
	lpCtx, err := s.LPToken.Resolve(p.Ctx, token)
	if err != nil {
		s.Logger.Error("failed to resolve LP token", zap.Error(err), zap.String("token", token))
		return fmt.Errorf("resolve lp token: %w", err)
	}
	if lpCtx == nil {
		s.Logger.Warn("LP token not found or expired", zap.String("token", token))
		p.Abort = true
		p.AbortCode = 404
		return nil
	}

	// 2. Hydrate campaign and stream
	camp, err := s.Cache.GetCampaignByID(p.Ctx, lpCtx.CampaignID)
	if err != nil {
		return fmt.Errorf("get campaign: %w", err)
	}
	stream, err := s.Cache.GetStream(p.Ctx, lpCtx.StreamID)
	if err != nil {
		return fmt.Errorf("get stream: %w", err)
	}

	if camp == nil || stream == nil {
		s.Logger.Error("LP context refers to non-existent entities", 
			zap.String("token", token),
			zap.String("campaign_id", lpCtx.CampaignID.String()),
			zap.String("stream_id", lpCtx.StreamID.String()),
		)
		p.Abort = true
		p.AbortCode = 404
		return nil
	}

	s.Logger.Debug("L2 context resolved", 
		zap.String("token", token),
		zap.String("campaign", camp.Name),
		zap.String("stream", stream.Name),
	)

	p.Campaign = camp
	p.Stream = stream
	if p.RawClick == nil {
		p.RawClick = &model.RawClick{}
	}
	p.RawClick.ClickToken = token
	p.VisitorCode = lpCtx.VisitorCode
	p.RawClick.SubID1 = lpCtx.SubID1
	p.RawClick.SubID2 = lpCtx.SubID2
	p.RawClick.SubID3 = lpCtx.SubID3
	p.RawClick.SubID4 = lpCtx.SubID4
	p.RawClick.SubID5 = lpCtx.SubID5

	return nil
}
