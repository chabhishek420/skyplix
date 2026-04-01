# Testing

**Analysis Date:** 2026-04-01

## Current State

- There is no root app test runner configured in `package.json`.
- No `test`, `test:unit`, `test:integration`, or `test:e2e` script exists today.
- No root Jest, Vitest, Playwright, or Cypress config was found.
- The live application under `src/` does not currently contain a conventional test suite with `*.test.ts`, `*.spec.ts`, or `__tests__/` coverage.

## What Exists Instead

- Verification is mostly linting, manual runtime checks, and targeted utility scripts.
- The root scripts currently available are `bun run dev`, `bun run lint`, and the Prisma commands in `package.json`.
- `bun run dev` runs the app on port 3000 and tees output into `dev.log`, which is the main place to inspect runtime errors.
- `bun run lint` is the only explicit quality gate that resembles automated verification for the app code.
- Prisma scripts such as `bun run db:push`, `bun run db:generate`, and `bun run db:reset` verify schema/tooling behavior, but they are not application tests.

## Observable Test-Like Artifacts

- `src/lib/tds/tests/verify-pipeline.ts` exists and behaves like a standalone verification script, not a formal test harness wired into `package.json`.
- That script exercises pipeline scenarios directly with `console.log(...)` and exits on failure, which makes it useful for manual parity checks but not for CI-style regression testing.
- No app-level CI workflow was found under `.github/workflows/`.
- Files under `reference/` contain many vendor or upstream project tests, but those do not exercise the live TypeScript app in `src/`.

## Pattern Of Manual Verification

- API routes are usually checked by running `bun run dev` and hitting the changed endpoint directly.
- Admin routes often require auth headers or a browser session cookie, so manual verification has to include both authenticated and unauthenticated requests.
- The public dashboard at `src/app/page.tsx` is the main visible UI surface, so browser smoke checks against `/` are the most common UI verification path.
- The project instructions also call out `dev.log` as the first diagnostic source when a route or page misbehaves.

## What The Codebase Suggests As Risk Areas

- Traffic handling is the highest-value and highest-risk area, especially `src/app/api/click/route.ts`, `src/app/api/click/json/route.ts`, `src/app/api/postback/route.ts`, and `src/lib/tds/pipeline/runner.ts`.
- Admin auth is sensitive because `src/lib/auth/admin-auth.ts` supports multiple transport paths and a localhost development bypass in `checkAuth(request)`.
- Admin CRUD handlers are numerous under `src/app/api/admin/`, and their request bodies are not uniformly schema-validated yet.
- The single-page dashboard in `src/app/page.tsx` depends on several admin APIs, so response-shape regressions show up there quickly.
- Pipeline and macro behavior under `src/lib/tds/` is complex enough that pure unit coverage would likely be valuable, but it does not exist yet.

## Specific Gaps

- There is no formal regression coverage for route handlers in `src/app/api/`.
- There is no coverage for auth header parsing, cookie session handling, or localhost skip-auth behavior in `src/lib/auth/admin-auth.ts`.
- There is no coverage for click identifier generation, macro expansion, or pipeline stage ordering in `src/lib/tds/`.
- There is no browser automation for the dashboard layout or admin interactions in `src/app/page.tsx` and `src/app/(admin)/admin/`.
- There is no visible coverage report or threshold enforcement.

## Best First Tests If A Suite Is Added

- `src/lib/tds/click-id.ts` for deterministic edge cases.
- `src/lib/auth/admin-auth.ts` for auth transports, cookie generation, and localhost bypass behavior.
- `src/lib/tds/pipeline/runner.ts` and `src/lib/tds/click-processor.ts` for the main click flow.
- `src/app/api/admin/campaigns/route.ts` and `src/app/api/admin/streams/route.ts` for schema validation and CRUD behavior.
- `src/app/api/postback/route.ts` for conversion updates and idempotency-related behavior.
- `src/app/page.tsx` for the dashboard's API contracts and loading states.

## Practical Verification Workflow Today

1. Run `bun run lint` after code changes.
2. Run `bun run dev` and watch `dev.log` for runtime errors.
3. Manually call the modified route or exercise the changed page in the browser.
4. Use Prisma scripts when the change touches schema or persistence.
5. For pipeline changes, run or adapt `src/lib/tds/tests/verify-pipeline.ts` as a manual script until a real harness exists.

## Uncertainties

- I did not find evidence of an app-wide test harness, but it is possible one exists outside the repo root or in an untracked local workflow.
- I did not find CI configuration in the repository, so automated execution on push or PR is currently unconfirmed rather than proven absent.
- The manual verification script in `src/lib/tds/tests/verify-pipeline.ts` may evolve into a formal test later, but today it reads like a standalone developer tool.

## Summary

- The live app currently relies on linting, Prisma commands, `bun run dev`, and manual route/page checks.
- There is no conventional automated test suite in `src/` yet.
- The highest-value future coverage would likely start with auth helpers, click/pipeline logic, and a few representative admin route handlers.
