# Codebase Structure

**Analysis Date:** 2026-03-31

## Directory Layout

```
/home/z/my-project/
├── src/                          # Application source code (~28,019 lines)
│   ├── app/                      # Next.js App Router pages + API routes
│   │   ├── page.tsx              # AI Skills Hub demo (1,201 lines)
│   │   ├── layout.tsx            # Root layout (HTML, fonts, providers)
│   │   ├── globals.css           # Global styles (Tailwind + CSS variables)
│   │   └── api/                  # 32 API route handlers
│   │       ├── click/            # Traffic click processing
│   │       ├── postback/         # Conversion tracking
│   │       ├── lp/               # Landing page offer tracking
│   │       ├── ai/               # AI skill endpoints (9 routes)
│   │       ├── admin/            # Admin CRUD endpoints (18 routes)
│   │       └── route.ts          # Root API fallback
│   ├── components/               # React components
│   │   └── ui/                   # shadcn/ui components (52 files)
│   ├── hooks/                    # Custom React hooks
│   │   ├── use-mobile.ts         # Mobile viewport detection
│   │   └── use-toast.ts          # Toast notification hook
│   └── lib/                      # Core libraries
│       ├── db.ts                 # Prisma client singleton
│       ├── utils.ts              # Utility functions (cn helper)
│       ├── auth/                 # Authentication
│       │   ├── index.ts          # Auth barrel export
│       │   └── admin-auth.ts     # Custom API-key admin auth
│       └── tds/                  # ★ TDS Engine Core (~16,565 lines)
│           ├── index.ts          # TDS barrel export
│           ├── click-processor.ts    # Main click processing (446 lines)
│           ├── rotator.ts            # Stream rotation (331 lines)
│           ├── bot-detection.ts      # Bot/fraud detection (679 lines)
│           ├── click-id.ts           # Click ID generation
│           ├── macros.ts             # Macro system entry (775 lines)
│           ├── pipeline/             # Click processing pipeline
│           │   ├── pipeline.ts       # Pipeline orchestration
│           │   ├── payload.ts        # Pipeline data payload (585 lines)
│           │   ├── types.ts          # Pipeline type definitions
│           │   └── stages/           # 25 pipeline stages
│           ├── actions/              # Stream action executors
│           │   ├── base.ts           # Base action interface
│           │   ├── repository.ts     # Action registry
│           │   ├── types.ts          # Action type definitions
│           │   └── predefined/       # 19 action implementations
│           ├── filters/              # Stream filter evaluators
│           │   ├── index.ts          # Filter dispatcher (659 lines)
│           │   ├── types.ts          # Filter type definitions
│           │   ├── advanced.ts       # Complex filter logic (545 lines)
│           │   └── [8 filter types]  # country, browser, os, device, etc.
│           ├── macros/               # Template macro system
│           │   ├── processor.ts      # Macro processor
│           │   ├── registry.ts       # Macro registry
│           │   ├── types.ts          # Macro type definitions
│           │   └── predefined/       # 26 macro modules
│           ├── contexts/             # Request context handlers
│           │   ├── gateway-context.ts # Gateway click context (354 lines)
│           │   └── landing-context.ts # Landing page context (333 lines)
│           ├── data/                 # Reference data
│           │   ├── countries.ts       # Country codes/names
│           │   ├── browsers.ts        # Browser detection data
│           │   ├── operating-systems.ts # OS detection data
│           │   ├── languages.ts       # Language codes
│           │   ├── operators.ts       # ISP operators (421 lines)
│           │   ├── connection-types.ts # Connection type data
│           │   ├── search-engines.ts  # Search engine list
│           │   └── bot-signatures.ts  # Known bot UA patterns
│           ├── services/             # Business logic services
│           │   ├── cookies-service.ts # Cookie management (326 lines)
│           │   ├── geo-db-service.ts  # GeoIP lookup (283 lines)
│           │   ├── ip-info-service.ts # IP information (279 lines)
│           │   ├── entity-binding-service.ts # Entity association (262 lines)
│           │   ├── lp-token-service.ts # Landing page tokens
│           │   └── proxy-service.ts   # Proxy support
│           └── utils/                # TDS utilities
│               ├── raw-click-serializer.ts # Click data serialization (364 lines)
│               └── index.ts               # Utility helpers
├── prisma/                      # Database
│   ├── schema.prisma            # 22 Prisma models (693 lines)
│   └── seed.ts                  # Database seeder (10,383 bytes)
├── db/                          # Database files
│   └── custom.db                # SQLite database (gitignored)
├── public/                      # Static assets
├── reference/                   # Original Keitaro PHP source (57MB, legacy reference)
│   ├── admin/                   # PHP admin UI
│   ├── vendor/                  # PHP dependencies
│   └── application/             # PHP application code
├── skills/                      # Installed AI agent skills (gitignored)
│   ├── ez-agents/               # EZ Agents v5.0.6
│   └── ralph-zero/              # Ralph Zero v0.1.0
├── examples/                    # Example code
│   └── websocket/               # WebSocket demo (frontend + server)
├── mini-services/               # Mini service placeholder
├── docs/                        # Documentation
│   ├── TRANSLATION_ANALYSIS.md  # PHP→TS translation analysis
│   ├── TRANSLATION_STATUS.md    # Translation progress tracking
│   ├── VERIFICATION_REPORT.md   # Feature verification report
│   ├── changes.md               # Change log
│   └── project_status.md        # Overall project status
├── .planning/                   # EZ Agents planning artifacts
│   └── codebase/                # Codebase mapping documents (7 files)
├── .gsd-source/                 # GSD source data (legacy)
├── upload/                      # Upload directory (mounted volume)
├── bootstrap.sh                 # Session rehydration script
├── CLAUDE.md                    # Claude Code project config
├── REHYDRATE.md                 # Quick-start rehydration prompt
├── Caddyfile                    # Caddy gateway config
├── .env.example                 # Environment variable template
├── .gitignore                   # Git ignore rules
├── package.json                 # Project manifest
├── bun.lock                     # Bun lockfile
├── tsconfig.json                # TypeScript configuration
├── next.config.ts               # Next.js configuration
├── tailwind.config.ts           # Tailwind CSS configuration
├── postcss.config.mjs           # PostCSS configuration
├── eslint.config.mjs            # ESLint flat config
├── components.json              # shadcn/ui configuration
└── worklog.md                   # Agent work log
```

## Directory Purposes

**`src/app/`** — Next.js App Router
- Purpose: Pages and API routes
- Contains: `page.tsx` (AI Skills Hub demo with 8 panels), `layout.tsx` (root), `globals.css` (styles), 32 API routes
- Key files: `src/app/page.tsx` (AI skills demo), `src/app/api/click/route.ts` (traffic entry point)

**`src/components/ui/`** — shadcn/ui components
- Purpose: Reusable UI primitives from shadcn/ui (New York variant)
- Contains: 52 pre-built components (button, dialog, table, form, etc.)
- Key files: `button.tsx`, `card.tsx`, `table.tsx`, `dialog.tsx`, `form.tsx`
- Note: No custom components outside `ui/` — all UI is in `page.tsx`

**`src/lib/tds/`** — TDS Engine Core
- Purpose: Complete traffic distribution engine (60% of codebase)
- Contains: Click processing, bot detection, stream rotation, macro expansion, filters, actions
- Key files: `click-processor.ts`, `rotator.ts`, `bot-detection.ts`, `macros.ts`

**`src/lib/tds/pipeline/`** — Click Processing Pipeline
- Purpose: 25-stage sequential pipeline for processing each click
- Contains: Pipeline orchestrator, payload types, individual stage implementations
- Key files: `pipeline.ts` (orchestrator), `stages/choose-stream.ts`, `stages/execute-action.ts`

**`src/lib/tds/actions/`** — Stream Action Executors
- Purpose: Execute actions when a stream matches (redirect, iframe, show text, etc.)
- Contains: Base action class, registry, 19 predefined action implementations
- Key files: `base.ts`, `repository.ts`, `predefined/http-redirect.ts`

**`src/lib/tds/filters/`** — Stream Filter Evaluators
- Purpose: Evaluate stream conditions (country, browser, OS, device, uniqueness, etc.)
- Contains: Filter dispatcher, type definitions, 10 filter implementations
- Key files: `index.ts` (dispatcher), `advanced.ts`, `country.ts`

**`src/lib/tds/macros/`** — Template Macro System
- Purpose: Replace `{macro}` tokens in URLs and content (e.g., `{subid}`, `{country}`)
- Contains: Processor, registry, 26 predefined macro modules
- Key files: `processor.ts`, `macros.ts` (entry), `predefined/subid.ts`

**`prisma/`** — Database Schema & Seeding
- Purpose: Prisma ORM schema and database seeder
- Contains: 22 model definitions, seed data generator
- Key files: `schema.prisma` (models), `seed.ts` (seeds 757 clicks, 28 conversions)

**`reference/`** — Legacy PHP Code
- Purpose: Original Keitaro TDS PHP source used as translation reference
- Contains: PHP admin UI, vendor dependencies, application logic, migrations
- Note: 57MB, not imported by the app — purely for developer reference

## Key File Locations

**Entry Points:**
- `src/app/page.tsx`: AI Skills Hub demo (8 panels: Chat, Image, Vision, TTS, ASR, Video, Search, Reader)
- `src/app/api/click/route.ts`: Traffic click processing endpoint
- `src/app/api/postback/route.ts`: Conversion postback endpoint
- `src/app/layout.tsx`: Root HTML layout with fonts and providers

**Configuration:**
- `prisma/schema.prisma`: Database schema (22 models)
- `next.config.ts`: Next.js configuration
- `tailwind.config.ts`: Theme tokens and dark mode
- `eslint.config.mjs`: Linting rules
- `.env.example`: Required environment variables

**Core TDS Logic:**
- `src/lib/tds/click-processor.ts`: Main click processing entry point
- `src/lib/tds/rotator.ts`: Stream rotation and selection
- `src/lib/tds/bot-detection.ts`: Bot detection rules engine
- `src/lib/tds/pipeline/pipeline.ts`: Pipeline stage orchestration
- `src/lib/tds/macros/macros.ts`: Macro expansion entry point

**API Routes (32 total):**
- Traffic: `src/app/api/click/route.ts`, `src/app/api/postback/route.ts`
- Admin CRUD: `src/app/api/admin/` (18 routes: campaigns, streams, offers, etc.)
- AI Skills: `src/app/api/ai/` (9 routes: chat, image, vision, tts, asr, etc.)

**Authentication:**
- `src/lib/auth/admin-auth.ts`: Custom API-key-based admin auth (Bearer / X-API-Key / Cookie / Query)
- `src/app/api/admin/login/route.ts`: Login endpoint
- `src/app/api/admin/logout/route.ts`: Logout endpoint

## Naming Conventions

**Files:**
- kebab-case for all files: `click-processor.ts`, `bot-detection.ts`, `admin-auth.ts`
- API routes: `route.ts` in directory matching endpoint path
- Component files: PascalCase for shadcn/ui: `Button.tsx`, `AlertDialog.tsx`
- Pipeline stages: kebab-case: `choose-stream.ts`, `execute-action.ts`
- Filter types: kebab-case: `country.ts`, `browser.ts`, `advanced.ts`
- Macro modules: kebab-case: `subid.ts`, `country.ts`, `advanced.ts`

**Directories:**
- kebab-case: `tds/`, `bot-detection/`, `codebase/`
- API route directories match URL path: `admin/campaigns/`, `ai/chat/`

**TypeScript Patterns:**
- Interfaces/types in `types.ts` files within each module
- Barrel exports via `index.ts` in each directory
- `'use client'` directive for client components
- `'use server'` for server-side code (rare — uses API routes instead)

## Where to Add New Code

**New API Route:**
- Create `src/app/api/{path}/route.ts` with GET/POST/etc. exports
- Import `db` from `@/lib/db` for database access
- Follow existing patterns in `src/app/api/admin/campaigns/route.ts`

**New TDS Filter:**
- Add file in `src/lib/tds/filters/{name}.ts`
- Implement filter evaluation logic
- Register in `src/lib/tds/filters/index.ts` dispatcher
- Add type in `src/lib/tds/filters/types.ts`

**New TDS Action:**
- Add file in `src/lib/tds/actions/predefined/{name}.ts`
- Extend base action class from `src/lib/tds/actions/base.ts`
- Register in `src/lib/tds/actions/repository.ts`

**New TDS Macro:**
- Add file in `src/lib/tds/macros/predefined/{name}.ts`
- Register in `src/lib/tds/macros/registry.ts`
- Follow pattern from existing macros like `subid.ts`

**New Pipeline Stage:**
- Add file in `src/lib/tds/pipeline/stages/{name}.ts`
- Register in `src/lib/tds/pipeline/pipeline.ts` stage array

**New UI Component:**
- For shadcn/ui additions: use `npx shadcn@latest add {component}`
- For custom components: create in `src/components/{name}.tsx`
- Import in `src/app/page.tsx` (single-page architecture)

**New Database Model:**
- Add to `prisma/schema.prisma`
- Run `bun run db:push` to sync
- Run `bun run db:generate` to update Prisma client

## Special Directories

**`reference/`** (57MB):
- Purpose: Original Keitaro PHP source code for developer reference
- Generated: No
- Committed: Yes (but should be removed — dead weight)
- Note: Not imported by the application

**`skills/`**:
- Purpose: Installed AI agent skill systems (EZ Agents, Ralph Zero)
- Generated: Yes (cloned during `bootstrap.sh`)
- Committed: No (gitignored)

**`upload/`**:
- Purpose: File upload directory
- Generated: No
- Committed: No (mounted volume, cannot be deleted)

**`db/`**:
- Purpose: SQLite database files
- Generated: Yes (by Prisma)
- Committed: No (`custom.db` gitignored)

**`node_modules/`**:
- Purpose: npm/bun dependencies
- Generated: Yes (by `bun install`)
- Committed: No (gitignored)

**`.planning/`**:
- Purpose: EZ Agents planning artifacts (codebase maps, phase plans)
- Generated: Yes (by EZ Agents commands)
- Committed: Yes (useful project documentation)

---

*Structure analysis: 2026-03-31*
