# Codebase Conventions

**Analysis Date:** 2026-04-01

## High-Level Shape

- This is a Next.js 16 App Router codebase with most live behavior split between `src/app/`, `src/components/`, and `src/lib/`.
- Public UI currently lives in `src/app/page.tsx`, while the admin shell is organized under route groups like `src/app/(admin)/admin/` and auth UI under `src/app/(auth)/login/`.
- The traffic-distribution engine lives under `src/lib/tds/` and is consumed by thin route handlers such as `src/app/api/click/route.ts` and `src/app/api/click/json/route.ts`.

## Naming And Placement

- Route handlers follow the App Router convention of `route.ts` in path-shaped directories, for example `src/app/api/admin/campaigns/route.ts`, `src/app/api/admin/streams/route.ts`, and `src/app/api/admin/stats/route.ts`.
- Shared modules usually use kebab-case filenames, such as `src/lib/auth/admin-auth.ts`, `src/lib/tds/click-processor.ts`, and `src/lib/tds/pipeline/runner.ts`.
- UI primitives under `src/components/ui/` also use kebab-case, matching the shadcn/ui generator output in files like `src/components/ui/button.tsx`, `src/components/ui/dialog.tsx`, and `src/components/ui/table.tsx`.
- Feature directories are typically lowercase or kebab-case, including `src/lib/auth/`, `src/lib/tds/actions/`, `src/components/admin/`, and `prisma/`.

## TypeScript And Import Style

- TypeScript is strict at the project level, but `noImplicitAny` is explicitly disabled in `tsconfig.json`.
- The repo uses the `@/*` path alias for internal imports, mapped to `./src/*`.
- Existing code prefers alias imports over long relative paths, such as `@/lib/db`, `@/lib/auth`, `@/components/ui/button`, and `@/hooks/use-toast`.
- `React` components and exported types use `PascalCase`, while functions and local variables are generally `camelCase`.
- Prisma models are `PascalCase`, while Prisma field names are `camelCase`, as seen in `prisma/schema.prisma`.

## React And Next Patterns

- Most server logic stays in route handlers or `src/lib/` helpers rather than in page components.
- `src/app/page.tsx` is a client component and currently contains a lot of local UI state, dialog wiring, fetch calls, and tabbed admin dashboard logic in one file.
- The app uses the App Router's route groups to separate concerns without changing URLs, for example `src/app/(admin)/admin/` and `src/app/(auth)/login/`.
- Root app styling and typography live in `src/app/globals.css`, and the root layout in `src/app/layout.tsx` mounts the global toaster.
- `src/app/layout.tsx` currently uses `Geist` and `Geist_Mono` from `next/font/google`, so font selection is centralized there.

## UI Conventions

- The project uses shadcn/ui in the `new-york` style, confirmed by `components.json`.
- Tailwind CSS v4 is used through `src/app/globals.css` with CSS variables and `@theme inline`.
- Dark UI surfaces lean on slate backgrounds and emerald accents, especially in `src/app/page.tsx`, which uses classes like `bg-slate-950`, `bg-slate-900/50`, `border-slate-700`, and `text-emerald-400`.
- Shared UI building blocks are preferred over bespoke component systems, especially in `src/components/ui/`.
- The root layout applies global body styling and keeps the UI shell lightweight.

## API Handler Conventions

- Route handlers usually export HTTP verbs directly, such as `GET`, `POST`, `PUT`, and `DELETE`.
- Handlers typically accept `NextRequest` and return `NextResponse`.
- Admin routes usually start with `checkAuth(request)` from `src/lib/auth` and return early if authentication fails.
- Representative examples include `src/app/api/admin/campaigns/route.ts`, `src/app/api/admin/streams/route.ts`, `src/app/api/admin/affiliate-networks/route.ts`, and `src/app/api/admin/conversions/route.ts`.
- A few routes use different auth helpers or special-case bypasses, such as `src/app/api/admin/stats/route.ts`, which combines `checkAuth(request)` with local-development behavior in `src/lib/auth/admin-auth.ts`.

## Validation And Data Shape

- Zod is actively used in several admin mutation routes, including `src/app/api/admin/campaigns/route.ts`, `src/app/api/admin/streams/route.ts`, `src/app/api/admin/labels/route.ts`, and `src/app/api/admin/streams/filters/route.ts`.
- Those routes generally parse `await request.json()`, run `safeParse`, and return a `400` with formatted validation details when input is invalid.
- Validation is not completely uniform across the API surface yet. Some routes still accept request bodies directly and rely on manual field checks rather than a shared schema layer.
- Stringified nested fields still appear in a few places, for example `actionOptions` and `payload` in stream-filter routes, where `JSON.parse(...)` is used for additional validation.
- If you add new mutation routes, the safest local pattern is `request.json()` plus `zod` validation before any Prisma write.

## Auth And Session Conventions

- Admin auth is centralized in `src/lib/auth/admin-auth.ts`.
- The supported auth paths are header-based `Authorization: Bearer ...`, direct `Authorization` key usage, `X-API-Key`, and a hashed `admin_session` cookie.
- The cookie value is SHA256-based rather than storing the raw API key.
- Query-parameter auth was intentionally removed in `src/lib/auth/admin-auth.ts`, and the code comments treat that as a security hardening decision.
- `checkAuth(request)` is the most common route-level guard for admin APIs.

## Error Handling

- Route handlers generally wrap business logic in `try/catch` and log failures with `console.error(...)`.
- API responses are returned as JSON, but the exact shape varies by route.
- Some handlers use `{ success: true, ... }` envelopes, while others return flatter payloads such as `{ campaigns }` or `{ error: 'Failed to fetch stats' }`.
- The traffic pipeline and supporting services also rely on `console.error(...)` for operational failures, including `src/lib/tds/pipeline/runner.ts`, `src/lib/tds/click-processor.ts`, and several service modules under `src/lib/tds/services/`.
- If you need consistency in a new area, match the nearby route rather than assuming a global response contract.

## Shared Infrastructure Patterns

- Prisma access is centralized in `src/lib/db.ts`, which caches a single client on `globalThis` outside production.
- The pipeline adapter in `src/lib/tds/pipeline/runner.ts` is a thin bridge from `NextRequest` to the TDS engine and back to `NextResponse`.
- The TDS codebase is split into focused subtrees under `src/lib/tds/`, including `actions/`, `macros/`, `pipeline/`, `contexts/`, `services/`, and `utils/`.
- Some helper modules expose barrel exports, but barrel usage is not universal. Examples include `src/lib/auth/index.ts`, `src/lib/tds/index.ts`, `src/lib/tds/actions/index.ts`, and `src/lib/tds/macros/index.ts`.

## Scripts And Tooling

- The root `package.json` exposes `bun run dev`, `bun run lint`, and Prisma database scripts, but no app test runner script.
- `bun run dev` writes to `dev.log`, and project instructions explicitly treat that file as the first place to check for runtime errors.
- `bun run build` exists in `package.json`, but the project instructions discourage using it in this environment.
- Repository maintenance scripts live in `scripts/`, including `scripts/deepinit-generate-agents.mjs` and validation helpers such as `scripts/validate-all.sh`.

## Documentation And Comments

- Many backend and TDS files use module-level comments to explain lineage or intent, such as `src/lib/auth/admin-auth.ts`, `src/lib/tds/pipeline/runner.ts`, and `src/app/api/admin/campaigns/route.ts`.
- Longer system files sometimes use section dividers to make large modules easier to scan, especially in the pipeline and Prisma schema areas.
- The repo leans on descriptive file-level structure more than heavy inline commentary.

## Practical Defaults

- Use `route.ts` for new API endpoints and keep handlers thin.
- Prefer `@/*` imports.
- Use `checkAuth(request)` for admin endpoints unless a nearby route uses a different pattern.
- Validate mutation bodies with `zod` before writes.
- Build UI from `src/components/ui/*` and keep page shells aligned with the existing dark slate and emerald visual language.
- When behavior is uncertain or a route is an exception, follow the nearest existing file instead of inventing a new convention.
