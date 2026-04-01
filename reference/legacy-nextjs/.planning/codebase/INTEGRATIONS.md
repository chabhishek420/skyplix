# External Integrations

**Analysis Date:** 2026-04-01

## Active Integrations

### 1. SQLite via Prisma
- The datasource is SQLite in `prisma/schema.prisma`.
- Prisma client is instantiated in `src/lib/db.ts`.
- Runtime storage points at `db/custom.db`; the seed script is `prisma/seed.ts`.
- The repo also contains `prisma/db/custom.db`, which looks like a second generated copy in this workspace.

**Relevant files:**
- `prisma/schema.prisma`
- `src/lib/db.ts`
- `prisma/seed.ts`
- `db/custom.db`

### 2. Browser UI → Internal Admin APIs
- The public-facing shell in `src/app/page.tsx` is a client component, but most operational UI is in the admin routes/pages under `src/app/(admin)/admin/*`.
- The admin pages talk to internal JSON endpoints under `src/app/api/admin/*` for campaigns, streams, offers, publishers, stats, reports, clicks, conversions, settings, and more.
- Key route handlers are grouped in `src/app/api/admin/` and protected with `checkAuth(request)` from `src/lib/auth`.

**Relevant files:**
- `src/app/page.tsx`
- `src/app/(admin)/admin/page.tsx`
- `src/app/api/admin/stats/route.ts`
- `src/app/api/admin/campaigns/route.ts`
- `src/app/api/admin/streams/route.ts`
- `src/lib/auth/admin-auth.ts`

### 3. Admin Authentication
- The active auth system is custom API-key based, not NextAuth-backed.
- Accepted transports in `src/lib/auth/admin-auth.ts` are `Authorization: Bearer ...`, raw `Authorization`, `X-API-Key`, and the hashed `admin_session` cookie.
- `POST /api/admin/login` creates the cookie session, and `GET/POST /api/admin/logout` clears it.

**Relevant files:**
- `src/lib/auth/admin-auth.ts`
- `src/lib/auth/index.ts`
- `src/app/api/admin/login/route.ts`
- `src/app/api/admin/logout/route.ts`

## Traffic / Proxy Integrations

### 4. Reverse Proxy and Edge Headers
- The traffic routes read edge/proxy headers such as `cf-connecting-ip`, `cf-ipcountry`, `x-forwarded-for`, and `x-real-ip`.
- `bootstrap.sh` writes a Caddy gateway that forwards traffic to `localhost:3000`, or to a transformed port via `?XTransformPort=...`.

**Relevant files:**
- `src/app/api/click/route.ts`
- `src/app/api/click/json/route.ts`
- `src/app/api/postback/route.ts`
- `src/app/api/safe/route.ts`
- `bootstrap.sh`

### 5. GeoIP / Bot Detection Inputs
- Geo enrichment is built to use MaxMind databases from `MAXMIND_DB_PATH` and can fall back to `ip-api.com` in non-production paths.
- `GEOIP_ENABLED` toggles IP enrichment in `src/lib/tds/services/ip-info-service.ts`.
- `src/lib/tds/services/proxy-service.ts` and `src/lib/tds/bot-detection.ts` combine header, IP-range, and user-agent signals.
- I did not find checked-in MaxMind database files in the repo, so this integration likely depends on local operator-provided data.

**Relevant files:**
- `src/lib/tds/services/geo-db-service.ts`
- `src/lib/tds/services/ip-info-service.ts`
- `src/lib/tds/services/proxy-service.ts`
- `src/lib/tds/bot-detection.ts`

## Affiliate / Tracking Integrations

### 6. Affiliate/Postback Flow
- `src/app/api/postback/route.ts` accepts GET and POST postbacks from affiliate networks, normalizes status values, and writes conversions into Prisma.
- `src/app/api/lp/offer/route.ts` handles landing-page-to-offer tracking and updates click records.
- `src/app/api/click/route.ts` and `src/app/api/click/json/route.ts` are the primary traffic entrypoints for Keitaro-style click processing.

**Relevant files:**
- `src/app/api/postback/route.ts`
- `src/app/api/lp/offer/route.ts`
- `src/app/api/click/route.ts`
- `src/app/api/click/json/route.ts`
- `src/lib/tds/click-processor.ts`

### 7. Affiliate Network Model
- The Prisma `AffiliateNetwork` model stores network credentials and tracking parameter names in `prisma/schema.prisma`.
- Template routes provide preset parameter mappings for common networks such as HasOffers, Impact, Affise, CAKE, and Everflow.
- These templates are static config helpers, not live SDK integrations.

**Relevant files:**
- `prisma/schema.prisma`
- `src/app/api/admin/templates/affiliate-networks/route.ts`
- `src/app/api/admin/affiliate-networks/route.ts`

### 8. Traffic Source Templates
- `src/app/api/admin/templates/traffic-sources/route.ts` exposes preset source mappings for PropellerAds, ExoClick, MGID, Facebook, and Google Ads.
- These are config presets for admin setup, not direct API integrations with those vendors.

**Relevant files:**
- `src/app/api/admin/templates/traffic-sources/route.ts`
- `prisma/schema.prisma`

### 9. Integration Settings Stored In DB
- `src/app/api/admin/integrations/appsflyer/route.ts` stores AppsFlyer settings in `Setting` rows prefixed with `integration_appsflyer_`.
- `src/app/api/admin/integrations/facebook/route.ts` stores Facebook settings in `Setting` rows prefixed with `integration_facebook_`.
- I did not find outbound API calls to AppsFlyer or Facebook in `src/`; these routes currently persist configuration only.

**Relevant files:**
- `src/app/api/admin/integrations/appsflyer/route.ts`
- `src/app/api/admin/integrations/facebook/route.ts`
- `prisma/schema.prisma`

## Environment Hooks

### Declared in `.env.example`
- `ADMIN_API_KEY`
- `DATABASE_URL`
- `NODE_ENV`

### Read by code but not documented in `.env.example`
- `NEXT_PUBLIC_APP_URL` in `src/app/api/admin/logout/route.ts`
- `GEOIP_ENABLED` in `src/lib/tds/services/ip-info-service.ts`
- `MAXMIND_DB_PATH` in `src/lib/tds/services/geo-db-service.ts`
- `LP_TOKEN_SECRET` in `src/lib/tds/services/lp-token-service.ts`
- `MACRO_FILE_PATH` in `src/lib/tds/macros/predefined/advanced.ts`

## Unwired / Unclear

- `next-auth`, `next-intl`, `zustand`, `@tanstack/react-query`, `@tanstack/react-table`, `framer-motion`, and `z-ai-web-dev-sdk` are installed in `package.json`, but I did not find current imports under `src/`.
- If those packages are intended for future use, they are not part of the active integration path yet.

## Route Surface Summary

- Traffic-facing routes: `src/app/api/click/route.ts`, `src/app/api/click/json/route.ts`, `src/app/api/postback/route.ts`, `src/app/api/lp/offer/route.ts`, `src/app/api/safe/route.ts`
- Admin routes: the `src/app/api/admin/` tree covers CRUD, stats, reports, auth, template config, and integration settings
- Root API placeholder: `src/app/api/route.ts`

## Summary

The live codebase currently integrates with **SQLite/Prisma**, **custom admin auth**, **proxy/edge request headers**, **geo/IP enrichment helpers**, and **affiliate-style HTTP postbacks**. AppsFlyer and Facebook are represented as DB-backed admin configuration endpoints only, and several installed packages appear dormant in the current runtime path.
