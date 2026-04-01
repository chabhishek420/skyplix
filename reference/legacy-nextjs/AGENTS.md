# AGENTS.md — Project Configuration for ZAI / Codex

> Auto-read by Codex on session start. Keep this file version-controlled.

## Project: zai-yt-keitaro (Traffic Distribution System)

Keitaro-style TDS Platform built with Next.js 16, TypeScript, Prisma (SQLite), and shadcn/ui.
A full traffic distribution system with campaign management, click tracking, bot detection, and cloaking.

## Session Rehydration

1. **Restore project files** — Download the workspace `.tar` from your previous session (or `git clone`), extract it into the workspace.
2. **Run the bootstrap:**

```bash
bash bootstrap.sh
```

This clones skills from GitHub, installs node_modules, syncs the database, and runs verification.

## Architecture

| Layer | Stack |
|-------|-------|
| Framework | Next.js 16 (App Router) |
| Language | TypeScript 5 (strict) |
| Styling | Tailwind CSS 4 + shadcn/ui (New York) |
| Database | Prisma ORM + SQLite (file: `db/custom.db`) |
| Icons | Lucide React |
| State | Zustand (client), TanStack Query (server) |
| Auth | Custom API-key auth (Headers: Bearer/X-API-Key | Hashed Session Cookie) |
| AI SDK | z-ai-web-dev-sdk (backend only) |

## Key Directories

```
src/app/page.tsx          — TDS Dashboard (Modern Keitaro-style administrative interface)
src/app/api/              — 24 API routes (click, postback, admin CRUD, safe handling)
src/components/ui/        — shadcn/ui components (52 files)
prisma/schema.prisma      — 22 database models
db/custom.db              — SQLite database file
skills/                   — Installed skills (ez-agents, ralph-zero)
reference/                — Original Keitaro PHP source (for reference)
```

## Development Commands

```bash
bun install              # Install dependencies
bun run dev              # Dev server on port 3000
bun run lint             # ESLint check
bun run db:push          # Sync Prisma schema to DB
bun run db:generate      # Generate Prisma Client
bun run db:reset         # Reset database
```

## Critical Rules

1. **Port 3000 only** — The sandbox Caddy gateway only routes external traffic to port 3000
2. **Use API routes** — No server actions; all backend logic via `/api/` routes
3. **z-ai-web-dev-sdk** — Backend only, never import on client side
4. **API Gateway** — Requests to other ports use `?XTransformPort=<port>` query param
5. **WebSocket** — Use `io("/?XTransformPort=<port>")` with path always `/`
6. **Single route visible** — The sandbox exposes only `/`; keep all UI in `src/app/page.tsx`
7. **No `bun run build`** — Dev server only in this environment
8. **Check `dev.log`** — Read dev.log for server errors (most recent entries)
9. **Worklog** — All agents append progress to `worklog.md` in the project root
10. **Security** — Admin routes strictly enforce header-based Auth (no query keys); Cookies are SHA256 hashed
11. **Validation** — Mutations (POST/PUT) for Campaigns, Streams, and Offers use Zod schema validation

## Database Models (22 total)

User, Session, Campaign, Stream, StreamFilter, Landing, Offer,
StreamLandingAssociation, StreamOfferAssociation, CampaignPublisher, Publisher,
AffiliateNetwork, Domain, TrafficSource, CampaignTrafficSource, BotRule, SafePage,
Click, Conversion, DailyStat, Setting, AuditLog

## UI Conventions

- Dark theme with slate-800/900 backgrounds and emerald accents
- Responsive design (mobile-first with sm/md/lg breakpoints)
- All cards: `bg-slate-800/50 border-slate-700`
- Footer must be sticky to bottom with `mt-auto`
- Use shadcn/ui components, never build custom from scratch
- Tables: `max-h-96 overflow-y-auto` with sticky headers

## Skills (installed by bootstrap.sh)

| Skill | Source | Location | Activation |
|-------|--------|----------|------------|
| EZ Agents v5.0.6 | `github.com/howlil/ez-agents` | `skills/ez-agents/` | `/ez:help` |
| Ralph Zero v0.1.0 | `github.com/.../ralph-zero` | `skills/ralph-zero/` | `.venv/bin/ralph-zero run` |

Skills are cloned with `--depth 1` during bootstrap. Edit the URLs in `bootstrap.sh` to pin specific versions.

## API Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/click` | GET, POST | Process traffic clicks |
| `/api/click/json` | GET | JSON click response |
| `/api/postback` | GET, POST | Conversion postback |
| `/api/lp/offer` | GET | Landing page offer tracking |
| `/api/admin/campaigns` | CRUD | Campaign management |
| `/api/admin/streams` | CRUD | Stream management |
| `/api/admin/offers` | CRUD | Offer management |
| `/api/admin/landings` | CRUD | Landing page management |
| `/api/admin/publishers` | CRUD | Publisher management |
| `/api/admin/clicks` | GET | Click log |
| `/api/admin/conversions` | GET | Conversion data |
| `/api/admin/stats` | GET | Dashboard statistics |
| `/api/admin/reports` | GET | Analytics reports |
| `/api/admin/traffic-sources` | CRUD | Traffic source management |
| `/api/admin/domains` | CRUD | Domain management |
| `/api/admin/bot-rules` | CRUD | Bot detection rules |
| `/api/admin/affiliate-networks` | CRUD | Affiliate network management |
| `/api/admin/users` | CRUD | User management |
| `/api/admin/settings` | GET, PUT | System settings |
| `/api/admin/audit-logs` | GET | Audit trail |
| `/api/admin/login` | POST | Authentication |
| `/api/admin/logout` | POST | Logout |

<!-- DEEPINIT:START -->

## Deep Init Navigation

Generated: 2026-04-01

Primary application workspace for the Next.js-based TDS implementation, its supporting documentation, and the archived reference projects used during parity work.

| Directory | Purpose |
| --- | --- |
| `src/` | Live application source: routes, UI, shared libraries, and type definitions. (see `src/AGENTS.md`) |
| `prisma/` | Prisma schema and seeding entrypoints for the SQLite-backed data model. (see `prisma/AGENTS.md`) |
| `db/` | Local SQLite database assets and generated runtime data. (see `db/AGENTS.md`) |
| `docs/` | Project notes, verification reports, and operational writeups. (see `docs/AGENTS.md`) |
| `public/` | Static assets served directly by Next.js. (see `public/AGENTS.md`) |
| `scripts/` | Repository maintenance and validation scripts, including the deep-init generator. (see `scripts/AGENTS.md`) |
| `tasks/` | Human-maintained todo and lessons-learned notes. (see `tasks/AGENTS.md`) |
| `adapters/` | Prompting guidance for external model/provider adapters. (see `adapters/AGENTS.md`) |
| `examples/` | Small standalone examples used to validate patterns outside the main app. (see `examples/AGENTS.md`) |
| `planning/` | Architecture snapshots and codebase planning documents. (see `planning/AGENTS.md`) |
| `reference/` | Archived third-party/source-material projects for feature parity research. (see `reference/AGENTS.md`) |
| `mini-services/` | Reserved space for sidecar services; currently an empty container. (see `mini-services/AGENTS.md`) |

### Notes For Agents
- Treat the manual project rules above as authoritative; this generated section is only for navigation.
- Prefer editing `src/`, `prisma/`, and `docs/` unless the task explicitly calls for reference material or tooling changes.
- The `reference/` tree is intentionally shallow-documented here to avoid generating AGENTS files for vendored code.

<!-- DEEPINIT:END -->
