# Testing Patterns

## Current State: **No Testing Infrastructure**

### Test Framework
- **None configured**. No test runner in `package.json` scripts
- No test dependencies (`jest`, `vitest`, `mocha`, `playwright`, `cypress`, etc.) in `package.json`
- No `vitest.config.*`, `jest.config.*`, or `playwright.config.*` files
- No test-related fields in `tsconfig.json` (e.g., no `types: ["jest"]`)

### Test Files
- **Zero test files found**. Searched for:
  - `**/*.test.*` — 0 results
  - `**/*.spec.*` — 0 results
  - `**/__tests__/**` — not searched (no test infrastructure to reference)

### Test Coverage
- **0%** — no tests exist
- No coverage tooling (no `istanbul`, `nyc`, `c8`, or `vitest --coverage`)

## Gap Analysis

### Critical Areas Without Tests

| Area | Risk | Priority |
|------|------|----------|
| **Click ID generation** (`click-id.ts`) | Collision logic, parsing, validation — crypto-dependent | **High** |
| **Click processor** (`click-processor.ts`) | Core business logic — 12-step pipeline | **High** |
| **Bot detection** (`bot-detection.ts`) | Security-critical cloaking logic | **High** |
| **Filter system** (`filters/index.ts`) | 25+ filter implementations, registry pattern | **High** |
| **Auth middleware** (`admin-auth.ts`) | API key validation, session management | **High** |
| **Pipeline stages** (`pipeline/stages/*`) | 10+ stages with complex orchestration | **Medium** |
| **API routes** (`app/api/**`) | Input validation, response formatting | **Medium** |
| **Macro processor** (`macros/processor.ts`) | Template string substitution | **Medium** |
| **Services** (`services/*`) | IP info, proxy detection, cookies | **Low** |
| **UI components** (`components/**`) | Mostly shadcn/ui generated — less risk | **Low** |

### Recommended Testing Strategy

#### Framework Choice
- **Vitest** — best fit for this codebase:
  - Native TypeScript support (no extra config)
  - Fast execution (Vite-native)
  - Compatible with existing `tsconfig.json` (ESNext modules, path aliases)
  - Works with `bun` as test runner
  - Jest-compatible API for easy migration

#### Test Directory Structure
```
src/
├── lib/
│   ├── tds/
│   │   ├── click-id.ts
│   │   ├── click-id.test.ts        # Unit tests
│   │   ├── click-processor.ts
│   │   ├── click-processor.test.ts
│   │   ├── bot-detection.ts
│   │   ├── bot-detection.test.ts
│   │   ├── filters/
│   │   │   ├── index.ts
│   │   │   └── filters.test.ts     # All filter tests
│   │   ├── pipeline/
│   │   │   ├── types.ts
│   │   │   ├── pipeline.test.ts
│   │   │   └── stages/
│   │   │       └── *.test.ts
│   │   └── ...
│   ├── auth/
│   │   ├── admin-auth.ts
│   │   └── admin-auth.test.ts
│   └── ...
├── app/
│   └── api/
│       ├── click/
│       │   └── route.test.ts       # API integration tests
│       └── admin/
│           └── campaigns/
│               └── route.test.ts
└── ...
```

#### Test Types Needed

1. **Unit Tests** (highest priority)
   - Click ID: `generateClickId()`, `parseClickId()`, `isValidClickId()` — pure functions, easy to test
   - Filters: Each `FilterInterface.process()` method with mock `RawClick` data
   - Auth: `verifyAdminAuth()` with mock `NextRequest` objects
   - Stream selection: `selectStream()` weighted random logic

2. **Integration Tests** (medium priority)
   - API routes with mocked `db` (Prisma mock)
   - Click processing end-to-end with mocked dependencies
   - Auth flow: login → session → authenticated request

3. **E2E Tests** (lower priority — would require more setup)
   - Full click flow: request → processing → redirect
   - Admin CRUD operations via HTTP

#### Mocking Requirements

- **Prisma Client**: Mock `db.*` calls (critical — all data access)
- **NextRequest/NextResponse**: Create test helpers or use `node-mocks-http`
- **Crypto**: Mock `randomBytes` for deterministic click ID tests
- **Environment**: Mock `process.env.ADMIN_API_KEY`, `process.env.NODE_ENV`

### Setup Steps

1. Install: `bun add -d vitest @testing-library/react @testing-library/jest-dom`
2. Create `vitest.config.ts` with path alias resolution
3. Add `"test": "vitest"`, `"test:run": "vitest run"`, `"test:coverage": "vitest run --coverage"` to scripts
4. Add `"types": ["vitest/globals"]` to `tsconfig.json`
5. Start with pure function tests (click-id, filters) — no mocking needed
6. Progress to integration tests with Prisma mocking

### Estimated Test Coverage Targets

| Phase | Target | Files |
|-------|--------|-------|
| Phase 1: Pure functions | 80%+ line coverage | click-id.ts, filters/*.ts, utils |
| Phase 2: Business logic | 70%+ line coverage | click-processor.ts, bot-detection.ts, pipeline |
| Phase 3: API layer | 60%+ line coverage | All route.ts files |
| Phase 4: Auth & security | 90%+ line coverage | admin-auth.ts, all auth checks |
