package handler

import (
	"net"

	"github.com/jackc/pgx/v5/pgxpool"
	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/admin/repository"
	"github.com/skyplix/zai-tds/internal/botdb"
	"github.com/skyplix/zai-tds/internal/cache"
)

// Handler holds dependencies for all admin API endpoints.
type Handler struct {
	db        *pgxpool.Pool
	cache     *cache.Cache
	logger    *zap.Logger
	campaigns *repository.CampaignRepository
	streams   *repository.StreamRepository
	offers    *repository.OfferRepository
	landings  *repository.LandingRepository
	networks  *repository.NetworkRepository
	sources   *repository.SourceRepository
	domains   *repository.DomainRepository
	users     *repository.UserRepository
	settings  *repository.SettingsRepository
	botDB     interface {
		Add(string) error
		Exclude(string) error
		Replace(string) error
		Clear() error
		List() []botdb.IPRange
		Count() int
		Contains(net.IP) bool
	}
}

// NewHandler creates a new admin handler.
func NewHandler(db *pgxpool.Pool, cache *cache.Cache, botDB *botdb.ValkeyStore, logger *zap.Logger) *Handler {
	return &Handler{
		db:        db,
		cache:     cache,
		botDB:     botDB,
		logger:    logger,
		campaigns: repository.NewCampaignRepository(db),
		streams:   repository.NewStreamRepository(db),
		offers:    repository.NewOfferRepository(db),
		landings:  repository.NewLandingRepository(db),
		networks:  repository.NewNetworkRepository(db),
		sources:   repository.NewSourceRepository(db),
		domains:   repository.NewDomainRepository(db),
		users:     repository.NewUserRepository(db),
		settings:  repository.NewSettingsRepository(db),
	}
}
