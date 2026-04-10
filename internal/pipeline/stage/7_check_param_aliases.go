package stage

import (
	"fmt"
	"strings"

	"go.uber.org/zap"

	"github.com/skyplix/zai-tds/internal/cache"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// CheckParamAliasesStage resolves traffic source parameter aliases into standard sub_id fields.
type CheckParamAliasesStage struct {
	Cache  *cache.Cache
	Logger *zap.Logger
}

func (s *CheckParamAliasesStage) Name() string      { return "CheckParamAliases" }
func (s *CheckParamAliasesStage) AlwaysRun() bool { return false }

func (s *CheckParamAliasesStage) Process(p *pipeline.Payload) error {
	if p.Campaign == nil || p.Campaign.TrafficSourceID == nil {
		return nil
	}

	source, err := s.Cache.GetTrafficSource(p.Ctx, *p.Campaign.TrafficSourceID)
	if err != nil {
		s.Logger.Error("failed to load traffic source for param aliasing", zap.Error(err))
		return nil
	}
	if source == nil || len(source.Params) == 0 {
		return nil
	}

	rc := p.RawClick
	rawQuery := rc.RawQuery
	if rawQuery == "" {
		return nil
	}

	// Map incoming parameters based on TrafficSource configuration
	for externalKey, internalKey := range source.Params {
		val := getQueryParam(rawQuery, externalKey)
		if val == "" {
			continue
		}

		switch strings.ToLower(internalKey) {
		case "sub_id_1", "sub1": rc.SubID1 = val
		case "sub_id_2", "sub2": rc.SubID2 = val
		case "sub_id_3", "sub3": rc.SubID3 = val
		case "sub_id_4", "sub4": rc.SubID4 = val
		case "sub_id_5", "sub5": rc.SubID5 = val
		case "external_id", "tid": rc.ExternalID = val
		case "keyword", "kw": rc.Keyword = val
		case "source", "src": rc.Source = val
		default:
			if strings.HasPrefix(internalKey, "extra_param_") {
				var i int
				if _, err := fmt.Sscanf(internalKey, "extra_param_%d", &i); err == nil {
					switch i {
					case 1: rc.ExtraParam1 = val
					case 2: rc.ExtraParam2 = val
					case 3: rc.ExtraParam3 = val
					case 4: rc.ExtraParam4 = val
					case 5: rc.ExtraParam5 = val
					case 6: rc.ExtraParam6 = val
					case 7: rc.ExtraParam7 = val
					case 8: rc.ExtraParam8 = val
					case 9: rc.ExtraParam9 = val
					case 10: rc.ExtraParam10 = val
					}
				}
			}
		}
	}

	return nil
}
