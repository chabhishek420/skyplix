package stage

import (
	"context"

	"github.com/jackc/pgx/v5/pgxpool"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// FindCampaignStage — Pipeline Stage 4
// Looks up the campaign from the database by alias.
// Phase 1: direct PostgreSQL query (Phase 2 will add Valkey cache).
type FindCampaignStage struct {
	DB     *pgxpool.Pool
	Logger *zap.Logger
}

func (s *FindCampaignStage) Name() string { return "FindCampaign" }

func (s *FindCampaignStage) Process(payload *pipeline.Payload) error {
	alias := payload.RawClick.CampaignAlias
	if alias == "" {
		// Bare domain — no alias, will be handled by domain → campaign mapping (Phase 2)
		payload.Abort = true
		payload.AbortCode = 404
		return nil
	}

	campaign, err := s.findByAlias(payload.Ctx, alias)
	if err != nil {
		s.Logger.Error("campaign lookup failed", zap.String("alias", alias), zap.Error(err))
		payload.Abort = true
		payload.AbortCode = 500
		return nil
	}

	if campaign == nil {
		s.Logger.Debug("campaign not found", zap.String("alias", alias))
		payload.Abort = true
		payload.AbortCode = 404
		return nil
	}

	payload.Campaign = campaign
	payload.RawClick.CampaignID = campaign.ID
	return nil
}

// findByAlias queries PostgreSQL for a campaign by alias.
func (s *FindCampaignStage) findByAlias(ctx context.Context, alias string) (*model.Campaign, error) {
	row := s.DB.QueryRow(ctx, `
		SELECT id, alias, name, type, bind_visitors, state, default_stream_id
		FROM campaigns
		WHERE alias = $1 AND state = 'active'
		LIMIT 1
	`, alias)

	var c model.Campaign
	var defaultStreamID *string
	err := row.Scan(
		&c.ID,
		&c.Alias,
		&c.Name,
		&c.Type,
		&c.BindVisitors,
		&c.State,
		&defaultStreamID,
	)
	if err != nil {
		// pgx returns pgx.ErrNoRows when nothing is found
		if err.Error() == "no rows in result set" {
			return nil, nil
		}
		return nil, err
	}

	return &c, nil
}
