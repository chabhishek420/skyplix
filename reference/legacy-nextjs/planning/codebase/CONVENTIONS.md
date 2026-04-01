# Codebase Conventions

## Naming Patterns

| Element | Convention | Examples |
|---------|-----------|---------|
| **Files (lib/modules)** | `kebab-case.ts` | `click-processor.ts`, `bot-detection.ts`, `admin-auth.ts` |
| **Files (UI components)** | `kebab-case.tsx` | `button.tsx`, `alert-dialog.tsx`, `data-table.tsx` |
| **Files (API routes)** | `route.ts` in directory | `src/app/api/click/route.ts`, `src/app/api/admin/campaigns/route.ts` |
| **Directories** | `kebab-case` | `tds/`, `bot-detection/`, `admin-auth/` |
| **Functions** | `camelCase` | `processClick()`, `generateUniqueClickId()`, `checkAuth()` |
| **Interfaces/Types** | `PascalCase` | `ClickRequest`, `ClickResult`, `FilterInterface`, `RawClick` |
| **Classes** | `PascalCase` | `CountryFilter`, `BrowserFilter`, `FilterRegistry` |
| **Type aliases** | `PascalCase` | `ActionType`, `FilterMode` |
| **Constants** | `UPPER_SNAKE_CASE` (rare) | `TIMESTAMP_MASK`, `ADMIN_API_KEY` |
| **Enum-like strings** | `'lowercase_snake'` or `'kebab-case'` in unions | `'http302'`, `'do_nothing'`, `'to_campaign'`, `'device_type'` |
| **Module-scoped vars** | `camelCase` | `filterRegistry`, `globalForPrisma` |
| **Database model fields** | `camelCase` | `campaignId`, `pubId`, `isBot` |

## Code Style

### ESLint
- Config: `eslint.config.mjs` (ESLint 9 flat config)
- Base: `eslint-config-next/core-web-vitals` + `eslint-config-next/typescript`
- Enforcement: **Extremely lax** — nearly all strict rules are disabled:
  - `@typescript-eslint/no-explicit-any`: **off**
  - `@typescript-eslint/no-unused-vars`: **off**
  - `no-console`: **off**
  - `prefer-const`: **off**
  - `react-hooks/exhaustive-deps`: **off**
  - `no-empty`, `no-debugger`, `no-fallthrough`: **off**
- **No Prettier** — no prettier config file, no `prettier` in dependencies
- **No formatter** — relies on developer discipline; no auto-formatting enforced

### TypeScript
- `strict: true` enabled in `tsconfig.json`
- `noImplicitAny: false` (explicit override allowing implicit any)
- Target: `ES2017`, Module: `esnext`, ModuleResolution: `bundler`
- Path alias: `@/*` → `./src/*`

### Indentation
- Mixed: some files use 2-space indent, others use tabs (no consistent enforcement)

## Import Organization

### Alias Usage
- `@/lib/*` used extensively for library imports (82+ occurrences across source)
- Examples: `@/lib/db`, `@/lib/tds`, `@/lib/auth`, `@/lib/utils`

### Grouping Pattern (observed but not enforced)
```
// 1. External/Node modules
import { NextRequest, NextResponse } from 'next/server';
import { PrismaClient } from '@prisma/client';
import { randomBytes } from 'crypto';

// 2. Internal aliases
import { db } from '@/lib/db';
import { processClick } from '@/lib/tds';
import { checkAuth } from '@/lib/auth';
import { cn } from '@/lib/utils';

// 3. Relative imports (within same module)
import { detectBot, shouldCloak, type DetectionContext } from './bot-detection';
import type { RawClick } from '../pipeline/types';
import { LimitFilter } from './limit';
```

### Type Imports
- `import type` used for type-only imports: `import type { RawClick } from '../pipeline/types'`
- Inline type imports: `import { detectBot, type DetectionContext, type BotDetectionResult } from './bot-detection'`
- Barrel re-exports with types: `export { ipInfoService, type IpInfoResult } from './ip-info-service'`

## Error Handling

### Pattern 1: Try/Catch in API Routes (admin CRUD)
```typescript
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  try {
    // ... business logic ...
    return NextResponse.json({ campaigns });
  } catch (error) {
    console.error('Error fetching campaigns:', error);
    return NextResponse.json({ error: 'Failed to fetch campaigns' }, { status: 500 });
  }
}
```
- Every admin CRUD route uses this exact pattern
- Auth check before try block
- `console.error` with descriptive prefix
- Generic error message in response (never exposes internal details)
- Always returns `{ error: string }` with appropriate HTTP status

### Pattern 2: Return-based errors (TDS click processing)
```typescript
export async function processClick(request: ClickRequest): Promise<ClickResult> {
  try {
    if (!request.campaignId) {
      return { success: false, error: 'INVALID_CAMPAIGN_ID' };
    }
    // ...
    return { success: true, clickId, destinationUrl };
  } catch (error) {
    console.error('Click processing error:', error);
    return { success: false, error: 'INTERNAL_ERROR' };
  }
}
```
- Error codes are UPPER_SNAKE_CASE strings: `'INVALID_CAMPAIGN_ID'`, `'ADV_INACTIVE'`, `'INTERNAL_ERROR'`
- Result type discriminates via `success: boolean`
- No exceptions thrown for business errors — all handled via return values

### Pattern 3: Early return with null (auth middleware)
```typescript
export function checkAuth(request: NextRequest): NextResponse | null {
  const authResult = verifyAdminAuth(request);
  if (!authResult.authenticated) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }
  return null; // Auth passed
}
```

### Error Response Shape
```typescript
// Admin API errors
{ error: 'Failed to fetch campaigns' }  // status 500

// Click API errors
'INVALID_CAMPAIGN_ID'  // plain text, status 200 (Keitaro compatibility)

// Auth errors
{ error: 'Unauthorized', message: string, hint?: string }  // status 401

// Validation errors
{ error: 'Campaign ID required' }  // status 400
```

## Logging

### Console Usage (88 occurrences across source)
- **No structured logger** — uses raw `console.*` exclusively
- Patterns observed:
  - `console.error('Context:', error)` — error logging in catch blocks
  - `console.warn('[ClickID] Collision detected...')` — warning with module prefix in brackets
  - `console.log(...)` — general debugging (not gated by env)
- **No log levels** — no winston, pino, or similar
- **No request correlation IDs** in logs
- Prisma configured with `log: ['query']` — all DB queries logged

## Comments

### JSDoc/TSDoc Style
- Used consistently on **exported** functions and interfaces
- Module-level block comments describe purpose and architecture
- Section separators with `// ====` dividers in larger files
- Examples:
```typescript
/**
 * Click Processing Engine
 * 
 * Main traffic distribution logic:
 * 1. Validate request parameters
 * ...
 */

/**
 * Generate a guaranteed unique click ID with collision detection
 * @param maxRetries - Maximum retry attempts (default: 3)
 * @returns Promise<string> - Unique click ID
 */
```
- Internal/private functions also documented with brief JSDoc
- Inline comments for step numbering: `// 1. Validate`, `// 2. Lookup`, etc.

## Function Design

### Parameters
- **Object parameters** for complex inputs: `processClick(request: ClickRequest)`
- **Named parameters** with inline types for simple functions: `generateUniqueClickId(maxRetries: number = 3)`
- **Optional params** with `?`: `source?: string`, `sub1?: string`
- **Union types** for status-like params: `mode: 'accept' | 'reject'`
- **Default values** sparingly: `filterOr: boolean = false`, `maxRetries: number = 3`

### Return Types
- **Explicit return types** on all exported functions: `Promise<ClickResult>`, `string | null`, `boolean`
- **Result object pattern**: `{ success: boolean; error?: string; data?: T }`
- **Null for failure** in some cases: `FilterInterface | null`

### Function Patterns
- **Pure functions** where possible (filters, ID generators)
- **Async/await** throughout — no callback patterns
- **Higher-order functions**: `withAdminAuth<T>()`, `createAuthenticatedRoute<T>()`
- **Class-based**: Filter implementations use class with `implements FilterInterface`
- **Singleton**: `filterRegistry` exported as module-level instance

## Module Design

### Barrel Exports (index.ts)
Present in all TDS subdirectories — 8 barrel files found:

| Barrel File | Re-exports |
|-------------|-----------|
| `src/lib/tds/index.ts` | click-id, bot-detection, click-processor |
| `src/lib/tds/filters/index.ts` | All filter classes, registry, types |
| `src/lib/tds/services/index.ts` | All services with types |
| `src/lib/tds/data/index.ts` | Data dictionaries |
| `src/lib/tds/utils/index.ts` | Utility functions |
| `src/lib/tds/contexts/index.ts` | Context builders |
| `src/lib/tds/actions/index.ts` | Action handlers |
| `src/lib/tds/macros/index.ts` | Macro system |
| `src/lib/auth/index.ts` | Auth functions + AuthResult type |

### Barrel Export Style
```typescript
// Named re-exports with types
export { ipInfoService, type IpInfoResult } from './ip-info-service';
export { proxyService, type ProxyDetectionResult } from './proxy-service';

// Or wildcard re-exports
export * from './click-id';
export * from './bot-detection';
```

### Database Client
- Singleton pattern via `src/lib/db.ts`
- Global caching to survive HMR in dev: `globalForPrisma.prisma`
- Query logging enabled: `log: ['query']`

### UI Component Pattern
- All in `src/components/ui/` (shadcn/ui generated)
- Use `class-variance-authority` (cva) for variant management
- `cn()` utility (clsx + tailwind-merge) for class merging
- Forward props via spread: `{...props}`
- Named exports: `export { Button, buttonVariants }`

## UI Conventions

### Theme
- **Dark theme** with `darkMode: "class"` in Tailwind config
- CSS variables for semantic colors via HSL: `hsl(var(--background))`, `hsl(var(--primary))`
- shadcn/ui New York variant

### Color Palette
- Backgrounds: `bg-slate-800/50`, `bg-slate-900`
- Borders: `border-slate-700`
- Accents: emerald (from CLAUDE.md convention)
- Destructive: red (via CSS variable)
- All colors defined as HSL CSS variables for theming

### Layout Patterns
- Cards: `bg-slate-800/50 border-slate-700`
- Tables: `max-h-96 overflow-y-auto` with sticky headers
- Footer: sticky with `mt-auto`
- Mobile-first responsive: `sm:`, `md:`, `lg:` breakpoints

### Component Usage
- **Always use shadcn/ui** — never build custom from scratch
- Radix UI primitives under the hood
- `class-variance-authority` for variant-based components
- `lucide-react` for icons
- `sonner` for toasts

## API Route Conventions

### Structure
- All routes in `src/app/api/` using Next.js App Router
- Named exports: `GET`, `POST`, `PUT`, `DELETE`
- Single file per endpoint: `src/app/api/[resource]/route.ts`

### Auth Pattern
```typescript
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  // ...
}
```

### Response Pattern
- Success: `NextResponse.json({ resource })`
- Error: `NextResponse.json({ error: string }, { status: number })`
- Redirect: `NextResponse.redirect(url, 302)`

### Query Parameter Extraction
```typescript
const { searchParams } = new URL(request.url);
const status = searchParams.get('status');
```
