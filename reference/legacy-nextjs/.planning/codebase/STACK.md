# Technology Stack

**Analysis Date:** 2026-04-01

## Core Runtime

| Area | Current choice | Evidence |
|---|---|---|
| Framework | Next.js 16 App Router | `package.json`, `src/app/`, `next.config.ts` |
| Rendering model | Mixed App Router with one client landing page | `src/app/page.tsx`, `src/app/layout.tsx` |
| Language | TypeScript 5 | `package.json`, `tsconfig.json` |
| UI runtime | React 19 | `package.json` |
| Package manager | Bun | `bun.lock`, `package.json` scripts |
| ORM | Prisma 6 | `package.json`, `prisma/schema.prisma`, `src/lib/db.ts` |
| Database | SQLite | `prisma/schema.prisma`, `.env.example`, `db/custom.db` |
| Build target | Standalone Next output | `next.config.ts`, `package.json` |

## Active Application Shape

- The visible app shell is a single client page in `src/app/page.tsx`; the shared document wrapper and fonts live in `src/app/layout.tsx`.
- Admin UI pages exist under `src/app/(admin)/admin/*`, with shared admin layout/components under `src/components/admin/*`.
- All server-side behavior is implemented with Next.js route handlers under `src/app/api/**/route.ts`; I found **33 route files** total, including **27** under `src/app/api/admin/`.
- The central traffic-routing logic lives in `src/lib/tds/`, especially `src/lib/tds/pipeline/`, `src/lib/tds/actions/`, `src/lib/tds/filters/`, `src/lib/tds/macros/`, and `src/lib/tds/services/`.

## Frontend Stack

| Concern | Current signal | Notes |
|---|---|---|
| Styling | Tailwind CSS 4 | `package.json`, `components.json`, `src/app/globals.css` |
| Component system | shadcn/ui, New York variant | `components.json`, `src/components/ui/*` |
| Primitive layer | Radix UI packages | `package.json`, `src/components/ui/*` |
| Icons | Lucide React | `package.json`, `src/app/page.tsx` |
| Toasts | shadcn/toaster + Sonner package | `src/app/layout.tsx`, `src/components/ui/toaster.tsx`, `package.json` |
| Forms | React Hook Form + Zod resolver | `package.json`, `src/components/ui/form.tsx`, `src/app/api/admin/*` |

## Backend / Data Stack

- Prisma client singleton is defined in `src/lib/db.ts` and uses query/warn/error logging, not full query spam.
- Prisma schema is in `prisma/schema.prisma`; the runtime SQLite file is `db/custom.db`, and a second copy exists at `prisma/db/custom.db` in this workspace.
- The schema currently defines the core TDS entities plus supporting tables such as `Setting`, `AuditLog`, `Group`, `Label`, and `Trigger` in `prisma/schema.prisma`.
- Auth is custom, not NextAuth-backed, despite `next-auth` being installed. The active auth helpers live in `src/lib/auth/admin-auth.ts` and `src/lib/auth/index.ts`.
- Admin mutations commonly validate with Zod, for example in `src/app/api/admin/campaigns/route.ts`, `src/app/api/admin/streams/route.ts`, `src/app/api/admin/offers/route.ts`, and the integration routes.

## TDS / Domain Libraries

- The traffic distribution engine is centered in `src/lib/tds/pipeline/pipeline.ts` and `src/lib/tds/pipeline/runner.ts`.
- Request enrichment and cloaking helpers are split into `src/lib/tds/services/*`, `src/lib/tds/contexts/*`, and `src/lib/tds/bot-detection.ts`.
- Static lookup data for geo/device/browser logic lives in `src/lib/tds/data/*`.
- Domain-specific runtime files include `src/lib/tds/click-processor.ts`, `src/lib/tds/click-id.ts`, `src/lib/tds/rotator.ts`, and `src/lib/tds/macros.ts`.

## Tooling And Config

| Area | Current signal | Notes |
|---|---|---|
| TypeScript | `strict: true`, `noImplicitAny: false` | `tsconfig.json` |
| Module resolution | Bundler mode with `@/*` alias | `tsconfig.json` |
| Next config | `output: "standalone"`, `reactStrictMode: false`, build errors not ignored | `next.config.ts` |
| shadcn config | `new-york`, `rsc: true`, CSS variables on | `components.json` |
| Scripts | `dev`, `build`, `start`, `lint`, `db:*` | `package.json` |

## Dependency Signals

### Actively imported in `src/`
- `zod`
- `bcrypt`
- `lucide-react`
- `react-hook-form`
- `@prisma/client`
- `next`
- `react`
- `react-dom`

### Installed but not imported under `src/` during this scan
- `z-ai-web-dev-sdk`
- `next-auth`
- `next-intl`
- `zustand`
- `@tanstack/react-query`
- `@tanstack/react-table`
- `framer-motion`
- `@mdxeditor/editor`
- `@dnd-kit/*`
- `recharts`

I did not find current runtime usage for those packages under `src/`; they may be reserved for future work or historical parity.

## File References Worth Keeping Handy

- `package.json`
- `bun.lock`
- `tsconfig.json`
- `next.config.ts`
- `components.json`
- `src/app/page.tsx`
- `src/app/layout.tsx`
- `src/lib/db.ts`
- `src/lib/auth/admin-auth.ts`
- `src/lib/tds/pipeline/pipeline.ts`
- `src/lib/tds/pipeline/runner.ts`
- `prisma/schema.prisma`
- `db/custom.db`

## Summary

The live stack is a **Next.js 16 + React 19 + TypeScript + Prisma + SQLite** traffic-distribution app with a single client landing page, a broad App Router API surface, shadcn/ui-based admin UI, and a custom auth/data layer in `src/lib/`. Several packages are present in `package.json` but do not currently appear in the runtime import graph.
