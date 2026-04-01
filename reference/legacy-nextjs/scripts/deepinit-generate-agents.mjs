#!/usr/bin/env node

import fs from 'fs';
import path from 'path';

const rootDir = process.cwd();
const today = new Date().toISOString().slice(0, 10);

const specs = {
  '.': {
    rootAppendOnly: true,
    purpose:
      'Primary application workspace for the Next.js-based TDS implementation, its supporting documentation, and the archived reference projects used during parity work.',
    subdirectories: [
      ['src/', 'Live application source: routes, UI, shared libraries, and type definitions.', true],
      ['prisma/', 'Prisma schema and seeding entrypoints for the SQLite-backed data model.', true],
      ['db/', 'Local SQLite database assets and generated runtime data.', true],
      ['docs/', 'Project notes, verification reports, and operational writeups.', true],
      ['public/', 'Static assets served directly by Next.js.', true],
      ['scripts/', 'Repository maintenance and validation scripts, including the deep-init generator.', true],
      ['tasks/', 'Human-maintained todo and lessons-learned notes.', true],
      ['adapters/', 'Prompting guidance for external model/provider adapters.', true],
      ['examples/', 'Small standalone examples used to validate patterns outside the main app.', true],
      ['planning/', 'Architecture snapshots and codebase planning documents.', true],
      ['reference/', 'Archived third-party/source-material projects for feature parity research.', true],
      ['mini-services/', 'Reserved space for sidecar services; currently an empty container.', true],
    ],
    notes: [
      'Treat the manual project rules above as authoritative; this generated section is only for navigation.',
      'Prefer editing `src/`, `prisma/`, and `docs/` unless the task explicitly calls for reference material or tooling changes.',
      'The `reference/` tree is intentionally shallow-documented here to avoid generating AGENTS files for vendored code.',
    ],
  },
  src: {
    title: 'src',
    purpose:
      'Application source tree for the Next.js App Router app: pages, route handlers, reusable UI primitives, domain libraries, hooks, and shared TypeScript definitions.',
    subdirectories: [
      ['app/', 'App Router pages, layouts, and HTTP route handlers.', true],
      ['components/', 'Reusable UI and admin-facing React components.', true],
      ['hooks/', 'Client-side React hooks shared by the UI.', true],
      ['lib/', 'Server and shared utilities, auth helpers, Prisma access, and TDS logic.', true],
      ['types/', 'TypeScript shapes for admin config and navigation models.', true],
    ],
    workingIn: [
      'Respect the App Router split: UI lives under `app/` and backend logic stays in API routes or shared `lib/` modules.',
      'Use the established path aliases (`@/lib`, `@/components`) instead of deep relative imports.',
      'When a change crosses routing, UI, and data logic, update the relevant child AGENTS files as the entry point shifts.',
    ],
    testing: [
      'Run `bun run lint` after multi-file changes in `src/`.',
      'For traffic-handling changes, exercise the impacted `/api/*` route manually or through existing verification notes.',
    ],
    patterns: [
      'App Router file-system routing with route groups such as `(admin)` and `(auth)`.',
      'Shared domain code is concentrated in `src/lib/tds` and consumed by thin route handlers.',
    ],
    internalDeps: ['`prisma/schema.prisma` for persisted shape changes.', '`docs/` and `planning/` for architectural context.'],
    externalDeps: ['Next.js App Router', 'React 19', 'TypeScript strict mode'],
  },
  'src/app': {
    title: 'app',
    purpose:
      'Top-level App Router directory. It hosts the global layout and styles, the public entry page, the admin route group, the auth route group, and the API surface.',
    keyFiles: [
      ['layout.tsx', 'Root layout wrapper for the entire application.'],
      ['globals.css', 'Global Tailwind/theme styling shared by every route.'],
      ['page.tsx', 'Primary public page entry; currently an empty placeholder file.'],
    ],
    subdirectories: [
      ['(admin)/', 'Admin route group for the in-app management UI.', true],
      ['(auth)/', 'Authentication route group for login flows.', true],
      ['api/', 'Next.js route handlers for click tracking and admin CRUD.', true],
    ],
    workingIn: [
      'Keep user-facing UI on the visible `/` route unless the work specifically targets the hidden admin routes.',
      'Do not move backend logic into page files; route handlers should delegate to `src/lib/`.',
    ],
    testing: [
      'Smoke-test route rendering in `bun run dev` for layout or CSS changes.',
      'Call the affected `app/api` endpoint after any route-handler edit.',
    ],
    patterns: [
      'Route groups are used to separate admin/auth concerns without changing URLs.',
      'Layouts and pages stay thin; shared behavior should move into `src/components` or `src/lib`.',
    ],
    internalDeps: ['`src/components/` for page composition.', '`src/lib/` for route behavior and shared utilities.'],
    externalDeps: ['Next.js App Router', 'Tailwind CSS 4'],
  },
  'src/app/(admin)': {
    title: '(admin)',
    purpose:
      'Admin-only route group that isolates the management dashboard from the public root surface while preserving the canonical `/admin/*` URLs.',
    subdirectories: [['admin/', 'Concrete admin dashboard routes and page shells.', true]],
    workingIn: [
      'Treat this group as administrative UI only; shared admin chrome belongs in `src/components/admin` and `src/lib/admin`.',
      'Expect many pages here to act as placeholders while backend functionality is still being filled in.',
    ],
    testing: ['Open the affected `/admin/*` page in the dev server and verify the page shell renders.'],
    patterns: ['Route-group folder exists for organization, not URL naming.'],
    internalDeps: ['`src/components/admin/` for layout and navigation.', '`src/lib/auth/` for gatekeeping patterns.'],
    externalDeps: ['Next.js route groups'],
  },
  'src/app/(admin)/admin': {
    title: 'admin',
    purpose:
      'Admin dashboard route tree. Each child folder maps to a feature page such as campaigns, streams, offers, reports, and system diagnostics.',
    keyFiles: [
      ['layout.tsx', 'Admin layout entry; currently an empty scaffold placeholder.'],
      ['page.tsx', 'Admin landing page; currently an empty scaffold placeholder.'],
    ],
    subdirectories: [
      ['affiliate-networks/', 'Affiliate network management screen.', false],
      ['bot-detection/', 'Bot-detection administration screen.', false],
      ['campaigns/', 'Campaign list and editor screen.', false],
      ['clicks/', 'Tracked-click log screen.', false],
      ['conversions/', 'Conversion reporting screen.', false],
      ['diagnostics/', 'Operational diagnostics screen.', false],
      ['domains/', 'Domain management screen.', false],
      ['landings/', 'Landing-page management screen.', false],
      ['offers/', 'Offer management screen.', false],
      ['reports/', 'Aggregated reporting screen.', false],
      ['settings/', 'System settings screen.', false],
      ['streams/', 'Stream routing management screen.', false],
      ['system/', 'System/ops dashboard screen.', false],
      ['traffic-sources/', 'Traffic-source configuration screen.', false],
      ['trends/', 'Trend-analysis screen.', false],
      ['users/', 'Admin user management screen.', false],
    ],
    workingIn: [
      'Keep page modules thin and push repeated layout or state concerns into `src/components/admin`.',
      'When adding a new admin feature page, update both this directory and the navigation registry.',
    ],
    testing: [
      'Verify the target page route renders without hydration or import errors.',
      'If a page consumes admin APIs, exercise both the page and its matching `app/api/admin/*` route.',
    ],
    patterns: [
      'Feature-per-folder routing with a single `page.tsx` entry per admin screen.',
      'Several files are placeholders, so new work may need to establish the first concrete implementation pattern.',
    ],
    internalDeps: ['`src/components/admin/`', '`src/app/api/admin/`', '`src/lib/admin/`'],
    externalDeps: ['Next.js pages/layouts'],
  },
  'src/app/(auth)': {
    title: '(auth)',
    purpose:
      'Authentication route group for admin login flows and any future auth-only screens that should not inherit the admin dashboard shell.',
    subdirectories: [['login/', 'Admin login page route.', false]],
    workingIn: [
      'Keep auth UX separate from admin shell concerns.',
      'Any credential/session changes must stay aligned with `src/lib/auth` helpers and the login/logout API routes.',
    ],
    testing: [
      'Exercise the login page and corresponding API round-trip when auth behavior changes.',
    ],
    patterns: ['Route group used to isolate auth presentation from admin content.'],
    internalDeps: ['`src/lib/auth/`', '`src/app/api/admin/login`', '`src/app/api/admin/logout`'],
    externalDeps: ['Next.js route groups'],
  },
  'src/app/api': {
    title: 'api',
    purpose:
      'HTTP route-handler surface for traffic ingestion and admin APIs. Most handlers are intentionally thin wrappers over `src/lib/tds`, Prisma, or auth helpers.',
    keyFiles: [
      ['route.ts', 'Base API route placeholder/entrypoint.'],
    ],
    subdirectories: [
      ['admin/', 'Authenticated CRUD and reporting endpoints for the admin UI.', true],
      ['click/', 'Primary traffic entrypoint and JSON click helper.', false],
      ['lp/', 'Landing-page follow-up routing endpoints.', false],
      ['postback/', 'Conversion postback processing endpoint.', false],
      ['safe/', 'Safe-page delivery endpoint for cloaked/bot traffic.', false],
    ],
    workingIn: [
      'Keep route handlers small and delegate parsing, validation, and business logic into `src/lib/`.',
      'Preserve compatibility with Keitaro-style parameter names such as `campaign_id`, `pub_id`, and `sub1-sub15`.',
    ],
    testing: [
      'Manually hit the modified endpoint with representative query/body data.',
      'Check `dev.log` after route edits because runtime errors surface there quickly in this environment.',
    ],
    patterns: [
      'Next.js `route.ts` modules export HTTP verb functions directly.',
      'Traffic endpoints call the pipeline runner rather than duplicating click logic.',
    ],
    internalDeps: ['`src/lib/auth/`', '`src/lib/db.ts`', '`src/lib/tds/`'],
    externalDeps: ['NextRequest / NextResponse'],
  },
  'src/app/api/admin': {
    title: 'admin',
    purpose:
      'Admin CRUD/reporting API namespace. Each child folder contains a `route.ts` module that handles one resource area or auth/session action.',
    subdirectories: [
      ['affiliate-networks/', 'Affiliate network CRUD route.', false],
      ['audit-logs/', 'Audit log listing route.', false],
      ['bot-rules/', 'Bot-rule CRUD route.', false],
      ['campaigns/', 'Campaign CRUD route.', false],
      ['clicks/', 'Click-reporting route.', false],
      ['conversions/', 'Conversion-reporting route.', false],
      ['domains/', 'Domain CRUD route.', false],
      ['landings/', 'Landing CRUD route.', false],
      ['login/', 'Admin login/session bootstrap route.', false],
      ['logout/', 'Admin logout route.', false],
      ['offers/', 'Offer CRUD route.', false],
      ['publishers/', 'Publisher CRUD route.', false],
      ['reports/', 'Aggregate reporting route.', false],
      ['settings/', 'System settings read/write route.', false],
      ['stats/', 'Dashboard statistics route.', false],
      ['streams/', 'Stream CRUD route.', false],
      ['traffic-sources/', 'Traffic source CRUD route.', false],
      ['users/', 'Admin user CRUD route.', false],
    ],
    workingIn: [
      'Apply auth checks consistently. Existing routes typically gate requests with `checkAuth(request)` from `src/lib/auth`.',
      'Resource routes favor straightforward Prisma calls and return JSON payloads without an extra service layer.',
    ],
    testing: [
      'Exercise the exact HTTP verb you changed with and without auth headers/cookies.',
      'If you alter payload shapes, confirm the corresponding admin page still parses the response.',
    ],
    patterns: [
      'One resource per folder, one `route.ts` per resource.',
      'Current handlers are pragmatic CRUD endpoints, not a fully abstracted REST layer.',
    ],
    internalDeps: ['`src/lib/auth/`', '`src/lib/db.ts`', '`src/types/admin/`'],
    externalDeps: ['Prisma Client', 'Next.js route handlers'],
  },
  'src/components': {
    title: 'components',
    purpose:
      'Reusable React component library for both the admin interface and the shared shadcn/ui primitive layer.',
    subdirectories: [
      ['admin/', 'Admin dashboard shells, navigation, and shared admin view building blocks.', true],
      ['ui/', 'shadcn/ui-derived primitive wrappers and composite controls.', true],
    ],
    workingIn: [
      'Prefer composing existing `ui/` primitives before introducing custom component patterns.',
      'Keep admin-specific presentation inside `admin/` so the shared primitives remain generic.',
    ],
    testing: [
      'Open the affected page or story-like usage path in `bun run dev` and verify class names/rendering.',
    ],
    patterns: ['shadcn-style component modules with colocated exports per primitive.'],
    internalDeps: ['`src/lib/utils.ts` for class merging.', '`src/hooks/` for responsive helpers/toasts.'],
    externalDeps: ['Radix UI primitives', 'Lucide React', 'Tailwind CSS'],
  },
  'src/components/admin': {
    title: 'admin',
    purpose:
      'Admin-specific component set: layout scaffolding, navigation structures, dashboard widgets, and shared empty/section states for feature pages.',
    subdirectories: [
      ['dashboard/', 'Dashboard-specific overview widgets.', false],
      ['layout/', 'Admin shell, header, sidebar, and content wrappers.', false],
      ['nav/', 'Navigation configuration and nav-rendering helpers.', false],
      ['shared/', 'Reusable admin page elements and placeholder content.', false],
    ],
    workingIn: [
      'Centralize admin chrome changes here rather than duplicating shell markup in individual pages.',
      'If a file is currently empty, establish the simplest reusable abstraction you can rather than over-building.',
    ],
    testing: [
      'Verify the target `/admin/*` routes still render correctly across desktop/mobile breakpoints.',
    ],
    patterns: [
      'Feature pages are expected to compose these shared layout pieces rather than owning navigation directly.',
      'Several modules are scaffold placeholders awaiting fuller implementation.',
    ],
    internalDeps: ['`src/components/ui/`', '`src/lib/admin/`', '`src/types/admin/`'],
    externalDeps: ['React', 'Lucide React'],
  },
  'src/components/ui': {
    title: 'ui',
    purpose:
      'shadcn/ui-style primitives and wrappers used across the app. This directory is the main design-system surface for cards, forms, overlays, navigation, tables, and feedback components.',
    keyFiles: [
      ['button.tsx', 'Core button primitive used throughout the UI.'],
      ['card.tsx', 'Standard card container matching the project dark theme.'],
      ['form.tsx', 'Form integration helpers built around React Hook Form.'],
      ['sidebar.tsx', 'Sidebar shell primitive used by admin navigation.'],
      ['table.tsx', 'Table primitives for scrollable/sticky admin data tables.'],
      ['toast.tsx', 'Toast markup and variants.'],
      ['toaster.tsx', 'Global toast presenter.'],
    ],
    workingIn: [
      'Stay close to shadcn conventions so future upstream syncs stay easy.',
      'Prefer extending an existing primitive via variants/classes before adding a one-off component here.',
    ],
    testing: [
      'Render the consuming page and verify keyboard/focus behavior for interactive primitives.',
      'Check dark-theme contrast because the project styling assumes slate/emerald surfaces.',
    ],
    patterns: [
      'One component per file with Tailwind class composition.',
      'Wrappers generally mirror Radix primitives and expose project-specific styling defaults.',
    ],
    internalDeps: ['`src/lib/utils.ts`', '`src/hooks/use-toast.ts`'],
    externalDeps: ['Radix UI packages', 'class-variance-authority', 'tailwind-merge'],
  },
  'src/hooks': {
    title: 'hooks',
    purpose:
      'Shared React hooks used by the UI layer. Current hooks handle responsive checks and toast state management.',
    keyFiles: [
      ['use-mobile.ts', 'Responsive/mobile detection helper.'],
      ['use-toast.ts', 'Toast state hook used by the notification UI.'],
    ],
    workingIn: [
      'Keep hooks framework-focused and avoid leaking route-specific business logic into this directory.',
    ],
    testing: ['Exercise the UI that consumes the hook to verify client-only assumptions remain valid.'],
    patterns: ['Hooks are small, focused helpers rather than a large state-management layer.'],
    internalDeps: ['`src/components/ui/`'],
    externalDeps: ['React hooks'],
  },
  'src/lib': {
    title: 'lib',
    purpose:
      'Shared runtime library code for Prisma access, admin metadata, authentication helpers, and the core TDS engine.',
    keyFiles: [
      ['db.ts', 'Singleton Prisma Client wiring for the Next.js runtime.'],
      ['utils.ts', 'Small shared utility helpers consumed by UI code.'],
    ],
    subdirectories: [
      ['admin/', 'Admin-side registries and metadata helpers.', true],
      ['auth/', 'Authentication/session helpers for admin APIs.', true],
      ['tds/', 'Traffic distribution system engine and support modules.', true],
    ],
    workingIn: [
      'Keep this directory framework-light where possible so route handlers and UI can share logic cleanly.',
      'Database/model changes here should stay aligned with `prisma/schema.prisma`.',
    ],
    testing: [
      'Re-hit any route handler that consumes the modified helper.',
      'Run lint after signature or import-path changes.',
    ],
    patterns: [
      'Thin wrappers for external systems, with domain-heavy logic concentrated under `tds/`.',
    ],
    internalDeps: ['`prisma/`', '`src/types/`', '`src/app/api/`'],
    externalDeps: ['Prisma Client', 'Next.js server runtime'],
  },
  'src/lib/admin': {
    title: 'admin',
    purpose:
      'Admin metadata layer. This is the intended home for module registry, navigation definition, JS configuration helpers, and other admin-only shared configuration.',
    keyFiles: [
      ['auth.ts', 'Admin-facing auth utilities used by client/admin flows.'],
      ['js-config.ts', 'Client-consumable admin configuration helper.'],
      ['module-registry.ts', 'Reserved module registry scaffold; currently empty.'],
      ['navigation.ts', 'Reserved navigation helper scaffold; currently empty.'],
    ],
    workingIn: [
      'Prefer storing admin metadata and registries here rather than embedding them directly in page files.',
      'Empty files here are scaffolds; fill them deliberately instead of assuming they are dead code.',
    ],
    testing: ['Verify the admin shell or page consuming the config still renders.'],
    patterns: ['Configuration-oriented modules rather than domain services.'],
    internalDeps: ['`src/components/admin/`', '`src/types/admin/`', '`src/lib/auth/`'],
    externalDeps: ['TypeScript only; no special runtime dependency beyond Next.js where imported'],
  },
  'src/lib/auth': {
    title: 'auth',
    purpose:
      'Authentication and session helpers for admin routes. The current implementation centers on API-key validation plus cookie-based convenience for browser access.',
    keyFiles: [
      ['admin-auth.ts', 'Primary admin auth implementation and middleware helpers.'],
      ['index.ts', 'Re-export surface for auth helpers.'],
    ],
    workingIn: [
      'Preserve the supported auth vectors unless you update every admin consumer: Bearer, `X-API-Key`, query fallback, and cookie session.',
      'Be careful with development-only shortcuts such as localhost auth bypasses.',
    ],
    testing: [
      'Exercise login, logout, and at least one protected route after auth changes.',
      'Validate both authenticated and unauthenticated responses.',
    ],
    patterns: [
      'Pure helper functions wrapping Next.js request/response types.',
      'Route handlers call `checkAuth` or higher-order wrappers rather than duplicating validation.',
    ],
    internalDeps: ['`src/app/api/admin/login/`', '`src/app/api/admin/logout/`'],
    externalDeps: ['NextRequest / NextResponse'],
  },
  'src/lib/tds': {
    title: 'tds',
    purpose:
      'Core traffic distribution system implementation. This subtree contains click processing, bot detection, macro expansion, action execution, routing filters, and the staged pipeline that mirrors Keitaro concepts.',
    keyFiles: [
      ['click-processor.ts', 'Standalone click-processing engine with campaign/publisher lookup and cloaking decisions.'],
      ['bot-detection.ts', 'Bot detection heuristics and cloaking helpers.'],
      ['click-id.ts', 'Click ID generation and validation helpers.'],
      ['rotator.ts', 'Stream/offer/landing rotation support.'],
      ['index.ts', 'Top-level re-export surface for TDS modules.'],
      ['macros.ts', 'Compatibility entrypoint for macro-related helpers.'],
    ],
    subdirectories: [
      ['actions/', 'Action types and registries that turn pipeline choices into responses.', true],
      ['contexts/', 'Context objects shared during TDS execution.', true],
      ['data/', 'Static lookup data such as countries, browsers, operators, and bot signatures.', true],
      ['filters/', 'Stream filter implementations for targeting and uniqueness.', true],
      ['macros/', 'Macro registry, processor, and predefined macro handlers.', true],
      ['pipeline/', 'Staged click-processing pipeline and stage runner.', true],
      ['services/', 'Support services such as GeoIP, cookies, proxy, and entity binding.', true],
      ['utils/', 'Small TDS-specific helper utilities.', true],
    ],
    workingIn: [
      'Preserve Keitaro terminology and flow ordering when changing pipeline behavior.',
      'Avoid duplicating logic between the legacy `click-processor.ts` path and the newer staged pipeline without a deliberate migration plan.',
    ],
    testing: [
      'Hit `/api/click`, `/api/click/json`, `/api/postback`, or `/api/lp/offer` with representative traffic parameters.',
      'Check `dev.log` for stage failures because the pipeline logs heavily at runtime.',
    ],
    patterns: [
      'Keitaro-inspired domain model and vocabulary.',
      'Thin route handlers delegate to a staged pipeline or focused helpers inside this subtree.',
    ],
    internalDeps: ['`src/lib/db.ts`', '`prisma/schema.prisma`', '`reference/` for parity research'],
    externalDeps: ['Prisma Client', 'Next.js server runtime', 'optional MaxMind runtime module'],
  },
  'src/lib/tds/actions': {
    title: 'actions',
    purpose:
      'Action abstraction layer for the TDS engine. Action classes describe how a selected stream/offer responds: redirects, content bodies, frames, local files, remote fetches, and other Keitaro-style behaviors.',
    keyFiles: [
      ['base.ts', 'Abstract action contract shared by every action class.'],
      ['repository.ts', 'Singleton registry that maps action keys to classes and metadata.'],
      ['types.ts', 'Shared action result/type definitions.'],
      ['index.ts', 'Action exports.'],
    ],
    subdirectories: [['predefined/', 'Concrete built-in action implementations.', true]],
    workingIn: [
      'Register new actions in `repository.ts` or they will never be discoverable at runtime.',
      'Keep action classes focused on response generation; selection logic belongs in pipeline stages.',
    ],
    testing: [
      'Exercise a route that chooses the modified action type and inspect the resulting redirect/body/headers.',
    ],
    patterns: ['Registry plus concrete class implementations.'],
    internalDeps: ['`src/lib/tds/pipeline/`', '`src/lib/tds/macros/`'],
    externalDeps: ['Next.js response semantics when actions are materialized by the runner'],
  },
  'src/lib/tds/actions/predefined': {
    title: 'predefined',
    purpose:
      'Built-in action implementations that model common Keitaro delivery behaviors such as HTTP redirects, meta refreshes, frames, content responses, remote fetching, and special campaign hand-offs.',
    keyFiles: [
      ['http-redirect.ts', 'HTTP 301/302 redirect implementations.'],
      ['meta.ts', 'Meta refresh action implementations.'],
      ['iframe.ts', 'Iframe/frame-style action implementations.'],
      ['content.ts', 'HTML/text/404/do-nothing content actions.'],
      ['to-campaign.ts', 'Action that forwards flow into another campaign.'],
    ],
    workingIn: [
      'Match the repository keys and payload expectations used by upstream action selection.',
      'Be explicit about headers/content types when an action stops being a plain redirect.',
    ],
    testing: ['Trigger the exact action via a route or pipeline fixture and inspect the final response semantics.'],
    patterns: ['One action family per file, often exporting multiple closely related classes.'],
    internalDeps: ['`src/lib/tds/actions/base.ts`', '`src/lib/tds/macros/`'],
    externalDeps: ['Standard web response primitives'],
  },
  'src/lib/tds/contexts': {
    title: 'contexts',
    purpose:
      'Shared execution contexts used while evaluating landing and gateway flows inside the TDS engine.',
    keyFiles: [
      ['gateway-context.ts', 'Gateway/request context model.'],
      ['landing-context.ts', 'Landing-click context model.'],
      ['index.ts', 'Context exports.'],
    ],
    workingIn: ['Keep context objects lean and serializable enough for debugging/logging.'],
    testing: ['Exercise the consumer path that constructs the modified context.'],
    patterns: ['Context data classes/interfaces consumed by pipeline and macros layers.'],
    internalDeps: ['`src/lib/tds/pipeline/`', '`src/lib/tds/macros/`'],
    externalDeps: ['TypeScript only'],
  },
  'src/lib/tds/data': {
    title: 'data',
    purpose:
      'Static lookup datasets used by filters, macros, and bot detection: countries, browsers, languages, connection types, operators, and search engines.',
    keyFiles: [
      ['bot-signatures.ts', 'Known signatures used by bot detection.'],
      ['countries.ts', 'Country metadata used across targeting and macros.'],
      ['browsers.ts', 'Browser lookup data.'],
      ['languages.ts', 'Language metadata.'],
      ['index.ts', 'Barrel exports for static datasets.'],
    ],
    workingIn: [
      'Prefer additive edits and keep data normalized for predictable lookups.',
      'If a dataset grows substantially, document its source or refresh path in comments or docs.',
    ],
    testing: ['Run the consuming filter/macro flow to verify lookup keys still match runtime expectations.'],
    patterns: ['Static data modules with barrel exports.'],
    internalDeps: ['`src/lib/tds/filters/`', '`src/lib/tds/macros/`', '`src/lib/tds/bot-detection.ts`'],
    externalDeps: ['None beyond TypeScript runtime'],
  },
  'src/lib/tds/filters': {
    title: 'filters',
    purpose:
      'Targeting and gating filters for stream selection. This includes geo, device, browser, OS, connection, limits, and uniqueness checks.',
    keyFiles: [
      ['types.ts', 'Common filter contracts.'],
      ['index.ts', 'Filter exports.'],
      ['country.ts', 'Country-based targeting filter.'],
      ['device-type.ts', 'Device-type targeting filter.'],
      ['uniqueness.ts', 'Uniqueness-related filter logic.'],
    ],
    workingIn: [
      'Filters should remain deterministic and easy to combine during stream evaluation.',
      'Coordinate any schema/payload changes with the stored filter payload format.',
    ],
    testing: ['Run a click flow that exercises the filter you changed and verify stream choice/output.'],
    patterns: ['Filter-per-file with shared type contracts.'],
    internalDeps: ['`src/lib/tds/data/`', '`src/lib/tds/pipeline/stages/choose-stream.ts`'],
    externalDeps: ['TypeScript only'],
  },
  'src/lib/tds/macros': {
    title: 'macros',
    purpose:
      'Macro-expansion subsystem for URLs and response content. It parses `{macro}` / `$macro` tokens, resolves them from registry implementations or request params, and applies encoding rules.',
    keyFiles: [
      ['processor.ts', 'Core parser and replacement engine for macro expansion.'],
      ['registry.ts', 'Macro registry that maps names to implementations.'],
      ['types.ts', 'Macro context and parser item types.'],
      ['index.ts', 'Exports for macro helpers.'],
    ],
    subdirectories: [['predefined/', 'Concrete macro implementations grouped by subject area.', true]],
    workingIn: [
      'Keep macro naming and raw/encoded behavior backward-compatible because URLs and templates depend on it.',
      'If you add a macro, wire it into the registry and document the expected context keys.',
    ],
    testing: [
      'Run the exact action or URL-building path that emits the macro output.',
      'Verify both encoded and raw modes when the macro supports them.',
    ],
    patterns: ['Registry-driven macro implementations with a central parser.'],
    internalDeps: ['`src/lib/tds/contexts/`', '`src/lib/tds/actions/`', '`src/lib/tds/pipeline/`'],
    externalDeps: ['TypeScript only'],
  },
  'src/lib/tds/macros/predefined': {
    title: 'predefined',
    purpose:
      'Subject-oriented macro implementations for campaign, stream, geo, referrer, device, network, randomization, tracking, and request metadata.',
    keyFiles: [
      ['campaign.ts', 'Campaign-related macros.'],
      ['stream.ts', 'Stream-related macros.'],
      ['geo.ts', 'Geo-oriented macros.'],
      ['referrer.ts', 'Referrer macros.'],
      ['tracking.ts', 'Tracking/click identifiers and related macros.'],
    ],
    workingIn: [
      'Keep macro files grouped by subject area and avoid scattering overlapping names.',
      'Make sure new macro names remain unique across the registry.',
    ],
    testing: ['Exercise a content or redirect path that expands the macro and inspect the rendered value.'],
    patterns: ['Multiple small macro classes/functions grouped by domain topic.'],
    internalDeps: ['`src/lib/tds/macros/registry.ts`', '`src/lib/tds/data/`', '`src/lib/tds/services/`'],
    externalDeps: ['TypeScript only'],
  },
  'src/lib/tds/pipeline': {
    title: 'pipeline',
    purpose:
      'Staged click-processing engine that mirrors Keitaro-style first-level and second-level flow execution. It owns payload state, runner adapters, and the ordered stage lists.',
    keyFiles: [
      ['pipeline.ts', 'Main staged pipeline orchestration with first-level and second-level stage order.'],
      ['runner.ts', 'Adapter between Next.js route handlers and the pipeline engine.'],
      ['payload.ts', 'Mutable pipeline payload/state carrier.'],
      ['types.ts', 'Stage contracts and result types.'],
    ],
    subdirectories: [['stages/', 'Concrete pipeline stage implementations.', true]],
    workingIn: [
      'Stage order matters. Reordering stages can change click semantics, persistence, and recursion behavior.',
      'When introducing a new stage, decide whether it belongs in first-level flow, second-level flow, or both.',
    ],
    testing: [
      'Exercise the relevant route (`/api/click`, `/api/click/json`, `/api/lp/offer`) and inspect the final response.',
      'Review logs for abort conditions, recursion handling, and payload mutations.',
    ],
    patterns: [
      'Pipeline + payload + stage classes.',
      'Runner translates Next.js requests into payload objects and payloads back into `NextResponse`s.',
    ],
    internalDeps: ['`src/lib/tds/actions/`', '`src/lib/tds/filters/`', '`src/lib/tds/services/`'],
    externalDeps: ['Next.js server runtime'],
  },
  'src/lib/tds/pipeline/stages': {
    title: 'stages',
    purpose:
      'Concrete ordered pipeline stages covering request normalization, campaign lookup, targeting, selection, token/cookie handling, action execution, and click persistence.',
    keyFiles: [
      ['build-raw-click.ts', 'Initial raw click construction stage.'],
      ['check-bot.ts', 'Bot detection/cloaking stage.'],
      ['find-campaign.ts', 'Campaign lookup stage.'],
      ['choose-stream.ts', 'Stream selection stage.'],
      ['choose-offer.ts', 'Offer selection stage.'],
      ['execute-action.ts', 'Final action execution stage.'],
      ['store-raw-clicks.ts', 'Persistence stage for raw click storage.'],
    ],
    workingIn: [
      'Each stage should own one coherent mutation/decision step and communicate through the shared payload.',
      'Prefer adding logging at the payload/stage boundary when diagnosing flow issues.',
    ],
    testing: [
      'Exercise a traffic request that reaches the modified stage.',
      'If the stage mutates payload fields used later, verify downstream stages still behave correctly.',
    ],
    patterns: ['One stage per file implementing a shared interface.'],
    internalDeps: ['`src/lib/tds/pipeline/payload.ts`', '`src/lib/tds/services/`', '`src/lib/tds/actions/`'],
    externalDeps: ['TypeScript only'],
  },
  'src/lib/tds/services': {
    title: 'services',
    purpose:
      'Support services for the TDS runtime: GeoIP lookup, cookie handling, entity binding, proxy awareness, LP token creation, and related cross-cutting concerns.',
    keyFiles: [
      ['geo-db-service.ts', 'GeoIP resolution service with MaxMind and development fallback logic.'],
      ['cookies-service.ts', 'Cookie read/write helpers.'],
      ['entity-binding-service.ts', 'Visitor/entity binding helpers.'],
      ['lp-token-service.ts', 'Landing-page token helpers.'],
      ['proxy-service.ts', 'Proxy-related request helpers.'],
      ['index.ts', 'Service exports.'],
    ],
    workingIn: [
      'Be careful with optional infrastructure dependencies such as MaxMind databases; development fallbacks are intentional.',
      'Service changes often affect several stages at once, so verify all consumers.',
    ],
    testing: [
      'Run the flow that consumes the service and verify both happy-path and fallback behavior where relevant.',
    ],
    patterns: ['Function-based service modules with small shared state/caches when necessary.'],
    internalDeps: ['`src/lib/tds/pipeline/`', '`src/lib/tds/data/`', '`src/lib/db.ts`'],
    externalDeps: ['optional `maxmind` runtime dependency', 'Fetch API', 'Node `fs`/`path` where needed'],
  },
  'src/lib/tds/utils': {
    title: 'utils',
    purpose:
      'Small TDS-specific utility helpers that do not fit cleanly into actions, services, or pipeline stages.',
    keyFiles: [
      ['raw-click-serializer.ts', 'Helpers for serializing stored raw click payloads.'],
      ['index.ts', 'Utility exports.'],
    ],
    workingIn: ['Keep this directory for lightweight helpers, not new domain subsystems.'],
    testing: ['Exercise the consuming storage/reporting path after utility changes.'],
    patterns: ['Small focused helpers and barrel exports.'],
    internalDeps: ['`src/lib/tds/pipeline/`', '`src/lib/tds/services/`'],
    externalDeps: ['TypeScript only'],
  },
  'src/types': {
    title: 'types',
    purpose:
      'Shared TypeScript definition layer. Currently focused on admin configuration and navigation shapes.',
    subdirectories: [['admin/', 'Admin-specific type definitions.', true]],
    workingIn: ['Prefer centralizing reusable interface changes here rather than duplicating ad hoc object shapes.'],
    testing: ['Run lint or type-aware editor checks after changing exported types.'],
    patterns: ['Lightweight type-only modules grouped by feature.'],
    internalDeps: ['`src/lib/admin/`', '`src/components/admin/`'],
    externalDeps: ['TypeScript'],
  },
  'src/types/admin': {
    title: 'admin',
    purpose:
      'Admin-side TypeScript contracts for configuration objects and navigation structures.',
    keyFiles: [
      ['config.ts', 'Admin configuration types.'],
      ['navigation.ts', 'Navigation item and section types.'],
    ],
    workingIn: ['Update the consuming admin libraries/components in the same change when these types evolve.'],
    testing: ['Run lint or editor type checks where the exported types are consumed.'],
    patterns: ['Pure type-definition files without runtime behavior.'],
    internalDeps: ['`src/lib/admin/`', '`src/components/admin/nav/`'],
    externalDeps: ['TypeScript'],
  },
  prisma: {
    title: 'prisma',
    purpose:
      'Prisma schema and seed assets for the SQLite-backed TDS data model.',
    keyFiles: [
      ['schema.prisma', 'Canonical data model for campaigns, streams, clicks, conversions, auth, and settings.'],
      ['seed.ts', 'Database seeding entrypoint.'],
    ],
    workingIn: [
      'Schema changes must stay aligned with code in `src/lib/db.ts` and any Prisma queries in API routes.',
      'Prefer additive migrations/changes over breaking renames unless you are updating all consumers.',
    ],
    testing: [
      'Run `bun run db:generate` and `bun run db:push` when schema changes are intentional.',
      'Smoke-test affected API routes after changing model fields or relations.',
    ],
    patterns: ['Single Prisma schema for a local SQLite database.'],
    internalDeps: ['`db/`', '`src/lib/db.ts`', '`src/app/api/`'],
    externalDeps: ['Prisma ORM', 'SQLite'],
  },
  db: {
    title: 'db',
    purpose:
      'Database runtime directory. The current repo stores the SQLite database file here.',
    keyFiles: [['custom.db', 'SQLite database used by local development/testing.']],
    workingIn: [
      'Treat checked-in database files as environment artifacts unless the task explicitly calls for updating them.',
    ],
    testing: ['If the DB file changes intentionally, re-run the affected flows against the local app.'],
    patterns: ['Runtime data rather than source code.'],
    internalDeps: ['`prisma/`', '`src/lib/db.ts`'],
    externalDeps: ['SQLite'],
  },
  docs: {
    title: 'docs',
    purpose:
      'Project documentation and working notes: status tracking, runbooks, model guidance, verification reports, and historical change notes.',
    keyFiles: [
      ['runbook.md', 'Operational runbook for working with the project.'],
      ['project_status.md', 'High-level status snapshot.'],
      ['VERIFICATION_REPORT.md', 'Captured verification findings.'],
      ['changes.md', 'Change log/history notes.'],
    ],
    workingIn: [
      'Keep docs aligned with the current repo state; stale instructions are more harmful than missing detail.',
      'Favor concise, action-oriented documentation over speculative prose.',
    ],
    testing: ['No runtime test required, but verify referenced commands/paths still exist.'],
    patterns: ['Operational and status documentation in Markdown.'],
    internalDeps: ['`planning/`', '`AGENTS.md`', '`src/`'],
    externalDeps: ['Markdown only'],
  },
  public: {
    title: 'public',
    purpose:
      'Static assets served directly by Next.js.',
    keyFiles: [
      ['logo.svg', 'Project logo asset.'],
      ['robots.txt', 'Crawler instructions for the deployed app.'],
    ],
    workingIn: ['Keep filenames stable when referenced from app code or metadata.'],
    testing: ['Load the asset or route in the dev server after changes.'],
    patterns: ['Plain static files served at root-relative URLs.'],
    internalDeps: ['`src/app/` for metadata/layout usage.'],
    externalDeps: ['Next.js static asset serving'],
  },
  scripts: {
    title: 'scripts',
    purpose:
      'Repository maintenance and validation scripts. Most current scripts support cross-platform search and validation workflows; this directory now also owns the deep-init generator.',
    keyFiles: [
      ['deepinit-generate-agents.mjs', 'Generates and refreshes the AGENTS.md hierarchy for the live project surface.'],
      ['search_repo.sh', 'Unix search helper.'],
      ['validate-all.sh', 'Unix validation umbrella script.'],
      ['validate-skills.sh', 'Skill validation helper.'],
      ['validate-workflows.sh', 'Workflow validation helper.'],
    ],
    workingIn: [
      'Keep scripts portable where practical; this directory intentionally includes paired `.sh` and `.ps1` variants.',
      'If you add a new repo-maintenance script, document it here and in the root navigation if it becomes important.',
    ],
    testing: [
      'Run the script you changed directly instead of assuming it remains executable.',
    ],
    patterns: ['Cross-platform maintenance scripts and one-off tooling.'],
    internalDeps: ['`docs/` and `planning/` for maintenance context.'],
    externalDeps: ['Shell / PowerShell / Node depending on script'],
  },
  tasks: {
    title: 'tasks',
    purpose:
      'Human-maintained working notes for outstanding work and lessons learned.',
    keyFiles: [
      ['todo.md', 'Open task list.'],
      ['lessons.md', 'Captured lessons and follow-up insights.'],
    ],
    workingIn: ['Keep entries short, actionable, and date-aware if the note is time-sensitive.'],
    testing: ['No runtime testing required.'],
    patterns: ['Lightweight Markdown planning artifacts.'],
    internalDeps: ['`docs/`', '`planning/`'],
    externalDeps: ['Markdown only'],
  },
  adapters: {
    title: 'adapters',
    purpose:
      'Provider/model adapter notes used to steer external assistants or model-specific prompting behavior.',
    keyFiles: [
      ['CLAUDE.md', 'Adapter guidance for Claude-based workflows.'],
      ['GEMINI.md', 'Adapter guidance for Gemini-based workflows.'],
      ['GPT_OSS.md', 'Adapter guidance for GPT/open-source style workflows.'],
    ],
    workingIn: [
      'Keep model/provider-specific instructions clearly separated so they do not bleed into project source documentation.',
    ],
    testing: ['No runtime test; verify referenced conventions still match project reality.'],
    patterns: ['Instructional Markdown per provider.'],
    internalDeps: ['`AGENTS.md`', '`docs/`'],
    externalDeps: ['Markdown only'],
  },
  examples: {
    title: 'examples',
    purpose:
      'Small standalone reference implementations used to validate isolated patterns outside the main application.',
    subdirectories: [['websocket/', 'Minimal websocket example with separate frontend/server files.', true]],
    workingIn: ['Keep examples minimal and clearly isolated from production app code.'],
    testing: ['Run the example directly if you change it.'],
    patterns: ['Self-contained demonstration folders.'],
    internalDeps: ['`docs/` for explanation when examples become important.'],
    externalDeps: ['Depends on the example'],
  },
  'examples/websocket': {
    title: 'websocket',
    purpose:
      'Minimal websocket example split into a frontend client and a simple server implementation.',
    keyFiles: [
      ['frontend.tsx', 'Client-side example UI for websocket interaction.'],
      ['server.ts', 'Server-side websocket example implementation.'],
    ],
    workingIn: ['Treat this as a sandbox example, not a shared production abstraction.'],
    testing: ['Run the example end to end if behavior changes.'],
    patterns: ['Paired server/client demonstration files.'],
    internalDeps: ['`examples/`'],
    externalDeps: ['Framework/runtime implied by the example code'],
  },
  planning: {
    title: 'planning',
    purpose:
      'Architecture and project-planning artifacts captured during prior analysis work.',
    subdirectories: [['codebase/', 'Snapshot-style notes about structure, conventions, integrations, and testing.', true]],
    workingIn: ['Keep planning docs in sync with major architectural shifts or mark them explicitly as historical snapshots.'],
    testing: ['No runtime test required.'],
    patterns: ['Markdown planning artifacts grouped by topic.'],
    internalDeps: ['`docs/`', '`AGENTS.md`'],
    externalDeps: ['Markdown only'],
  },
  'planning/codebase': {
    title: 'codebase',
    purpose:
      'Focused architecture snapshot documents covering stack, structure, integrations, testing expectations, and current concerns.',
    keyFiles: [
      ['ARCHITECTURE.md', 'Architecture overview.'],
      ['STRUCTURE.md', 'Directory/code organization notes.'],
      ['CONVENTIONS.md', 'Implementation conventions.'],
      ['INTEGRATIONS.md', 'External/internal integration notes.'],
      ['TESTING.md', 'Testing expectations and gaps.'],
    ],
    workingIn: ['Use these files as planning references, then update them if implementation reality moves.'],
    testing: ['No runtime test required.'],
    patterns: ['One topic per planning document.'],
    internalDeps: ['`planning/`', '`docs/`', '`src/`'],
    externalDeps: ['Markdown only'],
  },
  reference: {
    title: 'reference',
    purpose:
      'Archived upstream/reference implementations used for parity research and reverse engineering. This directory intentionally contains foreign codebases and vendored trees that should not receive full deep-init expansion by default.',
    subdirectories: [
      ['Keitaro_source_php/', 'Large PHP Keitaro reference codebase.', false],
      ['KeitaroCustomScripts/', 'Custom Keitaro scripts reference repo.', false],
      ['YellowCloaker/', 'Archived cloaker codebase reference.', false],
      ['akm-traffic-tracker/', 'Traffic tracker reference project.', false],
      ['pp_adsensor/', 'Adsensor reference project.', false],
    ],
    workingIn: [
      'Treat everything here as read-mostly source material unless a task explicitly asks you to modify a reference project.',
      'Do not mirror the entire nested vendor structure with AGENTS files unless the user requests deep documentation for a specific reference repo.',
    ],
    testing: ['No project-wide testing is expected for reference material changes unless working inside one reference repo on purpose.'],
    patterns: ['Mixed third-party/project archives, often with their own VCS metadata and vendor trees.'],
    internalDeps: ['`src/lib/tds/` and `docs/` may cite these projects for parity research.'],
    externalDeps: ['Varies by reference project'],
  },
  'mini-services': {
    title: 'mini-services',
    purpose:
      'Reserved container for future sidecar or helper services. It is currently empty aside from a placeholder file.',
    workingIn: ['Create a child service folder before adding implementation files directly here.'],
    testing: ['No runtime test required until a concrete service exists.'],
    patterns: ['Container directory only.'],
    internalDeps: ['None yet'],
    externalDeps: ['None yet'],
  },
};

function ensureArray(value) {
  return Array.isArray(value) ? value : [];
}

function table(headers, rows) {
  if (!rows.length) {
    return ['None currently documented.'];
  }

  return [
    `| ${headers.join(' | ')} |`,
    `| ${headers.map(() => '---').join(' | ')} |`,
    ...rows.map((row) => `| ${row.join(' | ')} |`),
  ];
}

function preserveManualSection(existing) {
  if (!existing.includes('<!-- MANUAL:')) {
    return '<!-- MANUAL: Add directory-specific notes below this line. -->\n';
  }

  return existing.slice(existing.indexOf('<!-- MANUAL:'));
}

function buildSubdirRows(subdirectories) {
  return subdirectories.map(([dirName, purpose, hasAgents]) => [
    `\`${dirName}\``,
    hasAgents ? `${purpose} (see \`${dirName}AGENTS.md\`)` : purpose,
  ]);
}

function buildKeyFileRows(keyFiles) {
  return keyFiles.map(([fileName, description]) => [`\`${fileName}\``, description]);
}

function buildBulletLines(items) {
  if (!items.length) {
    return ['- None documented yet.'];
  }

  return items.map((item) => `- ${item}`);
}

function parentCommentFor(dirPath) {
  return `<!-- Parent: ../AGENTS.md -->`;
}

function buildAgentsContent(dirPath, spec, existing = '') {
  const keyFiles = ensureArray(spec.keyFiles);
  const subdirectories = ensureArray(spec.subdirectories);
  const workingIn = ensureArray(spec.workingIn);
  const testing = ensureArray(spec.testing);
  const patterns = ensureArray(spec.patterns);
  const internalDeps = ensureArray(spec.internalDeps);
  const externalDeps = ensureArray(spec.externalDeps);
  const manualSection = preserveManualSection(existing);
  const title = spec.title || path.basename(dirPath);

  const lines = [
    parentCommentFor(dirPath),
    `<!-- Generated: ${today} | Updated: ${today} -->`,
    '',
    `# ${title}`,
    '',
    '## Purpose',
    spec.purpose,
    '',
    '## Key Files',
    ...table(['File', 'Description'], buildKeyFileRows(keyFiles)),
    '',
    '## Subdirectories',
    ...table(['Directory', 'Purpose'], buildSubdirRows(subdirectories)),
    '',
    '## For AI Agents',
    '',
    '### Working In This Directory',
    ...buildBulletLines(workingIn),
    '',
    '### Testing Requirements',
    ...buildBulletLines(testing),
    '',
    '### Common Patterns',
    ...buildBulletLines(patterns),
    '',
    '## Dependencies',
    '',
    '### Internal',
    ...buildBulletLines(internalDeps),
    '',
    '### External',
    ...buildBulletLines(externalDeps),
    '',
    manualSection.trimEnd(),
    '',
  ];

  return `${lines.join('\n')}`;
}

function writeAgentsFile(relPath) {
  const spec = specs[relPath];
  const absDir = path.join(rootDir, relPath);
  const absFile = path.join(absDir, 'AGENTS.md');
  const existing = fs.existsSync(absFile) ? fs.readFileSync(absFile, 'utf8') : '';
  const content = buildAgentsContent(relPath, spec, existing);
  fs.writeFileSync(absFile, content, 'utf8');
}

function updateRootAgents() {
  const spec = specs['.'];
  const rootFile = path.join(rootDir, 'AGENTS.md');
  const current = fs.readFileSync(rootFile, 'utf8');
  const rows = buildSubdirRows(spec.subdirectories);
  const sectionLines = [
    '<!-- DEEPINIT:START -->',
    '',
    '## Deep Init Navigation',
    '',
    `Generated: ${today}`,
    '',
    spec.purpose,
    '',
    ...table(['Directory', 'Purpose'], rows),
    '',
    '### Notes For Agents',
    ...buildBulletLines(spec.notes),
    '',
    '<!-- DEEPINIT:END -->',
  ];
  const section = sectionLines.join('\n');

  const updated = current.includes('<!-- DEEPINIT:START -->')
    ? current.replace(/<!-- DEEPINIT:START -->[\s\S]*<!-- DEEPINIT:END -->/, section)
    : `${current.trimEnd()}\n\n${section}\n`;

  fs.writeFileSync(rootFile, updated, 'utf8');
}

function main() {
  updateRootAgents();

  Object.keys(specs)
    .filter((key) => key !== '.')
    .sort((a, b) => a.split('/').length - b.split('/').length || a.localeCompare(b))
    .forEach(writeAgentsFile);

  console.log(`Generated AGENTS hierarchy for ${Object.keys(specs).length} directories on ${today}.`);
}

main();
