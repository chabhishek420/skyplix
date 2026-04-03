# Knowledge

<!-- Append-only register of project-specific rules, patterns, and lessons learned -->

## Architecture & Pipeline

- **Pattern: Stage-based pipeline for deterministic click processing** — The core pipeline is ordered stages (1-23) that each take a Payload and return early if conditions abort. Each stage is independent with single responsibility. Keep this pattern; don't convert to middleware chains.

- **Pattern: Valkey for hot-path state, PostgreSQL for config, ClickHouse for analytics** — Three-store model: PostgreSQL is the config/metadata store (campaigns, streams, offers, landings, filters, users, settings). Valkey holds visitor sessions, entity caches, and rate limit counters. ClickHouse ingests raw clicks asynchronously for reporting. Never store config in Valkey (defeats the point of PostgreSQL ACID). Never make ClickHouse a synchronous path (kills latency).

- **Lesson: Pointer bugs in selection stages are latent** — Phase 2 had subtle heap allocation bugs in offer/landing rotation where slices were modified after being selected. Always copy before modifying. Use `append([]T{}, source...)` or explicit struct copies.

- **Lesson: Filter matching is case-sensitive by default; normalize early** — Filter values (ISP, country code, device type) from MaxMind are uppercase. Incoming geoip/device detection outputs must match. Do the normalization once in `FilterEngine` or `UpdateRawClickStage`, not per-filter.

- **Lesson: Cache fallbacks prevent cascading failures** — Early implementation had hard failures on Valkey outage (cache miss → nil pointer). Implement fallback paths: if cache miss, query PostgreSQL; if that fails too, have a sensible default (e.g., stream.DefaultOffer).

- **Lesson: Shutdown order matters** — Must cancel HTTP server BEFORE draining workers. Otherwise in-flight requests keep spinning up new work items while workers are trying to flush. Invert the typical dependency.

## Bot Detection & Cloaking

- **Pattern: Layered bot detection — fast path first** — Run IP-based checks before UA parsing. MaxMind ASN lookup is O(1) (binary search on sorted ranges). UA regex is slower. Run IP checks on every click; run advanced UA checks only if needed.

- **Lesson: Safe page modes map to Keitaro reference** — Phase 4 identified 4 safe page modes: `Remote` (proxy with TTL cache), `LocalFile` (static HTML), `Status404` / `DoNothing` (HTTP error), `ShowHtml` (inline). Stick to these; don't invent new ones.

- **Lesson: Remote proxy needs TTL cache** — Proxying a third-party safe page on every bot request kills performance and creates external dependency. Cache the response for 60s (Keitaro pattern). Implement in `ProxyAction` with Valkey.

## Testing & Verification

- **Lesson: Integration tests need real services** — Phase 1.5 learned that mocking PostgreSQL/ClickHouse misses real bugs (e.g., UUID validation failures at batch ingest time). For click pipeline, use Docker Compose with real services; for admin API, mock at the repository layer only.

- **Lesson: Unit test seed data must match production schema** — Phase 2 had test failures because seed data casing didn't match Keitaro exports. Use lowercase for URLs/hostnames, uppercase for country/device codes. Document the convention in test setup.

- **Lesson: Latency benchmarks require sustained load** — One-off measurements are noise. Phase 4.9.4 measured p99 at 1k RPS for 1m; single requests can look much faster. Always include load profile in the acceptance criteria.

## DevOps & Deployment

- **Lesson: Configuration via YAML is sufficient for now** — `config.yaml` with environment variable interpolation (Go `os.ExpandEnv`) handles local dev, Docker, and systemd deploys. Don't over-engineer to HCL/Terraform yet.

- **Lesson: ClickHouse batch ingestion needs partition strategy** — Phase 5 research noted ClickHouse queries slow down without time-based partitioning. Use `ReplacingMergeTree` partitioned by `date(click_time)` to enable TTL and pruning.

- **Lesson: Valkey is mission-critical for rate limiting** — If Valkey is down, rate limit stage can't run safely. Implement a circuit breaker: on repeated Valkey errors, fall back to a lenient in-memory rate limiter (not persistent across restarts, but better than crashing or allowing unlimited traffic).

## Code Organization

- **Pattern: Keep business logic in `internal/`, entry points in `cmd/`** — `cmd/zai-tds/main.go` bootstraps and wires packages. Core logic is in `internal/action`, `internal/pipeline`, `internal/queue`, `internal/worker`. Do not scatter business logic across cmd files.

- **Pattern: Repository pattern for data access** — Each major entity (Campaign, Stream, Offer, Click) has a repository that abstracts PostgreSQL/ClickHouse queries. Business logic calls repositories, not raw SQL. Easier to mock, test, and refactor.

- **Lesson: Context is always the first parameter** — Go convention. Pass context through every function that might block or need cancellation. Do not store context in structs (defeats the point of cancellation). `func (c *CampaignService) GetByID(ctx context.Context, id string) (*Campaign, error)`.

## Common Mistakes to Avoid

- **Do NOT**: Store state in global variables or package-level vars (breaks testability, makes concurrency bugs hard to trace).
- **Do NOT**: Use `time.Sleep` for synchronization in tests (flaky). Use channels or `<-time.After`.
- **Do NOT**: Ignore error wrapping. Always wrap with context: `fmt.Errorf("stage X failed: %w", err)`.
- **Do NOT**: Modify Valkey keys without checking TTL/expiry. Assume every key can be stale.
- **Do NOT**: Skip UUID validation on intake (Phase 1.5 learned this the hard way).
- **Do NOT**: Hardcode filter type matching strings. Define constants or enums.

---

*Last updated: 2026-04-03 — migrated from v1 to v2*
