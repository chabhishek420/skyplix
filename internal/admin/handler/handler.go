package handler

import (
	"net"
	"net/http"
	"time"

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
	uaStore interface {
		Add(string) error
		Remove(string) error
		Replace(string) error
		Clear() error
		Patterns() []string
	}
}

// NewHandler creates a new admin handler.
func NewHandler(db *pgxpool.Pool, cache *cache.Cache, botDB *botdb.ValkeyStore, uaStore *botdb.UAStore, logger *zap.Logger) *Handler {
	return &Handler{
		db:        db,
		cache:     cache,
		botDB:     botDB,
		uaStore:   uaStore,
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

// ListClusterNodes returns a list of active TDS nodes in the cluster.
// GET /api/v1/cluster/nodes
func (h *Handler) ListClusterNodes(w http.ResponseWriter, r *http.Request) {
	// Simple static implementation for now (Phase 10 placeholder)
	nodes := []map[string]interface{}{
		{
			"id":        "node-1",
			"host":      r.Host,
			"version":   "v1.0.0",
			"status":    "online",
			"last_seen": time.Now().Format(time.RFC3339),
		},
	}
	h.respondJSON(w, http.StatusOK, nodes)
}
