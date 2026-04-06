/*
 * MODIFIED: internal/cache/cache.go
 * PURPOSE: Implemented entity caching with Postgres fallbacks for offers/landings.
 *          Added documentation header and improved warmup error handling.
 */
package cache

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/model"
)

// Cache handles preloading and retrieving entities from Valkey.
// It acts as a bypass to PostgreSQL for the click hot path.
type Cache struct {
	vk     *redis.Client
	db     *pgxpool.Pool
	logger *zap.Logger
}

// New creates a new Cache service.
func New(vk *redis.Client, db *pgxpool.Pool, logger *zap.Logger) *Cache {
	return &Cache{
		vk:     vk,
		db:     db,
		logger: logger,
	}
}

// Warmup preloads all active campaigns, streams, offers, and landings into Valkey.
func (c *Cache) Warmup(ctx context.Context) error {
	start := time.Now()
	c.logger.Info("starting cache warmup")

	// 1. Load all active campaigns
	rows, err := c.db.Query(ctx, `
		SELECT id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id
		FROM campaigns
		WHERE state = 'active'
	`)
	if err != nil {
		return fmt.Errorf("query campaigns: %w", err)
	}
	defer rows.Close()

	var campaigns []model.Campaign
	for rows.Next() {
		var camp model.Campaign
		if err := rows.Scan(
			&camp.ID, &camp.Alias, &camp.Name, &camp.Type,
			&camp.BindVisitors, &camp.State, &camp.TrafficSourceID, &camp.DefaultStreamID,
		); err != nil {
			return fmt.Errorf("scan campaign: %w", err)
		}
		campaigns = append(campaigns, camp)
	}

	for _, camp := range campaigns {
		// Store campaign data
		campJSON, _ := json.Marshal(camp)
		if err := c.vk.Set(ctx, fmt.Sprintf("campaign:%s", camp.ID), campJSON, time.Hour).Err(); err != nil {
			return fmt.Errorf("set campaign %s: %w", camp.ID, err)
		}
		// Store alias mapping
		if err := c.vk.Set(ctx, fmt.Sprintf("campaign_alias:%s", camp.Alias), camp.ID.String(), time.Hour).Err(); err != nil {
			return fmt.Errorf("set alias %s: %w", camp.Alias, err)
		}

		// 2. Load streams for this campaign
		if err := c.warmupStreams(ctx, camp.ID); err != nil {
			return err
		}
	}

	c.logger.Info("cache warmup complete", zap.Duration("latency", time.Since(start)), zap.Int("campaigns", len(campaigns)))
	return nil
}

func (c *Cache) warmupStreams(ctx context.Context, campaignID uuid.UUID) error {
	rows, err := c.db.Query(ctx, `
		SELECT id, campaign_id, name, type, position, weight, state, action_type, action_payload, filters, daily_limit, total_limit
		FROM streams
		WHERE campaign_id = $1 AND state = 'active'
		ORDER BY position ASC
	`, campaignID)
	if err != nil {
		return fmt.Errorf("query streams for %s: %w", campaignID, err)
	}
	defer rows.Close()

	var streams []model.Stream
	for rows.Next() {
		var s model.Stream
		if err := rows.Scan(
			&s.ID, &s.CampaignID, &s.Name, &s.Type, &s.Position, &s.Weight,
			&s.State, &s.ActionType, &s.ActionPayload, &s.Filters, &s.DailyLimit, &s.TotalLimit,
		); err != nil {
			return fmt.Errorf("scan stream: %w", err)
		}
		streams = append(streams, s)

		// Warmup offers and landings for this stream
		if err := c.warmupStreamEntities(ctx, s.ID); err != nil {
			return err
		}
	}

	if len(streams) > 0 {
		streamsJSON, _ := json.Marshal(streams)
		if err := c.vk.Set(ctx, fmt.Sprintf("streams:%s", campaignID), streamsJSON, time.Hour).Err(); err != nil {
			return fmt.Errorf("set streams for %s: %w", campaignID, err)
		}
	}

	return nil
}

func (c *Cache) warmupStreamEntities(ctx context.Context, streamID uuid.UUID) error {
	// Offers
	offRows, err := c.db.Query(ctx, `
		SELECT o.id, o.name, o.url, o.affiliate_network_id, o.payout, o.state, so.weight
		FROM offers o
		JOIN stream_offers so ON o.id = so.offer_id
		WHERE so.stream_id = $1 AND o.state = 'active'
	`, streamID)
	if err != nil {
		return err
	}
	defer offRows.Close()

	var offers []model.WeightedOffer
	for offRows.Next() {
		var o model.Offer
		var weight int
		if err := offRows.Scan(&o.ID, &o.Name, &o.URL, &o.AffiliateNetworkID, &o.Payout, &o.State, &weight); err != nil {
			return err
		}
		offers = append(offers, model.WeightedOffer{Offer: o, Weight: weight})
	}
	if len(offers) > 0 {
		val, _ := json.Marshal(offers)
		c.vk.Set(ctx, fmt.Sprintf("stream_offers:%s", streamID), val, time.Hour)
	}

	// Landings
	lndRows, err := c.db.Query(ctx, `
		SELECT l.id, l.name, l.url, l.state, sl.weight
		FROM landings l
		JOIN stream_landings sl ON l.id = sl.landing_id
		WHERE sl.stream_id = $1 AND l.state = 'active'
	`, streamID)
	if err != nil {
		return err
	}
	defer lndRows.Close()

	var landings []model.WeightedLanding
	for lndRows.Next() {
		var l model.Landing
		var weight int
		if err := lndRows.Scan(&l.ID, &l.Name, &l.URL, &l.State, &weight); err != nil {
			return err
		}
		landings = append(landings, model.WeightedLanding{Landing: l, Weight: weight})
	}
	if len(landings) > 0 {
		val, _ := json.Marshal(landings)
		c.vk.Set(ctx, fmt.Sprintf("stream_landings:%s", streamID), val, time.Hour)
	}

	return nil
}

// GetCampaignByAlias retrieves a campaign by its alias.
func (c *Cache) GetCampaignByAlias(ctx context.Context, alias string) (*model.Campaign, error) {
	// 1. Try alias mapping
	idStr, err := c.vk.Get(ctx, fmt.Sprintf("campaign_alias:%s", alias)).Result()
	if err == redis.Nil {
		// Fallback to DB (might not be warmed up yet)
		var id uuid.UUID
		err := c.db.QueryRow(ctx, "SELECT id FROM campaigns WHERE alias = $1 AND state = 'active'", alias).Scan(&id)
		if err != nil {
			return nil, nil // Not found
		}
		idStr = id.String()
	} else if err != nil {
		return nil, err
	}

	// 2. Get campaign by ID
	return c.GetCampaignByID(ctx, uuid.MustParse(idStr))
}

// GetCampaignByID retrieves a campaign by its UUID.
func (c *Cache) GetCampaignByID(ctx context.Context, id uuid.UUID) (*model.Campaign, error) {
	val, err := c.vk.Get(ctx, fmt.Sprintf("campaign:%s", id)).Result()
	if err == redis.Nil {
		// Fallback to DB
		var camp model.Campaign
		err := c.db.QueryRow(ctx, `
			SELECT id, alias, name, type, bind_visitors, state, traffic_source_id, default_stream_id
			FROM campaigns WHERE id = $1
		`, id).Scan(&camp.ID, &camp.Alias, &camp.Name, &camp.Type, &camp.BindVisitors, &camp.State, &camp.TrafficSourceID, &camp.DefaultStreamID)
		if err != nil {
			return nil, err
		}
		// Async cache it
		go func() {
			data, _ := json.Marshal(camp)
			c.vk.Set(context.Background(), fmt.Sprintf("campaign:%s", id), data, time.Hour)
			c.vk.Set(context.Background(), fmt.Sprintf("campaign_alias:%s", camp.Alias), id.String(), time.Hour)
		}()
		return &camp, nil
	} else if err != nil {
		return nil, err
	}

	var camp model.Campaign
	if err := json.Unmarshal([]byte(val), &camp); err != nil {
		return nil, err
	}
	return &camp, nil
}

// GetStreamsByCampaign retrieves all active streams for a campaign.
func (c *Cache) GetStreamsByCampaign(ctx context.Context, campaignID uuid.UUID) ([]model.Stream, error) {
	val, err := c.vk.Get(ctx, fmt.Sprintf("streams:%s", campaignID)).Result()
	if err == redis.Nil {
		// Should have been warmed up, but fallback just in case
		rows, err := c.db.Query(ctx, `
			SELECT id, campaign_id, name, type, position, weight, state, action_type, action_payload, filters, daily_limit, total_limit
			FROM streams WHERE campaign_id = $1 AND state = 'active' ORDER BY position ASC
		`, campaignID)
		if err != nil {
			return nil, err
		}
		defer rows.Close()
		var streams []model.Stream
		for rows.Next() {
			var s model.Stream
			if err := rows.Scan(&s.ID, &s.CampaignID, &s.Name, &s.Type, &s.Position, &s.Weight, &s.State, &s.ActionType, &s.ActionPayload, &s.Filters, &s.DailyLimit, &s.TotalLimit); err != nil {
				return nil, err
			}
			streams = append(streams, s)
		}
		return streams, nil
	} else if err != nil {
		return nil, err
	}

	var streams []model.Stream
	if err := json.Unmarshal([]byte(val), &streams); err != nil {
		return nil, err
	}
	return streams, nil
}

// GetOffersByStream retrieves weighted offers for a stream.
func (c *Cache) GetOffersByStream(ctx context.Context, streamID uuid.UUID) ([]model.WeightedOffer, error) {
	val, err := c.vk.Get(ctx, fmt.Sprintf("stream_offers:%s", streamID)).Result()
	if err == redis.Nil {
		// Fallback to DB
		offRows, err := c.db.Query(ctx, `
			SELECT o.id, o.name, o.url, o.affiliate_network_id, o.payout, o.state, so.weight
			FROM offers o JOIN stream_offers so ON o.id = so.offer_id
			WHERE so.stream_id = $1 AND o.state = 'active'
		`, streamID)
		if err != nil {
			return nil, err
		}
		defer offRows.Close()
		var offers []model.WeightedOffer
		for offRows.Next() {
			var o model.Offer
			var weight int
			if err := offRows.Scan(&o.ID, &o.Name, &o.URL, &o.AffiliateNetworkID, &o.Payout, &o.State, &weight); err != nil {
				return nil, err
			}
			offers = append(offers, model.WeightedOffer{Offer: o, Weight: weight})
		}
		return offers, nil
	} else if err != nil {
		return nil, err
	}
	var offers []model.WeightedOffer
	if err := json.Unmarshal([]byte(val), &offers); err != nil {
		return nil, err
	}
	return offers, nil
}

// GetLandingsByStream retrieves weighted landings for a stream.
func (c *Cache) GetLandingsByStream(ctx context.Context, streamID uuid.UUID) ([]model.WeightedLanding, error) {
	val, err := c.vk.Get(ctx, fmt.Sprintf("stream_landings:%s", streamID)).Result()
	if err == redis.Nil {
		// Fallback to DB
		lndRows, err := c.db.Query(ctx, `
			SELECT l.id, l.name, l.url, l.state, sl.weight
			FROM landings l JOIN stream_landings sl ON l.id = sl.landing_id
			WHERE sl.stream_id = $1 AND l.state = 'active'
		`, streamID)
		if err != nil {
			return nil, err
		}
		defer lndRows.Close()
		var landings []model.WeightedLanding
		for lndRows.Next() {
			var l model.Landing
			var weight int
			if err := lndRows.Scan(&l.ID, &l.Name, &l.URL, &l.State, &weight); err != nil {
				return nil, err
			}
			landings = append(landings, model.WeightedLanding{Landing: l, Weight: weight})
		}
		return landings, nil
	} else if err != nil {
		return nil, err
	}
	var landings []model.WeightedLanding
	if err := json.Unmarshal([]byte(val), &landings); err != nil {
		return nil, err
	}
	return landings, nil
}

// GetTrafficSource retrieves a traffic source by ID.
func (c *Cache) GetTrafficSource(ctx context.Context, id uuid.UUID) (*model.TrafficSource, error) {
	val, err := c.vk.Get(ctx, fmt.Sprintf("source:%s", id)).Result()
	if err == redis.Nil {
		var s model.TrafficSource
		err := c.db.QueryRow(ctx, "SELECT id, name, postback_url, params, state FROM traffic_sources WHERE id = $1", id).Scan(&s.ID, &s.Name, &s.PostbackURL, &s.Params, &s.State)
		if err != nil {
			return nil, err
		}
		data, _ := json.Marshal(s)
		c.vk.Set(ctx, fmt.Sprintf("source:%s", id), data, time.Hour)
		return &s, nil
	} else if err != nil {
		return nil, err
	}
	var s model.TrafficSource
	if err := json.Unmarshal([]byte(val), &s); err != nil {
		return nil, err
	}
	return &s, nil
}

// GetAffiliateNetwork retrieves an affiliate network by ID.
func (c *Cache) GetAffiliateNetwork(ctx context.Context, id uuid.UUID) (*model.AffiliateNetwork, error) {
	val, err := c.vk.Get(ctx, fmt.Sprintf("network:%s", id)).Result()
	if err == redis.Nil {
		var n model.AffiliateNetwork
		err := c.db.QueryRow(ctx, "SELECT id, name, postback_url, state FROM affiliate_networks WHERE id = $1", id).Scan(&n.ID, &n.Name, &n.PostbackURL, &n.State)
		if err != nil {
			return nil, err
		}
		data, _ := json.Marshal(n)
		c.vk.Set(ctx, fmt.Sprintf("network:%s", id), data, time.Hour)
		return &n, nil
	} else if err != nil {
		return nil, err
	}
	var n model.AffiliateNetwork
	if err := json.Unmarshal([]byte(val), &n); err != nil {
		return nil, err
	}
	return &n, nil
}

// InvalidateCampaign evicts campaign data from Valkey.
func (c *Cache) InvalidateCampaign(ctx context.Context, campaignID uuid.UUID) error {
	c.vk.Del(ctx, fmt.Sprintf("campaign:%s", campaignID))
	c.vk.Del(ctx, fmt.Sprintf("streams:%s", campaignID))
	return nil
}

// GetCampaign is an alias for GetCampaignByID.
func (c *Cache) GetCampaign(ctx context.Context, id uuid.UUID) (*model.Campaign, error) {
	return c.GetCampaignByID(ctx, id)
}

// ScheduleWarmup sets a flag in Valkey indicating warmup is needed.
// Called by admin handlers after any entity mutation.
func (c *Cache) ScheduleWarmup() {
	c.vk.Set(context.Background(), "warmup:scheduled", "1", 30*time.Second)
}

// GetStream retrieves a single stream by its UUID.
func (c *Cache) GetStream(ctx context.Context, id uuid.UUID) (*model.Stream, error) {
	val, err := c.vk.Get(ctx, fmt.Sprintf("stream:%s", id)).Result()
	if err == redis.Nil {
		// Fallback to DB
		var s model.Stream
		err := c.db.QueryRow(ctx, `
			SELECT id, campaign_id, name, type, position, weight, state, action_type, action_payload, filters, daily_limit, total_limit
			FROM streams WHERE id = $1
		`, id).Scan(&s.ID, &s.CampaignID, &s.Name, &s.Type, &s.Position, &s.Weight, &s.State, &s.ActionType, &s.ActionPayload, &s.Filters, &s.DailyLimit, &s.TotalLimit)
		if err != nil {
			return nil, err
		}
		// Cache it
		go func() {
			data, _ := json.Marshal(s)
			c.vk.Set(context.Background(), fmt.Sprintf("stream:%s", id), data, time.Hour)
		}()
		return &s, nil
	} else if err != nil {
		return nil, err
	}
	var s model.Stream
	if err := json.Unmarshal([]byte(val), &s); err != nil {
		return nil, err
	}
	return &s, nil
}

// GetCampaignByDomain resolves a bare domain to its mapped campaign.
// Used for gateway context (bare domain hits without an alias).
func (c *Cache) GetCampaignByDomain(ctx context.Context, host string) (*model.Campaign, error) {
	// 1. Check Valkey
	idStr, err := c.vk.Get(ctx, fmt.Sprintf("domain:%s", host)).Result()
	if err == redis.Nil {
		// 2. Fallback to DB
		var campID uuid.UUID
		err := c.db.QueryRow(ctx, "SELECT campaign_id FROM domains WHERE domain = $1", host).Scan(&campID)
		if err != nil {
			return nil, nil // No mapping
		}
		// Cache it
		c.vk.Set(ctx, fmt.Sprintf("domain:%s", host), campID.String(), time.Hour)
		return c.GetCampaignByID(ctx, campID)
	} else if err != nil {
		return nil, err
	}
	return c.GetCampaignByID(ctx, uuid.MustParse(idStr))
}
