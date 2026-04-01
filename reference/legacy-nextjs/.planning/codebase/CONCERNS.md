# Concerns

**Analysis Date:** 2026-04-01

## Highest-Priority Concerns

### 1. Safe-page URL helpers still disagree on the route shape
- `src/lib/tds/pipeline/stages/check-bot.ts` redirects bots to `/api/safe`
- `src/app/api/safe/route.ts` is the only implemented safe-page route in the app tree
- `src/lib/tds/bot-detection.ts` still returns `/safe`, `/safe/debug`, `/safe/bot`, `/safe/security`, `/safe/error`, and `/safe/verify`

**Why it matters:**
The live pipeline points at the implemented route, but the older helper still emits paths that do not exist in the current `src/app/api/` tree. I could not find a caller for `getSafePageUrl()` outside `src/lib/tds/bot-detection.ts`, so this may be dormant today, but it is an easy regression point if that helper is reused later.

### 2. Admin mutation validation is uneven across the API surface
- Routes with zod-backed validation include `src/app/api/admin/campaigns/route.ts`, `src/app/api/admin/streams/route.ts`, `src/app/api/admin/offers/route.ts`, `src/app/api/admin/groups/route.ts`, `src/app/api/admin/labels/route.ts`, `src/app/api/admin/triggers/route.ts`, `src/app/api/admin/streams/filters/route.ts`, and `src/app/api/admin/streams/actions/route.ts`
- Other mutation routes still parse raw JSON and write directly to Prisma with little shape checking, including `src/app/api/admin/bot-rules/route.ts`, `src/app/api/admin/landings/route.ts`, `src/app/api/admin/publishers/route.ts`, `src/app/api/admin/domains/route.ts`, `src/app/api/admin/users/route.ts`, `src/app/api/admin/settings/route.ts`, `src/app/api/admin/conversions/route.ts`, and `src/app/api/admin/audit-logs/route.ts`

**Why it matters:**
Malformed payloads can still slip through in some admin flows, and the response contracts vary by endpoint. The current mix is workable, but it makes the admin UI and future maintenance harder because there is no single validation pattern to rely on.

### 3. Project docs still drift from the actual route inventory
- `AGENTS.md` says `src/app/api/` has 24 routes and `prisma/schema.prisma` has 22 models
- A current scan found 33 `route.ts` files under `src/app/api/` and 27 under `src/app/api/admin/`
- `docs/project_status.md` still says there are 23 total route handlers and 17/17 protected admin routes

**Why it matters:**
This repo is instruction-driven, so stale inventory numbers lead to bad assumptions fast. The architecture docs are useful, but the route counts and protection summaries need to be treated as approximate until they are regenerated from the live tree.

### 4. Admin auth remains broad and environment-dependent
- `src/lib/auth/admin-auth.ts` accepts Bearer tokens, raw `Authorization`, `X-API-Key`, and the hashed cookie session
- `checkAuth()` skips auth for localhost in non-production
- The session cookie is a deterministic SHA256 hash of the shared API key, not a per-session random secret

**Why it matters:**
This is fine for local development, but it is easy to overestimate how strong the session model is. The localhost bypass is convenient and intentionally documented, yet it also means auth behavior changes based on host header and environment rather than only on credentials.

## Runtime and Scaling Concerns

### 5. SQLite is still the first likely throughput ceiling
- Prisma is pointed at SQLite in `prisma/schema.prisma`, `src/lib/db.ts`, and `db/custom.db`
- Reporting-heavy endpoints such as `src/app/api/admin/stats/route.ts` and `src/app/api/admin/reports/route.ts` issue multiple `count`, `groupBy`, and `aggregate` queries per request

**Why it matters:**
The app is still a write-heavy click tracker wrapped around a single-file database. SQLite is a good fit for the current footprint, but click volume and analytics traffic will likely hit contention and latency limits before the business logic itself does.

### 6. In-memory caches are local and time-based only
- `src/lib/tds/bot-detection.ts` caches bot rules in memory for 5 minutes
- `src/lib/tds/macros.ts` caches file-backed macro content for 1 minute

**Why it matters:**
Admin edits can take time to appear, and the behavior can differ across long-lived Node processes because there is no shared invalidation layer. That is acceptable for now, but it is a real source of “why didn’t my change take effect?” confusion.

### 7. Route vocabularies still drift from the Prisma comments in a few spots
- `src/app/api/admin/campaigns/route.ts` accepts `safePageType` values `redirect`, `local`, and `direct`
- `prisma/schema.prisma` comments for `Campaign.safePageType` describe `redirect` and `content`
- `src/app/api/admin/streams/route.ts` accepts `type: regular | forced | fallback`
- `prisma/schema.prisma` comments for `Stream.type` describe `forced`, `regular`, and `default`

**Why it matters:**
These fields are strings rather than strict enums, so the mismatch will not fail loudly. That makes the drift easier to miss, but it also means UI labels, persisted data, and future parity work can diverge without obvious errors.

### 8. Build and test safety are still fairly light
- `next.config.ts` no longer ignores TypeScript build errors, which is good
- `reactStrictMode` is still `false`
- `package.json` has no `test` script
- No `.github/workflows/` directory was found
- `src/lib/tds/tests/verify-pipeline.ts` exists, but it is a standalone script rather than an automated test target

**Why it matters:**
The repo still leans on linting and manual verification. That is workable for a small team, but it leaves more room for regressions in the pipeline and admin APIs than a basic CI gate would.

## Lower-Priority Notes

### 9. Some older concerns are already fixed in the live tree
- `src/lib/tds/actions/repository.ts` now has a real `src/lib/tds/actions/predefined/local-file.ts`
- `src/app/api/click/json/route.ts` now delegates to the shared pipeline runner instead of importing the action repository directly
- `src/app/api/admin/stats/route.ts` now uses `checkAuth()` consistently
- Query-parameter auth was removed from `src/lib/auth/admin-auth.ts`

**Why it matters:**
These are worth noting so the next mapping pass does not keep stale debt on the board. They are not current concerns, but they explain why the older map no longer matches the repo.

## Summary
The most important current concerns are:
1. safe-page route drift between `src/lib/tds/bot-detection.ts` and the implemented `/api/safe` route
2. uneven admin-request validation across `src/app/api/admin/*`
3. stale repo docs and route-count inventory
4. broad, environment-sensitive admin auth
5. SQLite as the likely scaling ceiling
6. local-only caches with no shared invalidation
