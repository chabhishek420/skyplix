# Architecture — zai-yt-keitaro (TDS Platform)

> Auto-generated codebase map. Last updated: 2026-03-31.

## 1. Pattern Overview

| Concern | Pattern / Technology |
|---|---|
| Framework | Next.js 16 App Router (file-system routing) |
| Language | TypeScript 5 (strict) |
| Styling | Tailwind CSS 4 + shadcn/ui (New York theme) |
| Database | Prisma ORM → SQLite (`db/custom.db`) |
| Icons | Lucide React |
| Client State | React hooks (useState/useRef) |
| Auth | API-key-based (Bearer / X-API-Key / Cookie / Query) |
| AI SDK | z-ai-web-dev-sdk (backend only, never on client) |
| Package Manager | Bun |

The codebase follows a **4-layer architecture**:

```
UI Layer → API Layer → TDS Engine → Data Layer (Prisma + SQLite)
```

All backend logic lives in `/api/` routes — there are **no server actions**.

---

## 2. Layers

### 2.1 UI Layer — `src/app/page.tsx`

A single-page client component (`'use client'`) that renders an **AI Skills Hub** with 8 interactive demos:
Chat, Image Generation, TTS, ASR, Vision, Video, Web Search, Page Reader.

- **~1,200 lines** in a single `page.tsx` file
- Uses only shadcn/ui components (Card, Button, Input, Select, etc.)
- Communicates with backend exclusively via `fetch('/api/...')` calls
- The app layout (`layout.tsx`) is minimal — Geist fonts + Toaster

> **Note:** The UI layer currently does NOT include a TDS admin dashboard. The TDS is a headless API.

### 2.2 API Layer — `src/app/api/`

32 route handlers across 4 route groups:

| Group | Routes | Purpose |
|---|---|---|
| `/api/click`, `/api/click/json` | 2 | Traffic click processing (TDS) |
| `/api/postback`, `/api/lp/offer` | 2 | Conversion tracking + LP→Offer flow |
| `/api/admin/*` | 18 | CRUD for all TDS entities |
| `/api/ai/*` | 9 | AI skills (chat, image, tts, asr, vision, video, search, read) |
| `/api` (root) | 1 | Health check (`Hello, world!`) |

**Traffic Entry Points (4 routes, no auth required):**
- `GET/POST /api/click` — Primary TDS click processor, extracts params → calls `processClick()`
- `GET /api/click/json` — JSON response variant
- `GET/POST /api/postback` — Conversion postback (returns 200 always to prevent enumeration)
- `GET/POST /api/lp/offer` — Landing page → Offer tracking via LP token

**Admin Entry Points (18 routes, auth required via `checkAuth()`):**
- `/api/admin/login`, `/api/admin/logout` — Session management
- `/api/admin/campaigns`, `/api/admin/streams`, `/api/admin/offers` — Core CRUD
- `/api/admin/landings`, `/api/admin/publishers`, `/api/admin/domains` — Entity CRUD
- `/api/admin/traffic-sources`, `/api/admin/bot-rules`, `/api/admin/affiliate-networks` — Config CRUD
- `/api/admin/clicks`, `/api/admin/conversions`, `/api/admin/stats` — Read-only analytics
- `/api/admin/reports`, `/api/admin/audit-logs` — Reporting
- `/api/admin/users`, `/api/admin/settings` — System management

**AI Entry Points (9 routes):**
- `/api/ai/chat`, `/api/ai/image`, `/api/ai/tts`, `/api/ai/asr`, `/api/ai/vision`
- `/api/ai/video`, `/api/ai/video/status`, `/api/ai/search`, `/api/ai/read`

### 2.3 TDS Engine — `src/lib/tds/`

The core Traffic Distribution System. **~16,565 lines** across ~100 TypeScript files.

Architecture mirrors the original Keitaro PHP TDS, ported to TypeScript with class-based design.

#### 2.3.1 Pipeline System (`pipeline/`)

The pipeline is the heart of the TDS. It processes clicks through sequential stages.

```
Pipeline
├── First Level (25 stages) — Full click processing
│   1.  DomainRedirectStage
│   2.  CheckPrefetchStage
│   3.  BuildRawClickStage
│   4.  CheckBotStage
│   5.  FindCampaignStage
│   6.  CheckDefaultCampaignStage
│   7.  UpdateRawClickStage
│   8.  CheckParamAliasesStage
│   9.  UpdateCampaignUniquenessStage
│   10. ChooseStreamStage
│   11. UpdateStreamUniquenessStage
│   12. ChooseLandingStage
│   13. ChooseOfferStage
│   14. GenerateTokenStage
│   15. FindAffiliateNetworkStage
│   16. UpdateHitLimitStage
│   17. UpdateCostsStage
│   18. UpdatePayoutStage
│   19. SaveUniquenessSessionStage
│   20. SetCookieStage
│   21. ExecuteActionStage
│   22. PrepareRawClickToStoreStage
│   23. CheckSendingToAnotherCampaignStage
│   24. StoreRawClicksStage
│
└── Second Level (13 stages) — LP→Offer flow
    1.  FindCampaignStage
    2.  UpdateParamsFromLandingStage
    3.  CheckDefaultCampaignStage
    4.  CheckParamAliasesStage
    5.  ChooseStreamStage
    6.  ChooseOfferStage
    7.  FindAffiliateNetworkStage
    8.  UpdateCostsStage
    9.  UpdatePayoutStage
    10. SetCookieStage
    11. ExecuteActionStage
    12. CheckSendingToAnotherCampaignStage
    13. StoreRawClicksStage
```

**Pipeline class** (`pipeline/pipeline.ts`):
- Two stage arrays: `firstLevelStages` and `secondLevelStages`
- Runs stages sequentially with abort-on-error
- Supports **recursion** (campaign → campaign redirect) with `MAX_REPEATS = 10`
- `freezeStages()` prevents re-initialization during recursion
- Auto-detects pipeline level via `Payload.isSecondLevel()`

**Payload** (`pipeline/payload.ts`):
- Carries all state through pipeline stages (request, rawClick, campaign, stream, landing, offer, action, etc.)
- Fluent builder API: `setRawClick()`, `setCampaign()`, `setRedirect()`, `abort()`
- Recursion tracking: `_repeatCount` with `MAX_REPEATS = 10`
- `resetForCampaignRedirect()` clears entities but preserves rawClick + increments repeat count

**Types** (`pipeline/types.ts`):
- `RawClick` — 60+ fields covering everything: IDs, geo, device, traffic params, sub1-sub15, revenue, uniqueness, etc.
- `Campaign`, `Stream`, `Landing`, `Offer`, `StreamFilter` — entity interfaces
- `ActionType` — 17 action types: http_redirect, http301, meta, double_meta, iframe, frame, js, blank_referrer, local_file, show_html, show_text, status404, do_nothing, to_campaign, sub_id, remote, curl, form_submit
- `StageInterface` — `name` + `process(payload) → StageResult`
- `StageResult` — `{ success, payload, error?, abort? }`

#### 2.3.2 Actions System (`actions/`)

**Base class:** `AbstractAction` (`actions/base.ts`)
- Abstract `execute()` method returns `ActionResult`
- Built-in macro processing via `processMacros()` using the MacrosProcessor
- Helper methods: `setRedirect()`, `setBody()`, `setStatus()`, `addHeader()`, `getProcessedPayload()`

**Registry:** `ActionRepository` singleton (`actions/repository.ts`)
- Maps string action type → concrete action class
- `getNewActionInstance(type)` creates a new instance with the pipeline payload injected
- 19 registered action types

**Predefined Actions** (`actions/predefined/`):
| File | Action Classes |
|---|---|
| `http-redirect.ts` | HttpRedirectAction, Http301RedirectAction |
| `meta.ts` | MetaRedirectAction, DoubleMetaRedirectAction |
| `iframe.ts` | IframeRedirectAction, FrameRedirectAction |
| `js.ts` | JsRedirectAction |
| `content.ts` | ShowHtmlAction, ShowTextAction, Status404Action, DoNothingAction |
| `frame.ts` | FrameAction |
| `remote.ts` | RemoteAction |
| `curl.ts` | CurlAction |
| `form-submit.ts` | FormSubmitAction |
| `to-campaign.ts` | ToCampaignAction |
| `subid.ts` | SubIdAction |
| `blank-referrer.ts` | BlankReferrerAction |
| `show-text.ts` | ShowTextAction |
| `do-nothing.ts` | DoNothingAction |
| `status404.ts` | Status404Action |

#### 2.3.3 Filters System (`filters/`)

**Interface:** `FilterInterface { name, description, process(filter, rawClick) → FilterResult }`

**Registry:** `FilterRegistry` singleton with 29 registered filters:

| Filter | Purpose |
|---|---|
| CountryFilter | ISO country code matching |
| BrowserFilter | Browser name matching |
| OsFilter | Operating system matching |
| DeviceTypeFilter | desktop/mobile/tablet |
| IpFilter | IP with CIDR and wildcard support |
| LanguageFilter | Browser language |
| KeywordFilter | Search keyword (exact/contains/regex) |
| ReferrerFilter | Referrer URL matching |
| ScheduleFilter | Time-based day/hour schedule |
| IsBotFilter | Bot flag matching |
| ProxyFilter | Proxy usage detection |
| MobileFilter | Mobile status |
| CityFilter | City name matching |
| RegionFilter | Region/state matching |
| LimitFilter | Click limit checking |
| UniquenessFilter | Visitor uniqueness (campaign/stream/global) |
| ConnectionTypeFilter | ISP connection type |
| IspFilter | ISP name |
| OperatorFilter | Mobile carrier |
| HideClickDetectFilter | Click hiding detection |
| Ipv6Filter | IPv6 address matching |
| ParameterFilter | URL parameter matching |
| EmptyReferrerFilter | Empty referrer check |
| AnyParamFilter | Any parameter presence |
| UserAgentFilter | User-Agent matching |
| DeviceModelFilter | Device model matching |
| OsVersionFilter | OS version matching |
| BrowserVersionFilter | Browser version matching |
| IntervalFilter | Time interval between clicks |

**Filter evaluation:** `checkFilters()` supports AND (default) and OR modes (`filterOr` flag on Stream).

#### 2.3.4 Macros System (`macros/`)

Template variable replacement engine. Supports both `{macro_name}` and `$macro_name` syntax with optional arguments `{macro:arg1,arg2}`.

**Processor** (`macros/processor.ts`):
- `MacrosProcessor.process(content, context)` — parses content, looks up macros in registry, replaces values
- Supports raw mode (`$_macro`) to skip URL encoding
- Falls back to request params if no registered macro matches

**Registry** (`macros/registry.ts`):
- Singleton with 60+ registered macros across categories:

| Category | Macros |
|---|---|
| Click/Sub ID | subid, clickid, sub_id, click_id |
| Campaign | campaignid, campaignname |
| Stream | streamid |
| Geo | country, city, region/state/province |
| Device | browser, browser_version, os, os_version, device_type, device_model, device_brand |
| Request | ip, user_agent/ua, referrer/referer, keyword/kw, source, language/lang |
| Revenue | cost |
| DateTime | date, time, timestamp/ts/unix |
| Random | random/rand |
| Offer/Landing | offer, offerid, landing, landingid |
| Conversion | visitor_code, profit, revenue, sale_revenue, lead_revenue, currency, status, payout, goal1-4, is_lead, is_sale, is_rejected |
| Advanced | sample, from_file, base64_encode/decode, urlencode/decode, md5, sha256, lower/upper, substring, replace |
| Tracking | session_id, token, lp_token, parent_click_id, uuid, gen_id, creative_id, ad_campaign_id |

#### 2.3.5 Contexts (`contexts/`)

- `LandingContext` — `serveLandingPage()`, `handleLpToOfferClick()` for LP→Offer flow
- `GatewayContext` — `handleGatewayRedirect()`, `generateGatewayUrl()` for gateway routing

#### 2.3.6 Services (`services/`)

| Service | Purpose |
|---|---|
| IpInfoService | IP geolocation and info |
| ProxyService | Proxy detection |
| CookiesService | Cookie management |
| EntityBindingService | Visitor-to-stream/landing/offer binding |
| LpTokenService | LP token creation/validation for offer tracking |
| GeoDbService | GeoIP database integration |

#### 2.3.7 Data Module (`data/`)

Static reference data (no PHP dependencies):
| Module | Content |
|---|---|
| `countries.ts` | 250+ country codes, names, validation |
| `languages.ts` | Language codes and names |
| `browsers.ts` | Browser name database |
| `operating-systems.ts` | OS database with families |
| `connection-types.ts` | ISP connection types (MaxMind) |
| `search-engines.ts` | Search engine referrer parsing, keyword extraction |
| `operators.ts` | Mobile carrier database (by country) |
| `bot-signatures.ts` | Bot user-agent patterns |

#### 2.3.8 Other TDS Modules

| File | Purpose |
|---|---|
| `click-processor.ts` | `processClick()` — direct click processing (simpler, no pipeline) |
| `click-id.ts` | Click ID generation (8-char timestamp + 16-char random hex) |
| `bot-detection.ts` | `detectBot()`, `shouldCloak()` — bot detection with confidence scoring |
| `rotator.ts` | `StreamRotator` (position/weight selection), `LandingOfferRotator` (weighted association selection) |
| `utils/raw-click-serializer.ts` | RawClick serialization/deserialization |

### 2.4 Data Layer

**Prisma ORM** → **SQLite** (`db/custom.db`)

**Schema** (`prisma/schema.prisma`) — **22 models**:

| Model | Purpose |
|---|---|
| User, Session | Admin user management |
| Campaign | Traffic campaigns (status, type, caps, bindings, safe page) |
| Stream | Traffic streams within campaigns (forced/regular/default) |
| StreamFilter | Filter rules on streams (geo, device, etc.) |
| Landing | Landing page entities |
| Offer | Offer entities with payout, cap, country targeting |
| StreamLandingAssociation | Landing↔Stream many-to-many with share % |
| StreamOfferAssociation | Offer↔Stream many-to-many with share % |
| CampaignPublisher | Campaign↔Publisher access control |
| Publisher | Traffic publishers with stats |
| AffiliateNetwork | Affiliate network credentials |
| Domain | Domain management (campaign/landing/offer domains) |
| TrafficSource, CampaignTrafficSource | Traffic source configuration |
| BotRule, SafePage | Bot detection rules and safe page content |
| Click | Individual click records (100+ columns) |
| Conversion | Conversion/postback records |
| DailyStat | Aggregated daily statistics |
| Setting | Key-value system settings |
| AuditLog | Admin action audit trail |

**Database access:** `src/lib/db.ts` exports singleton `db` (PrismaClient) with global caching in dev.

---

## 3. Data Flow

### 3.1 Click Processing (Primary Path)

```
Browser/Client
    │
    ▼
GET /api/click?campaign_id=X&pub_id=Y&source=Z&sub1=...
    │
    ▼
click/route.ts ── extracts params, headers, IP
    │
    ▼
processClick() from click-processor.ts
    │
    ├── 1. Validate campaign_id, pub_id
    ├── 2. Lookup campaign (active, with streams)
    ├── 3. Lookup publisher (active)
    ├── 4. Check publisher access
    ├── 5. Bot detection (detectBot)
    ├── 6. Generate click ID (collision-safe)
    ├── 7. If bot (confidence ≥ 70): → safe page redirect
    ├── 8. Select stream (weighted random)
    ├── 9. Build destination URL (macro substitution)
    ├── 10. Record click in DB
    ├── 11. Update publisher stats
    └── 12. Return: { success, destinationUrl, setCookie }
    │
    ▼
302 redirect to destination URL
    (with Set-Cookie, X-RT: 1, Referrer-Policy: no-referrer)
```

### 3.2 Pipeline Processing (Advanced Path)

```
Browser/Client
    │
    ▼
GET /api/click?campaign_id=X&pub_id=Y
    │
    ▼
Payload.fromRequest(request)
    │
    ▼
Pipeline.start(payload)
    │
    ├── Level 1: 25 stages sequentially
    │   DomainRedirect → CheckPrefetch → BuildRawClick → CheckBot
    │   → FindCampaign → CheckDefault → UpdateRawClick → CheckParamAliases
    │   → UpdateCampaignUniqueness → ChooseStream (rotator+filters)
    │   → UpdateStreamUniqueness → ChooseLanding → ChooseOffer
    │   → GenerateToken → FindAffiliateNetwork → UpdateHitLimit
    │   → UpdateCosts → UpdatePayout → SaveUniquenessSession
    │   → SetCookie → ExecuteAction → PrepareRawClick → CheckSending
    │   → StoreRawClicks
    │
    └── (If to_campaign action) → Recurse back to Level 1 (max 10x)
    │
    ▼
NextResponse.redirect() / HTML body / 404
```

### 3.3 Conversion Postback Flow

```
Affiliate Network
    │
    ▼
GET/POST /api/postback?clickid=XXX&status=approved&payout=5.0
    │
    ▼
Validate click ID → Find click → Create/Update Conversion
    → Update publisher stats (if approved)
    → Always returns 200 (prevents enumeration)
```

### 3.4 LP → Offer Flow

```
Landing Page (visitor clicks offer link)
    │
    ▼
GET /api/lp/offer?lp_token=TOKEN
    │
    ▼
Parse token → Find click → Mark landingClicked
    → Resolve offer URL → 302 redirect to offer
```

---

## 4. Key Abstractions

| Abstraction | File(s) | Pattern |
|---|---|---|
| Pipeline | `pipeline/pipeline.ts` | Sequential stage execution with recursion guard |
| Stage | `pipeline/stages/*.ts` | `StageInterface { name, process(payload) }` |
| Payload | `pipeline/payload.ts` | Mutable state carrier with fluent API |
| Action | `actions/base.ts` | `AbstractAction { execute() }` with macro support |
| Filter | `filters/index.ts` | `FilterInterface { process(filter, rawClick) }` |
| Macro | `macros/processor.ts` | Registry-based `{token}` / `$token` replacement |
| Service | `services/*.ts` | Singleton services (IP, proxy, cookies, tokens) |
| Rotator | `rotator.ts` | Weighted random + position-based selection |
| Auth | `auth/admin-auth.ts` | API-key auth (4 methods: Bearer, X-API-Key, Cookie, Query) |

---

## 5. Error Handling Patterns

1. **TDS Pipeline:** Each stage returns `StageResult`. On error: `abort = true`, pipeline stops. Recursion overflow returns explicit error.
2. **API Routes:** `try/catch` around all handlers. Returns 500 JSON on unhandled errors.
3. **Click Processing:** Returns `{ success: false, error: 'ERROR_CODE' }` with typed error codes: `INVALID_CAMPAIGN_ID`, `ADV_INACTIVE`, `PUBLISHER_NOT_ACTIVE`, `INSUFFICIENT_PERMISSION`, `INTERNAL_ERROR`.
4. **Postback:** Always returns 200 to prevent enumeration. Errors are logged server-side.
5. **Auth:** Returns 401 JSON with `error`, `message`, and `hint` fields.
6. **Admin CRUD:** Validates required fields → 400. Catches Prisma errors → 500.

---

## 6. Cross-Cutting Concerns

### Authentication
- **Admin routes** (`/api/admin/*`): Protected by `checkAuth(request)` which calls `verifyAdminAuth()`.
- Auth methods (in priority): Bearer token → X-API-Key header → Cookie session → Query param (dev only).
- Dev mode skips auth on localhost.
- **Traffic routes** (`/api/click`, `/api/postback`): No auth required.
- **AI routes** (`/api/ai/*`): No auth required (uses backend SDK).

### Logging
- `console.error()` for all unhandled errors.
- Pipeline logs stored in `payload.logs[]` array (in-memory, not persisted).
- `dev.log` checked for server errors.

### Validation
- Click params: `parseInt()` + `isNaN()` checks.
- Prisma: Schema-level validation (unique constraints, required fields).
- API routes: Manual validation of required query params before processing.

### IP Extraction
- Shared utility pattern: CF-Connecting-IP → X-Forwarded-For → X-Real-IP → 'unknown'.
- Duplicated in `click/route.ts`, `postback/route.ts`, and `Payload.getIp()`.

### Middleware
- No Next.js middleware file detected. Auth is applied per-route via `checkAuth()`.
- No CSRF protection beyond same-origin cookies.
