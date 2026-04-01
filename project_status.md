# Project Status & Debug Log

> **Last Updated:** 2026-04-01T23:45:00+05:30
> **Current Phase:** Pre-Phase 1 (Architecture & Research Complete)

## 🎯 What Has Been Implemented

1. **Legacy Infrastructure Deprecation**
   - Evaluated the existing Next.js + Prisma + SQLite prototype.
   - Identified critical performance bottlenecks (SQLite locks, Node.js single-thread limits, framework overhead).
   - Moved the legacy codebase to `reference/legacy-nextjs/` and initialized a fresh Git repository for the Go rewrite.

2. **Deep Codebase Analysis (Keitaro PHP Reference)**
   - Scanned 1,705 PHP files across the reference `Keitaro_source_php/` directory.
   - Mapped the exact **two-level pipeline** architecture (23 stages for Campaign Click, 13 stages for Landing Click).
   - Identified 51 feature modules, 27 stream filters, and 15 action types.
   - Confirmed Keitaro's async event architecture (using Redis as the primary hot-path cache and write buffer, not just session storage).

3. **Technology Stack Finalized**
   - **Language:** Go 1.23+ (Targeting <5ms p99 latency)
   - **HTTP Framework:** Chi v5 (Chosen over Fiber for full `net/http` compatibility, avoiding vendor lock-in).
   - **Primary Database:** PostgreSQL 16 (Config storage, JSONB for rules) via `pgx` v5 and `sqlc` (zero-ORM).
   - **Cache & Message Queue:** Valkey 8 (Open-source BSD alternative to Redis, providing critical hot-path caching and async click buffering).
   - **Analytics Database:** ClickHouse 24 (Columnar storage for real-time aggregation of billions of click events).
   - **Frontend:** Vite + React 19 + shadcn/ui + TanStack Query (Embedded directly in the Go binary via `//go:embed` for a single-file deployment).

4. **GSD Project Documentation Complete**
   - `SPEC.md` - Technical specification and architecture overview.
   - `ROADMAP.md` - 7-Phase execution plan.
   - `ARCHITECTURE.md` - Detailed Keitaro-to-Go mapping.
   - `STACK.md` - Complete technology inventory.
   - `RESEARCH.md` - 12-layer deep-dive research with source verifications.
   - `DECISIONS.md` - ADRs logging critical shifts (Chi, Valkey, sqlc, etc.).
   - Cleaned up all stale `.gsd` files from the Next.js era.

---

## 🚀 What's Next

**Immediate Next Step:** Run `/plan 1` to begin Phase 1 execution.

**Phase 1 Focus:**
- Scaffolding the Go project structure (`/cmd`, `/internal`, etc.).
- Setting up the Docker Compose environment (PostgreSQL, Valkey, ClickHouse).
- Implementing the core Level 1 Click Pipeline (Stages 1-6, 9, 20, 23).
- Integrating MaxMind GeoLite2 and `device-detector`.

---

## 🐛 Detailed Debug Log (Architecture Pivot)

**Context:** During the transition from the Next.js prototype to the Go architecture, several critical assumptions were evaluated and corrected based on deep source code inspection.

1. **The "22-Stage Pipeline" Myth:**
   - *Initial Assumption:* The Keitaro pipeline was a single 22-stage flow.
   - *Verification:* Inspected `Traffic/Pipeline/Pipeline.php`.
   - *Correction:* The pipeline consists of **two distinct levels**. Level 1 handles Campaign clicks (23 stages), and Level 2 handles Landing-to-Offer clicks (13 stages), linked by a `visitor_code` cookie. `SPEC.md` was updated.

2. **The Role of Redis (Valkey):**
   - *Initial Assumption:* Redis was just for rate-limiting and user sessions.
   - *Verification:* Analyzed `CommandQueue/QueueStorage/RedisStorage.php` and `CachedData/Storage/RedisStorage.php`.
   - *Correction:* Redis is the core of Keitaro's performance. MySQL is *never* hit on the hot path. Configs are pre-loaded into Redis, and incoming clicks are buffered in Redis (`RPUSH`) before a cron worker flushes them to the DB. We replaced Redis with Valkey 8 for licensing reasons, but the architectural pattern is identical.

3. **HTTP Framework: Fiber vs. Chi:**
   - *Initial Assumption:* Fiber should be used because synthetic benchmarks show it's the fastest.
   - *Verification:* Researched 2026 benchmarks and evaluated the impact of `fasthttp` (used by Fiber).
   - *Correction:* Fiber breaks compatibility with the vast `net/http` middleware ecosystem. Furthermore, in a DB-bound (Postgres/Valkey) and I/O-bound (GeoIP) system, the ~50µs routing advantage of Fiber is statistically invisible. Switched to Chi v5 for a robust, standards-compliant foundation.

4. **The ORM Trap:**
   - *Initial Assumption:* GORM should be used for database interactions.
   - *Verification:* Keitaro uses raw ADODB SQL. GORM introduces heavy reflection overhead and N+1 query risks.
   - *Correction:* Adopted `sqlc` + `pgx` to generate compile-time type-safe Go code from plain `.sql` files, guaranteeing zero reflection overhead on the critical path.

5. **Next.js vs. Vite + React:**
   - *Initial Assumption:* A Next.js App Router (static export) is the best choice for the admin UI.
   - *Verification:* Next.js static exports strip out Server Components and API routes, leaving behind unnecessary framework bloat for a pure SPA.
   - *Correction:* Switched to Vite + React 19 + shadcn/ui. This provides lightning-fast HMR during development and compiles into a clean static bundle that can be seamlessly embedded into the Go binary via `//go:embed`.

**Git State:** All documentation and architectural updates are synchronized and cleanly committed to the `main` branch.
