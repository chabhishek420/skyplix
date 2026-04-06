package stage

import (
	"github.com/psanford/tlsfingerprint"
	"github.com/skyplix/zai-tds/internal/pipeline"
)

// JA3EnrichmentStage extracts TLS fingerprints from context and populates RawClick.
type JA3EnrichmentStage struct{}

func (s *JA3EnrichmentStage) Name() string      { return "JA3Enrichment" }
func (s *JA3EnrichmentStage) AlwaysRun() bool   { return true }

type fingerprintContextKey struct{}

func (s *JA3EnrichmentStage) Process(p *pipeline.Payload) error {
	if p.RawClick == nil {
		return nil
	}

	val := p.Request.Context().Value(fingerprintContextKey{})
	if val == nil {
		return nil
	}

	fp, ok := val.(*tlsfingerprint.Fingerprint)
	if !ok {
		return nil
	}

	p.RawClick.JA3 = fp.JA3Hash()
	p.RawClick.JA4 = fp.JA4String()

	// TLSHost often corresponds to SNI
	if fp.HasSNI {
		// Note: tlsfingerprint doesn't currently expose the actual SNI string easily,
		// but we can at least mark it. If we need the string, we'd need more complex parsing.
		p.RawClick.TLSHost = "sni_present"
	}

	return nil
}
