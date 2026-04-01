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

---

## Session: 2026-04-02 00:41

### Objective
Deep empirical validation of all planning documents against the Keitaro v9.13.9 PHP source code to finalize the pre-Phase 1 architecture.

### Accomplished
- Completed 1,700-file Keitaro source code audit.
- Identified 8 material flaws in initial architectural assumptions.
- Corrected the "no ClickHouse" assumption (found `rbooster` config).
- Discovered 4 new action types, including the critical `Remote` proxy action for cloaking.
- Mapped Keitaro's undocumented Entity Binding system (`BindVisitors`).
- Documented 13 background workers (cron tasks), including the critical Redis → DB flush.
- Fixed device detection library choice (`robicode` vs `mssola`).
- Restructured `ROADMAP.md` (moved bot detection to Phase 1, entity binding to Phase 2).
- Added 3 new ADRs to `DECISIONS.md`.

### Verification
- [x] All 23 Level 1 pipeline stages verified line-by-line.
- [x] All 13 Level 2 pipeline stages verified line-by-line.
- [x] Redis caching points and background workers verified.
- [ ] Needs evaluation between `robicode/device-detector` and `mileusna/useragent` in Phase 1.

### Paused Because
Context getting heavy (Context Health Monitor triggered). Saturated with old PHP code research. It's time to flush the context and start fresh before building the Go scaffold.

### Handoff Notes
The planning is 100% accurate and vetted. Next session should jump straight to `/plan 1` and execution of the `/cmd`, `/internal`, `/db` scaffolding. Ensure Docker compose includes Postgres, Valkey, and ClickHouse.
