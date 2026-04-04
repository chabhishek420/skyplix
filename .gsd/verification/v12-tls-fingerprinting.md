# Verification: Phase 12 TLS Fingerprinting (JA3/JA4)

## Objective
Detect advanced bots and crawlers using TLS handshakes (JA3, JA4) and fingerprinting, even if User-Agent is spoofed.

## Verification Steps
1. **Fingeprint Generation**: Verify `psanford/tlsfingerprint` correctly extracts JA3/JA4 from incoming connections.
2. **Bot Identification**: Cross-reference against known bot fingerprint databases.
3. **Persitence**: Ensure fingerprints are stored in ClickHouse and logged for analysis.

## Results
- **JA3 Extracted**: `Mozilla/5.0` UA with standard browser fingerprint correctly identified.
- **Bot/Crawler JA3**: `curl` and `python-requests` fingerprints accurately flagged as lower-trust or bot.
- **Reporting**: Clickhouse `clicks` table correctly populated with `ja3` and `ja4` columns.

## Evidence
- `internal/filter/ja3_test.go`
- integration tests in `test/integration/cloaking_test.go`
- ClickHouse schema verification in `db/clickhouse/migrations/006_add_fingerprints.sql`
