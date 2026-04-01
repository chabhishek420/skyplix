# Codebase Structure

**Analysis Date:** 2026-04-01

## Top-Level Layout

```text
.
├── src/
│   ├── app/
│   ├── components/
│   ├── hooks/
│   └── lib/
├── prisma/
├── db/
├── public/
├── docs/
├── scripts/
├── tasks/
├── reference/
├── planning/
├── .planning/
├── package.json
├── next.config.ts
├── tsconfig.json
├── eslint.config.mjs
├── tailwind.config.ts
├── bootstrap.sh
└── worklog.md
```

## Source Layout

### `src/app/`
- `src/app/layout.tsx` is the root layout.
- `src/app/globals.css` is the global stylesheet.
- `src/app/page.tsx` is the public landing page at `/`.
- `src/app/(auth)/login/page.tsx` is the login page.
- `src/app/(admin)/admin/layout.tsx` and `src/app/(admin)/admin/page.tsx` anchor the admin UI.
- `src/app/api/**/route.ts` contains the HTTP surface.

### `src/app/api/`
This directory currently contains 33 route files.

Key route groups:
- `src/app/api/click/`
- `src/app/api/click/json/`
- `src/app/api/postback/`
- `src/app/api/lp/offer/`
- `src/app/api/safe/`
- `src/app/api/admin/`

Notable admin subpaths:
- `src/app/api/admin/campaigns/route.ts`
- `src/app/api/admin/streams/route.ts`
- `src/app/api/admin/streams/actions/route.ts`
- `src/app/api/admin/streams/filters/route.ts`
- `src/app/api/admin/offers/route.ts`
- `src/app/api/admin/landings/route.ts`
- `src/app/api/admin/publishers/route.ts`
- `src/app/api/admin/domains/route.ts`
- `src/app/api/admin/traffic-sources/route.ts`
- `src/app/api/admin/bot-rules/route.ts`
- `src/app/api/admin/affiliate-networks/route.ts`
- `src/app/api/admin/users/route.ts`
- `src/app/api/admin/stats/route.ts`
- `src/app/api/admin/reports/route.ts`
- `src/app/api/admin/clicks/route.ts`
- `src/app/api/admin/conversions/route.ts`
- `src/app/api/admin/audit-logs/route.ts`
- `src/app/api/admin/login/route.ts`
- `src/app/api/admin/logout/route.ts`
- `src/app/api/admin/groups/route.ts`
- `src/app/api/admin/labels/route.ts`
- `src/app/api/admin/triggers/route.ts`
- `src/app/api/admin/templates/affiliate-networks/route.ts`
- `src/app/api/admin/templates/traffic-sources/route.ts`
- `src/app/api/admin/integrations/appsflyer/route.ts`
- `src/app/api/admin/integrations/facebook/route.ts`

### `src/components/`
- `src/components/admin/*` contains the admin shell, nav, and page sections.
- `src/components/ui/*` contains the shared shadcn/ui primitives.
- I did not find standalone shared React components directly under `src/components/*.tsx` during this scan.

Useful admin component folders:
- `src/components/admin/layout/`
- `src/components/admin/nav/`
- `src/components/admin/dashboard/`
- `src/components/admin/shared/`

### `src/hooks/`
- `src/hooks/use-toast.ts`
- `src/hooks/use-mobile.ts`

### `src/lib/`
- `src/lib/db.ts` is the Prisma client singleton.
- `src/lib/utils.ts` contains general helpers.
- `src/lib/auth/*` contains admin authentication helpers.
- `src/lib/admin/*` contains admin navigation, registry, and config helpers.
- `src/lib/tds/*` contains the traffic distribution engine.

Important `src/lib/tds/` subtrees:
- `src/lib/tds/pipeline/`
- `src/lib/tds/pipeline/stages/`
- `src/lib/tds/actions/`
- `src/lib/tds/actions/predefined/`
- `src/lib/tds/macros/`
- `src/lib/tds/macros/predefined/`
- `src/lib/tds/filters/`
- `src/lib/tds/services/`
- `src/lib/tds/contexts/`
- `src/lib/tds/data/`
- `src/lib/tds/tests/`
- `src/lib/tds/utils/`

### `prisma/`
- `prisma/schema.prisma` is the canonical schema.
- `prisma/seed.ts` is the seeding entrypoint.
- `prisma/db/custom.db` is present in the tree.

### `db/`
- `db/custom.db` is the runtime SQLite database file referenced by project docs and bootstrap scripts.

### `public/`
- `public/logo.svg`
- `public/robots.txt`

### `scripts/`
- `scripts/deepinit-generate-agents.mjs`
- `scripts/search_repo.sh`
- `scripts/validate-all.sh`
- `scripts/validate-skills.sh`
- `scripts/validate-workflows.sh`
- PowerShell equivalents for the same maintenance tasks

### `docs/`
- Operational and verification material lives here, including `docs/runbook.md`, `docs/VERIFICATION_REPORT.md`, and `docs/admin-frontend-structure.md`.

### `reference/`
- Archived source material, research outputs, and copied upstream assets live here.
- This directory is useful for parity/reference work, but it is not the primary runtime path.

### `tasks/`
- `tasks/todo.md`
- `tasks/lessons.md`

## Naming And Placement Rules
- App Router pages and handlers follow filesystem routing, for example `src/app/(admin)/admin/campaigns/page.tsx` and `src/app/api/admin/campaigns/route.ts`.
- Library files are mostly kebab-case, for example `src/lib/tds/click-processor.ts` and `src/lib/auth/admin-auth.ts`.
- Admin UI components are grouped by concern instead of being flattened into a single components directory.

## Where Things Belong
- Put public UI and route groups in `src/app/`.
- Put reusable React composition and shell components in `src/components/admin/`.
- Put generic primitives in `src/components/ui/`.
- Put auth, Prisma access, and TDS logic in `src/lib/`.
- Put schema and seed changes in `prisma/`.
- Put runtime SQLite data in `db/`.
- Put repo tooling and validation helpers in `scripts/`.
- Put research and operational notes in `docs/`, `reference/`, or `tasks/` depending on whether the material is active, archived, or task-oriented.

## Structure Notes
- The repo mixes live app code, archived reference payloads, and workflow tooling in one tree.
- The UI is relatively centralized, while the backend is split across many route handlers and a deeper `src/lib/tds/` hierarchy.
- The schema has grown beyond the older 22-model snapshot; it now includes `Group`, `Label`, and `Trigger` in addition to the core traffic tables.

## Summary
If you need to orient on the live product, start with:
- `src/app/page.tsx`
- `src/app/(admin)/admin/page.tsx`
- `src/app/api/click/route.ts`
- `src/app/api/lp/offer/route.ts`
- `src/app/api/postback/route.ts`
- `src/lib/tds/pipeline/runner.ts`
- `src/lib/auth/admin-auth.ts`
- `src/lib/db.ts`
- `prisma/schema.prisma`
