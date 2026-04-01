# Architecture

**Analysis Date:** 2026-04-01

## System Shape
The live app is a Next.js App Router TDS with three visible surfaces:
- public marketing/landing UI at `src/app/page.tsx`
- auth entry at `src/app/(auth)/login/page.tsx`
- admin UI under `src/app/(admin)/admin/*`

HTTP traffic is handled through route handlers in `src/app/api/**/route.ts`, and the traffic-handling endpoints now delegate into the pipeline runner in `src/lib/tds/pipeline/runner.ts`.

```text
Browser / external networks
  -> src/app/api/*/route.ts
  -> src/lib/auth/* or src/lib/tds/pipeline/*
  -> src/lib/db.ts
  -> Prisma
  -> SQLite (DATABASE_URL, documented as file:./db/custom.db)
```

## Execution Entry Points
- `src/app/page.tsx` is the public `/` entry point.
- `src/app/(auth)/login/page.tsx` is the login page for admin access.
- `src/app/(admin)/admin/layout.tsx` and `src/app/(admin)/admin/page.tsx` anchor the admin shell.
- `src/app/api/route.ts` is a base API placeholder.
- `src/app/api/click/route.ts`, `src/app/api/click/json/route.ts`, `src/app/api/postback/route.ts`, `src/app/api/lp/offer/route.ts`, and `src/app/api/safe/route.ts` are the main traffic endpoints.
- `package.json` defines the local runtime commands: `bun run dev`, `bun run lint`, `bun run db:push`, and `bun run db:generate`.

## Request And Data Flow
### Click Flow
`src/app/api/click/route.ts` and `src/app/api/click/json/route.ts` both call `src/lib/tds/pipeline/runner.ts`.

The active flow is:
`NextRequest` -> `Payload.fromRequest(request)` -> `Pipeline.createDefault()` -> `runFirstLevel()` -> `pipelinePayloadToResponse()` or `pipelinePayloadToJsonResponse()`.

That means the pipeline is the current request path, not just a dormant library. The older direct processor still exists at `src/lib/tds/click-processor.ts`, but I did not find a current caller outside `src/lib/tds/index.ts`.

### LP To Offer Flow
`src/app/api/lp/offer/route.ts` uses two paths:
- `GET` delegates to `runSecondLevelPipeline(request)` for token-based landing-to-offer routing.
- `POST` does lightweight click lookup/update directly with Prisma via `src/lib/db.ts`.

### Postback Flow
`src/app/api/postback/route.ts` parses `clickid`, `status`, `payout`, and transaction IDs, validates the click ID via `src/lib/tds`, and writes `Conversion` rows directly through Prisma.

### Safe Page Flow
`src/app/api/safe/route.ts` serves the cloaked/safe page. It reads `safe_page_content` and `safe_page_url` from `Setting` records through `src/lib/db.ts` and falls back to a minimal HTML stub if nothing is configured.

## Route Organization
There are 33 route files under `src/app/api/`, including 27 admin routes.

### Public Traffic Routes
- `src/app/api/click/route.ts`
- `src/app/api/click/json/route.ts`
- `src/app/api/postback/route.ts`
- `src/app/api/lp/offer/route.ts`
- `src/app/api/safe/route.ts`
- `src/app/api/route.ts`

### Admin API Routes
The admin API surface lives under `src/app/api/admin/*`, including:
- CRUD endpoints such as `src/app/api/admin/campaigns/route.ts`, `src/app/api/admin/streams/route.ts`, `src/app/api/admin/offers/route.ts`, `src/app/api/admin/landings/route.ts`, `src/app/api/admin/publishers/route.ts`, `src/app/api/admin/domains/route.ts`, `src/app/api/admin/traffic-sources/route.ts`, and `src/app/api/admin/users/route.ts`
- operational endpoints such as `src/app/api/admin/clicks/route.ts`, `src/app/api/admin/conversions/route.ts`, `src/app/api/admin/stats/route.ts`, `src/app/api/admin/reports/route.ts`, and `src/app/api/admin/audit-logs/route.ts`
- auth endpoints `src/app/api/admin/login/route.ts` and `src/app/api/admin/logout/route.ts`
- nested admin areas such as `src/app/api/admin/streams/actions/route.ts`, `src/app/api/admin/streams/filters/route.ts`, `src/app/api/admin/integrations/appsflyer/route.ts`, `src/app/api/admin/integrations/facebook/route.ts`, `src/app/api/admin/templates/affiliate-networks/route.ts`, `src/app/api/admin/templates/traffic-sources/route.ts`, `src/app/api/admin/groups/route.ts`, `src/app/api/admin/labels/route.ts`, and `src/app/api/admin/triggers/route.ts`

## Shared Libraries
### Core TDS Library
`src/lib/tds/` is the main domain package. It currently exposes:
- direct helpers: `src/lib/tds/click-id.ts`, `src/lib/tds/bot-detection.ts`, `src/lib/tds/rotator.ts`, `src/lib/tds/click-processor.ts`, `src/lib/tds/macros.ts`
- pipeline engine: `src/lib/tds/pipeline/*`
- pipeline stages: `src/lib/tds/pipeline/stages/*`
- actions: `src/lib/tds/actions/*`
- macros: `src/lib/tds/macros/*`
- filters: `src/lib/tds/filters/*`
- services: `src/lib/tds/services/*`
- contexts: `src/lib/tds/contexts/*`
- static data and lookup tables: `src/lib/tds/data/*`

The pipeline runner is the current orchestration boundary:
- `src/lib/tds/pipeline/runner.ts`
- `src/lib/tds/pipeline/pipeline.ts`
- `src/lib/tds/pipeline/payload.ts`

### Admin And Auth Helpers
- `src/lib/admin/*` holds admin-side navigation, module registry, and JS config helpers.
- `src/lib/auth/admin-auth.ts` centralizes API-key and cookie authentication.
- `src/lib/auth/index.ts` is the barrel export used by admin routes.

### UI Support
- `src/components/admin/*` contains the admin shell, layout, nav, and section components.
- `src/components/ui/*` contains the shadcn/ui primitive set.
- `src/hooks/use-toast.ts` and `src/hooks/use-mobile.ts` provide client helpers.

## Persistence Boundary
- `prisma/schema.prisma` is the canonical data model.
- `src/lib/db.ts` is the Prisma singleton.
- `db/custom.db` is the checked-in SQLite database file referenced by docs and bootstrap scripts.
- `prisma/db/custom.db` also exists in the tree; I did not verify whether it is still used at runtime or is an artifact from prior work.

The schema currently defines 25 models, including `User`, `Session`, `Campaign`, `Stream`, `Landing`, `Offer`, `Click`, `Conversion`, `Setting`, `AuditLog`, `Group`, `Label`, and `Trigger`.

## Security And Auth Notes
- Admin auth is header/cookie based and implemented in `src/lib/auth/admin-auth.ts`.
- `checkAuth(request)` is the common route guard.
- `verifyAdminAuth(request)` is used where a raw auth result is needed.
- Query-string auth is intentionally not used in the current auth helper.
- `src/app/api/admin/stats/route.ts` uses the shared auth helper directly.

## Uncertainties
- I did not find a current route caller for `src/lib/tds/click-processor.ts`, so its status is unclear beyond being exported from `src/lib/tds/index.ts`.
- `src/lib/tds/pipeline/*` is clearly wired into click and LP-to-offer routes, but some of the lower-level stage and action modules may still be partially overlapping with older direct helpers.
- The repo contains both `db/custom.db` and `prisma/db/custom.db`; the active runtime DB file is determined by `DATABASE_URL`, and I did not verify which copy is authoritative for every workflow.

## Summary
The codebase is best understood as a Next.js dashboard plus API-driven TDS platform. The current runtime path is:
`src/app/page.tsx` / `src/app/(admin)/admin/*` for UI, `src/app/api/*` for request handling, `src/lib/tds/pipeline/runner.ts` for traffic orchestration, and `src/lib/db.ts` plus `prisma/schema.prisma` for persistence.
