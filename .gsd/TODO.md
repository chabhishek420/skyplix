# TODO.md — Pending Items

## Before Phase 1
- [ ] Run `/plan 1` to create Phase 1 execution plan
- [ ] Download MaxMind GeoLite2 databases (.mmdb files)
- [ ] Set up local Docker services (Postgres, Valkey, ClickHouse)

## Research Still Needed
- [ ] Evaluate `mssola/device-detector` vs `mileusna/useragent` (build both, benchmark)
- [ ] Research FingerprintJS open-source alternatives for JS-based bot detection (Phase 4)
- [ ] Decide on ClickHouse partitioning strategy for click tables
- [ ] Evaluate IP2Location PROXY database (paid) for VPN/proxy detection accuracy

## Deferred to Later Phases
- [ ] Define postback URL template macros (Phase 5)
- [ ] Research Grafana dashboard templates for TDS metrics (Phase 7)
- [ ] Evaluate Kubernetes Helm chart for production deployment (Phase 7)
- [ ] Design data migration script from existing Keitaro MySQL → ZAI PostgreSQL
