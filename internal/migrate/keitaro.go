package migrate

import (
	"context"
	"database/sql"
	"fmt"
	"github.com/google/uuid"
	"github.com/skyplix/zai-tds/internal/model"
	_ "github.com/go-sql-driver/mysql"
	"time"
)

// KeitaroImporter defines the logic to import entities from a Keitaro MySQL source.
type KeitaroImporter struct {
	sourceDB *sql.DB
	targetWS uuid.UUID
}

// NewKeitaroImporter creates a new importer with a connected MySQL source.
func NewKeitaroImporter(dsn string, targetWS uuid.UUID) (*KeitaroImporter, error) {
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		return nil, err
	}
	return &KeitaroImporter{sourceDB: db, targetWS: targetWS}, nil
}

// Close closes the source database connection.
func (i *KeitaroImporter) Close() error {
	return i.sourceDB.Close()
}

// CampaignImport represents a campaign being migrated.
type CampaignImport struct {
	SourceID    int
	Campaign    *model.Campaign
	Success     bool
	ErrorReason string
}

// ImportCampaigns performs the migration from Keitaro to SkyPlix.
func (i *KeitaroImporter) ImportCampaigns(ctx context.Context, dryRun bool) ([]CampaignImport, error) {
	rows, err := i.sourceDB.QueryContext(ctx, "SELECT id, alias, name, state, cost_type, cost_value FROM keitaro_campaigns")
	if err != nil {
		return nil, fmt.Errorf("query keitaro_campaigns: %w", err)
	}
	defer rows.Close()

	var results []CampaignImport
	for rows.Next() {
		var id int
		var alias, name, state, costType string
		var costValue float64
		if err := rows.Scan(&id, &alias, &name, &state, &costType, &costValue); err != nil {
			results = append(results, CampaignImport{SourceID: id, Success: false, ErrorReason: err.Error()})
			continue
		}

		c := &model.Campaign{
			ID:          uuid.New(),
			WorkspaceID: i.targetWS,
			Alias:       alias,
			Name:        name,
			State:       state,
			CostModel:   costType,
			CostValue:   int64(costValue * 100), // convert to cents
			CreatedAt:   time.Now(),
			UpdatedAt:   time.Now(),
		}

		results = append(results, CampaignImport{
			SourceID: id,
			Campaign: c,
			Success:  true,
		})
	}
	return results, nil
}
