# External Integrations

**Analysis Date:** 2026-03-31

## APIs & External Services

### z-ai-web-dev-sdk v0.0.17 (AI Features — Backend Only)
Used in 9 API routes, never imported on the client side. Instantiated via `ZAI.create()`.

| Route | Feature | Method |
|-------|---------|--------|
| /api/ai/chat | Chat completions | POST |
| /api/ai/image | Image generation | POST |
| /api/ai/vision | Vision/image analysis | POST |
| /api/ai/tts | Text-to-speech | POST |
| /api/ai/asr | Automatic speech recognition | POST |
| /api/ai/search | AI-powered search | POST |
| /api/ai/video | Video generation | POST |
| /api/ai/video/status | Video status polling | POST |
| /api/ai/read | Content reading | POST |

### Affiliate Networks
- HasOffers / Tune integration via AffiliateNetwork model
- Postback URL tracking with configurable parameters (clickid, payout, status)
- Supports any affiliate network with custom baseUrl, apiKey, apiSecret

## Data Storage

### SQLite via Prisma ORM
- **Connection:** `file:./db/custom.db` (configured via DATABASE_URL env var)
- **Prisma Client:** Singleton pattern in `src/lib/db.ts` (globalThis caching for dev hot-reload)
- **Query logging:** Enabled (`log: ['query']`)
- **Schema:** 22 models covering campaign management, click tracking, bot detection, and analytics
- **Seed script:** `prisma/seed.ts` with sample campaigns, publishers, bot rules, safe pages, and 7 days of simulated click data

### Database Models (22)
User, Session, Campaign, Stream, StreamFilter, Landing, Offer,
StreamLandingAssociation, StreamOfferAssociation, CampaignPublisher, Publisher,
AffiliateNetwork, Domain, TrafficSource, CampaignTrafficSource, BotRule, SafePage,
Click, Conversion, DailyStat, Setting, AuditLog

## Authentication

### Custom Admin Auth (NOT NextAuth.js)
- **Implementation:** `src/lib/auth/admin-auth.ts`
- **Note:** next-auth v4 is installed but **not actively used**. The actual auth system is custom API-key-based.
- **Auth methods (in priority order):**
  1. Bearer token in Authorization header
  2. X-API-Key header
  3. Cookie-based session (`admin_session` cookie, HttpOnly, 24h expiry)
  4. Query parameter `api_key` (testing only, not recommended for production)
- **Config:** `ADMIN_API_KEY` env var (defaults to `tds-admin-secret-key-change-in-production` in dev)
- **Dev bypass:** Auth is skipped on localhost in non-production environments
- **Helpers exported:** `verifyAdminAuth()`, `withAdminAuth()`, `createAuthenticatedRoute()`, `createAdminSession()`, `clearAdminSession()`, `checkAuth()`

### Password Hashing
- bcrypt ^6.0.0 — Used in `/api/admin/users` route for user password management

## CI/CD
- None detected — Dev server only (`bun run dev`)
- Build script exists (`next build` with standalone output) but not used in current environment
- No Docker, GitHub Actions, or deployment configuration found

## Environment Configuration

### Required Variables (from .env.example)
| Variable | Purpose | Default |
|----------|---------|---------|
| DATABASE_URL | SQLite connection string | file:./db/custom.db |
| ADMIN_API_KEY | Admin API authentication key | tds-admin-secret-key-change-in-production |
| NEXTAUTH_URL | NextAuth base URL (unused) | — |
| NEXTAUTH_SECRET | NextAuth secret (unused) | — |
| ANTHROPIC_API_KEY | Claude API key for Ralph Zero CLI (optional) | — |

### Notes
- `.env` file is present in the project
- Only DATABASE_URL and ADMIN_API_KEY are actively used
- NEXTAUTH_* variables are placeholders (NextAuth is installed but unused)

## Webhooks & Callbacks

### /api/postback (Conversion Postback)
- **Methods:** GET, POST
- **Purpose:** Receives conversion postbacks from affiliate networks
- **Flow:** Matches clickId → updates Conversion record → updates Click revenue fields
- **Supported networks:** Any network configured in AffiliateNetwork model with custom postback parameters

### /api/click (Traffic Click Processing)
- **Methods:** GET, POST
- **Purpose:** Main traffic distribution endpoint
- **Flow:** Receives click → finds campaign → applies bot detection → runs filter pipeline → rotator → redirect/cloak

### /api/lp/offer (Landing Page Offer Tracking)
- **Method:** GET
- **Purpose:** Tracks landing page → offer transitions via LP token system

## Traffic Distribution Pipeline
The core TDS engine (`src/lib/tds/`) provides:
- **Click processor:** Full pipeline with 20+ stages (bot detection, geo lookup, stream selection, landing/offer rotation, macro substitution)
- **Bot detection:** Regex-based rules for IP, user agent, referrer, parameter, header matching
- **Cloaking:** Safe page system with multi-language support (redirect or content modes)
- **Macro system:** 50+ predefined macros for dynamic URL parameter substitution
- **Rotator:** Weight-based and position-based stream selection with landing/offer rotation
- **Geo database service:** Country, city, ISP, connection type lookup
- **Entity binding:** Visitor-level stream/landing/offer binding
