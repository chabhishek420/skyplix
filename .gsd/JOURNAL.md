# JOURNAL.md — Project Journal

## 2026-04-01 — Project Initialized & Stack Finalized

### Codebase Analysis
- Analyzed Keitaro PHP source v9.13.9 (1,705 PHP files, 4 modules)
- Verified two-level pipeline from `Traffic/Pipeline/Pipeline.php`:
  Level 1 = 23 stages (campaign click), Level 2 = 13 stages (landing click)
- Cataloged 51 component modules, 55 admin API controllers
- Identified 27 stream filter types from `Component/StreamFilters/Filter/`
- Identified 15 action types from `Traffic/Actions/Predefined/`
- Analyzed YellowCloaker (12-check bot detection system)
- Verified yljary.com live TDS infrastructure via terminal
- Scanned 8 reference projects in `reference/` directory

### Architecture Decisions
- Decision: Go rewrite over continuing Next.js implementation
- Decision: Chi v5 over Fiber (net/http compatibility > synthetic speed)
- Decision: Valkey 8 over Redis 7 (BSD open-source, drop-in compatible)
- Decision: sqlc + pgx over GORM (zero ORM, compile-time safety)
- Decision: Vite + React over Next.js for admin (static embed in Go binary)
- Decision: Valkey session tokens over JWT (revocable, team-safe)
- Decision: ClickHouse 24 for analytics (Keitaro has none — we leapfrog)

### Repository Cleanup
- Archived legacy Next.js codebase into `reference/legacy-nextjs/`
- Fresh `git init` — clean single-commit history
- Created SPEC, ROADMAP, ARCHITECTURE, STACK, RESEARCH, STATE

### Key Insight from Source
Redis is Keitaro's primary performance secret, not MySQL. All hot-path
reads go to Redis (pre-warmed entity cache). All hot-path writes queue
through Redis (async command buffer, flushed by cron in batches of 1000).
MySQL is never touched during click processing.
