# CONCERNS.md — Codebase Technical Debt & Risk Assessment

> Generated: 2026-03-31
> Scope: `/home/z/my-project` (zai-yt-keitaro TDS)

---

## 1. Tech Debt

### 1.1 Monolithic page.tsx (1,201 lines)
- **File:** `src/app/page.tsx` — single-file UI with 8 skill panels, all state, all handlers
- **Risk:** Merge conflicts, impossible to code-review in chunks, no component reuse
- **Action:** Extract each skill panel (chat, image, tts, asr, vision, video, search, reader) into separate components under `src/components/skills/`

### 1.2 Large backend files (500+ lines)
| File | Lines | Concern |
|------|-------|---------|
| `src/lib/tds/macros.ts` | 775 | Macro processor + builder + validator — no tests |
| `src/lib/tds/bot-detection.ts` | 679 | 400 lines of hardcoded IP ranges in `DATACENTER_IP_RANGES` |
| `src/lib/tds/filters/index.ts` | 659 | 14 filter classes + registry — all in one file |
| `src/lib/tds/pipeline/payload.ts` | 585 | Payload building logic |
| `src/lib/tds/pipeline/stages/build-raw-click.ts` | 563 | Raw click construction |
| `src/lib/tds/pipeline/stages/execute-action.ts` | 516 | Action execution logic |

### 1.3 Dead weight directories
| Directory | Size | Purpose |
|-----------|------|---------|
| `reference/` | **57 MB** | Original Keitaro PHP source code — not imported by any TS/JS file |
| `.gsd-source/` | **6.1 MB** | Unknown source — not referenced in codebase |
| `skills/ez-agents/node_modules/` | Large | Skill dependencies shipped in workspace |

- **Action:** Move `reference/` and `.gsd-source/` to an external archive or add to `.gitignore`

### 1.4 Duplicate macro processors
- `src/lib/tds/macros.ts` (775 lines) — standalone macro processor
- `src/lib/tds/macros/processor.ts` — another macro processor in a directory module
- `src/lib/tds/macros/predefined/advanced.ts` — yet more macro logic
- **Risk:** Divergent behavior, maintenance burden, unclear which is authoritative

---

## 2. Known Issues

### 2.1 TDS not operational — empty database
- **0 Streams, 0 Offers, 0 Landings** in the SQLite database
- The TDS pipeline (`/api/click`) will process requests but has **nothing to route to**
- Campaigns exist but have no associated streams, so all clicks hit the default "no stream found" path
- **Action:** Seed database with sample streams, offers, and landings

### 2.2 TODO in rotator
- `src/lib/tds/rotator.ts:178` — `// TODO: Implement actual binding storage`
- Visitor binding (stream/landing/offer stickiness) is **not persisted**

### 2.3 Bot detection IP ranges are static and overlapping
- `src/lib/tds/bot-detection.ts` lines 45-237: 200+ hardcoded `DATACENTER_IP_RANGES` entries
- **Overlapping prefixes:** e.g., `3.` appears under both AWS and GCP; `13.` under AWS, GCP, Azure; `23.` under AWS, Azure, Linode; `35.` under AWS, GCP; `52.` under AWS, Azure; `104.` under AWS, Azure, DigitalOcean, Cloudflare; `45.` under DigitalOcean, Linode, VPN/Proxy
- **First-match wins** — the check order determines which provider is "blamed," not the actual provider
- **Risk:** False positives on legitimate cloud users; outdated ranges never updated
- **Action:** Replace with a proper IP-to-ASN database (e.g., MaxMind mmdb, already partially integrated in `geo-db-service.ts`)

### 2.4 `checkAuth()` skips authentication in development
- `src/lib/auth/admin-auth.ts:192` — `const skipAuth = process.env.NODE_ENV !== 'production' && isLocalDevelopment(request)`
- All 17 admin API routes are **unauthenticated** in dev mode
- The default API key is `'tds-admin-secret-key-change-in-production'` (line 11)

---

## 3. Security Considerations

### 3.1 Auth implementation
| Issue | Severity | Location |
|-------|----------|----------|
| Single shared API key (no per-user tokens) | **High** | `admin-auth.ts:11` |
| API key passed in query parameter (`?api_key=`) — appears in access logs, browser history | **High** | `admin-auth.ts:65` |
| Session cookie stores the raw API key as its value | **Medium** | `admin-auth.ts:145` |
| No rate limiting on login or API endpoints | **Medium** | All routes |
| No CSRF protection on cookie-based auth | **Medium** | `admin-auth.ts:74` |
| Dev mode bypasses auth entirely | **Medium** | `admin-auth.ts:192` |
| Default API key is well-known | **Medium** | `admin-auth.ts:11` |

### 3.2 Input validation gaps
- Regex patterns from `BotRule.pattern` are used in `new RegExp()` calls (`checkRuleMatch`) — **ReDoS risk** if user-controlled
- `KeywordFilter` and `ReferrerFilter` in `filters/index.ts` pass user-defined regex patterns to `new RegExp(k, 'i')` — same ReDoS risk
- No request body validation/sanitization on admin CRUD routes (campaigns, streams, offers, etc.) — relies solely on TypeScript types

### 3.3 Traffic API routes lack auth
- `/api/click`, `/api/click/json`, `/api/postback`, `/api/lp/offer` — **no authentication required** (by design for traffic routing, but postback endpoint is writable)
- Postback route (`/api/postback`) creates/updates conversions — **no validation that the postback comes from a legitimate affiliate network**

### 3.4 Password handling
- `src/app/api/admin/users/route.ts` — uses `bcrypt` for password hashing (good)
- But passwords stored as plain `String` in Prisma schema with no length validation

---

## 4. Performance Bottlenecks

### 4.1 Potential N+1 queries in pipeline
- `src/lib/tds/pipeline/stages/choose-stream.ts:140` — `db.stream.findMany()` then iterates streams and checks filters
- `src/lib/tds/pipeline/stages/choose-offer.ts:142-205` — Multiple sequential DB calls:
  1. `db.streamOfferAssociation.findMany()`
  2. `db.offer.findUnique()` per association
  3. `db.offer.findMany()` as fallback
- **Risk:** If a stream has N offer associations, this makes N+2 DB round-trips

### 4.2 SQLite for production TDS
- SQLite is single-writer — concurrent click processing will serialize writes
- No connection pooling needed (file-based) but `PRAGMA journal_mode=WAL` may not be configured
- **Risk:** Under high traffic (100+ clicks/sec), SQLite will bottleneck on writes to `Click` and `DailyStat` tables

### 4.3 57MB `reference/` directory
- Slows down IDE indexing, `git status`, and deployment
- Not used by the application at all

### 4.4 Click table bloat
- `Click` model has **45+ columns** including `sub1` through `sub15` and `extraParam1-3`
- Every click writes a wide row — impacts insert performance and storage

### 4.5 Module-level caches with no invalidation strategy
- `botRulesCache` in `bot-detection.ts` — 5-minute TTL, global mutable state
- `fileCache` in `macros.ts` — 1-minute TTL, never cleaned up (memory leak potential)
- In serverless/server environments, module-level state can be stale across invocations

---

## 5. Fragile Areas

### 5.1 The page.tsx monolith (1,201 lines)
- All 8 AI skill panels in a single component
- Each panel has its own state (15+ `useState` hooks at the top level)
- Video polling (`setInterval` in `generateVideo`) uses `useRef` but no cleanup on unmount for error paths
- Any change to one skill panel risks breaking others

### 5.2 Sync/async macro duality
- `replaceMacrosSync()` and `replaceMacros()` in `macros.ts` — two near-identical implementations
- `{from_file:...}` macro only works in async mode; sync mode returns `[file:filename]` placeholder
- If sync version is accidentally used where async is needed, macros silently fail

### 5.3 `console.error` used as primary error handling
- **55+ `console.error()` calls** across the codebase — no structured logging, no error aggregation
- Errors in pipeline stages are caught and logged but often return generic error responses
- No way to correlate errors across the pipeline without manual log digging

### 5.4 Hardcoded safe page paths
- `src/lib/tds/bot-detection.ts:650-657` — safe page URLs like `/safe/debug`, `/safe/bot` are hardcoded strings
- These routes may not exist (no corresponding pages found in the codebase)

### 5.5 Type safety erosion
- 11 uses of `: any` type across critical pipeline code:
  - `createMacroDataFromRawClick(rawClick: any, campaign?: any, stream?: any, request?: any)`
  - `geo-db-service.ts` — `maxMindReader: any`, `ispReader: any`, `asnReader: any`
  - `choose-offer.ts` — `mapOffer(offer: any)`, `generate-token.ts` — 4 `any` params

---

## 6. Scaling Limits

| Limit | Current | Impact |
|-------|---------|--------|
| Database | SQLite (file-based) | Single writer, no replication, no horizontal scaling |
| Auth | Single API key | Cannot have multiple admin users with different permissions |
| Session | Stateless cookie | Works for scaling but no session revocation mechanism |
| Bot rules | Global cache, 5min TTL | All workers share same stale data |
| File macros | `fs.readFile` with 1-min cache | No CDN, no distributed cache |
| Logging | `console.error` | No log aggregation, rotation, or alerting |

---

## 7. Dependencies at Risk

| Dependency | Risk |
|------------|------|
| `@maxmind/mmdb` or GeoIP module | Imported conditionally — no fallback if module missing |
| `z-ai-web-dev-sdk` | Backend AI SDK — no version pinning visible, external dependency |
| `bcrypt` | Native module — may fail in certain environments (Alpine, serverless) |
| No dependency audit detected | No `npm audit` or lockfile integrity check configured |

---

## 8. Missing Critical Features

### 8.1 Zero test coverage
- **No test framework** in `package.json` (no jest, vitest, mocha)
- **No test files** in `src/` (0 `.test.ts`, 0 `.spec.ts`, 0 `__tests__/` directories)
- **No CI/CD pipeline** (no `.github/workflows/`, no `Dockerfile`, no deployment config)
- The 55+ `console.error` calls suggest errors are "handled" but never verified

### 8.2 No environment validation
- No startup check that required env vars (`DATABASE_URL`, `ADMIN_API_KEY`) are set
- No validation that SQLite database file exists or is writable

### 8.3 No monitoring or health checks
- No `/api/health` endpoint
- No metrics collection (request latency, error rates, queue depth)
- No alerting on failed clicks, postbacks, or bot detection

### 8.4 No database migrations strategy
- Uses `prisma db push` (schema sync) — destructive in production
- No migration files, no rollback capability

### 8.5 Missing admin UI
- `page.tsx` is an AI Skills Hub demo page, **not a TDS admin dashboard**
- 17 admin API routes exist but there is no admin UI to use them
- The CLAUDE.md references a "TDS Dashboard UI" but `page.tsx` is a skills showcase

---

## 9. Test Coverage Gaps

| Area | Tests | Priority |
|------|-------|----------|
| Click pipeline (bot detection → campaign → stream → offer → redirect) | None | **Critical** |
| Macro replacement (sync + async, edge cases) | None | **Critical** |
| Filter system (14 filter types, accept/reject modes, OR logic) | None | **Critical** |
| Authentication (API key, cookie, dev bypass) | None | **High** |
| Postback processing (conversion creation, payout tracking) | None | **High** |
| Admin CRUD routes (17 endpoints) | None | **High** |
| IP matching (CIDR, wildcard, exact) | None | **Medium** |
| Safe page routing | None | **Medium** |
| Page.tsx UI components | None | **Low** (manual testing acceptable) |

---

## 10. Summary: Top 10 Actions

1. **Add tests** — Set up vitest, write tests for click pipeline and macro processor
2. **Split page.tsx** — Extract 8 skill panels into separate components
3. **Replace hardcoded IP ranges** — Use MaxMind ASN database (already partially integrated)
4. **Fix auth security** — Remove query param auth, add rate limiting, rotate default API key
5. **Seed the database** — Add sample streams, offers, landings so TDS can actually route traffic
6. **Add input validation** — Zod schemas for all admin CRUD routes and postback endpoint
7. **Consolidate macro processors** — Remove duplicate `macros.ts` vs `macros/processor.ts`
8. **Add ReDoS protection** — Timeout or safe-regex on user-provided regex patterns
9. **Remove dead weight** — Archive `reference/` (57MB) and `.gsd-source/` (6.1MB)
10. **Set up CI/CD** — GitHub Actions for lint + test + type-check on every push
