package stage

import (
	"fmt"

	"github.com/skyplix/zai-tds/internal/pipeline"
)

// UpdateParamsStage — Pipeline Stage 6.5
// Handles dynamic parameter overrides (sub_ids, external_id, keyword)
// from the request query string.
type UpdateParamsStage struct{}

func (s *UpdateParamsStage) Name() string    { return "UpdateParams" }
func (s *UpdateParamsStage) AlwaysRun() bool { return false }

func (s *UpdateParamsStage) Process(p *pipeline.Payload) error {
	if p.RawClick == nil {
		return nil
	}

	rc := p.RawClick
	rawQuery := rc.RawQuery
	if rawQuery == "" {
		return nil
	}

	// Dynamic overrides for standard sub_ids
	if val := getQueryParam(rawQuery, "sub_id_1", "sub1"); val != "" {
		rc.SubID1 = val
	}
	if val := getQueryParam(rawQuery, "sub_id_2", "sub2"); val != "" {
		rc.SubID2 = val
	}
	if val := getQueryParam(rawQuery, "sub_id_3", "sub3"); val != "" {
		rc.SubID3 = val
	}
	if val := getQueryParam(rawQuery, "sub_id_4", "sub4"); val != "" {
		rc.SubID4 = val
	}
	if val := getQueryParam(rawQuery, "sub_id_5", "sub5"); val != "" {
		rc.SubID5 = val
	}

	// Attribution overrides
	if val := getQueryParam(rawQuery, "external_id", "tid"); val != "" {
		rc.ExternalID = val
	}
	if val := getQueryParam(rawQuery, "keyword", "kw"); val != "" {
		rc.Keyword = val
	}
	if val := getQueryParam(rawQuery, "source", "src"); val != "" {
		rc.Source = val
	} else if val := getQueryParam(rawQuery, "site"); val != "" {
		rc.Source = val
	}

	// Extra params mapping (1-10)
	for i := 1; i <= 10; i++ {
		key := fmt.Sprintf("extra_param_%d", i)
		if val := getQueryParam(rawQuery, key); val != "" {
			switch i {
			case 1:
				rc.ExtraParam1 = val
			case 2:
				rc.ExtraParam2 = val
			case 3:
				rc.ExtraParam3 = val
			case 4:
				rc.ExtraParam4 = val
			case 5:
				rc.ExtraParam5 = val
			case 6:
				rc.ExtraParam6 = val
			case 7:
				rc.ExtraParam7 = val
			case 8:
				rc.ExtraParam8 = val
			case 9:
				rc.ExtraParam9 = val
			case 10:
				rc.ExtraParam10 = val
			}
		}
	}

	return nil
}
