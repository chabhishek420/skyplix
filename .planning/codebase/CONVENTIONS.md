# Coding Conventions

**Analysis Date:** 2026-04-02

## Languages

**Primary:**
- TypeScript 5.x (SDK: `sdk/src/`)
- CommonJS JavaScript (CLI tools: `get-shit-done/bin/lib/*.cjs`)

**Secondary:**
- Shell scripting (install scripts, hooks)
- JSON (configuration, package manifests)

## TypeScript Configuration

**Config:** `sdk/tsconfig.json`

```json
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "NodeNext",
    "moduleResolution": "NodeNext",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "isolatedModules": true
  }
}
```

**Key settings:**
- Strict mode enabled — no implicit any, strict null checks
- ES2022 target for modern Node.js features
- NodeNext module resolution with `.js` extensions in imports
- Tests excluded from compilation (`exclude: ["src/**/*.test.ts"]`)

## Naming Patterns

**Files:**
- TypeScript: `kebab-case.ts` — `phase-runner.ts`, `event-stream.ts`
- CommonJS: `kebab-case.cjs` — `core.cjs`, `state.cjs`, `frontmatter.cjs`
- Tests: `*.test.ts` for unit, `*.integration.test.ts` for integration
- Config: `kebab-case.config.ts` or flat `config.json`

**Functions:**
- camelCase — `loadConfig()`, `parsePlan()`, `stateExtractField()`
- Verb prefixes for specific purposes:
  - `cmd` prefix for CLI command handlers — `cmdStateLoad()`, `cmdStatePatch()`
  - `get` prefix for getters — `getStatePath()`, `getPhaseFileStats()`
  - `is`/`has` prefix for predicates — `isGitIgnored()`, `hasSummary()`

**Variables:**
- camelCase — `tmpDir`, `phaseNumber`, `projectDir`
- snake_case for JSON output keys — `phase_number`, `current_phase`, `has_context`
- UPPERCASE for constants — `CONFIG_DEFAULTS`, `LOG_LEVEL_PRIORITY`

**Types/Interfaces:**
- PascalCase — `GSDConfig`, `PhaseRunnerDeps`, `PlanTask`
- Suffix interfaces with descriptive names — `GSDLoggerOptions`, `LogEntry`
- Type aliases for unions — `LogLevel`, `PhaseType`

## Code Style

**Formatting:**
- Tool: Not explicitly configured (no Prettier/ESLint configs found in main project)
- Manual formatting following consistent patterns:
  - Single quotes for strings
  - Trailing commas in multiline objects/arrays
  - 2-space indentation
  - Braces on same line (`if () {`)

**Linting:**
- Minimal explicit linting configuration
- Inline `eslint-disable` comments for intentional deviations
- SDK uses vitest with default settings

**Semicolons:**
- Used throughout both CJS and TS files

**Imports Organization:**

TypeScript (ESM with `.js` extension):
```typescript
// 1. Node.js built-ins
import { readFile } from 'node:fs/promises';
import { join } from 'node:path';

// 2. External packages
import { describe, it, expect } from 'vitest';

// 3. Internal packages (relative)
import { GSDLogger } from './logger.js';
import type { LogEntry } from './logger.js';
```

CommonJS:
```javascript
const fs = require('fs');
const path = require('path');
const { escapeRegex, loadConfig } = require('./core.cjs');
```

## Error Handling

**Pattern: Early return with helpful errors**
```typescript
// Good: Fail fast with descriptive message
export async function loadConfig(projectDir: string): Promise<GSDConfig> {
  const configPath = join(projectDir, '.planning', 'config.json');

  try {
    raw = await readFile(configPath, 'utf-8');
  } catch {
    // File missing — normal for new projects
    return structuredClone(CONFIG_DEFAULTS);
  }

  try {
    parsed = JSON.parse(trimmed);
  } catch (err) {
    const msg = err instanceof Error ? err.message : String(err);
    throw new Error(`Failed to parse config at ${configPath}: ${msg}`);
  }
}
```

**Pattern: Silent handling with empty catch**
```javascript
// For expected/benign errors
function safeReadFile(filePath) {
  try {
    return fs.readFileSync(filePath, 'utf-8');
  } catch {
    return null;
  }
}

// With comment for non-critical operations
try { fs.mkdirSync(path, { recursive: true }); } catch { /* ok */ }
```

**Pattern: Structured error results**
```javascript
// Return error info in output rather than throwing
function cmdStateLoad(cwd, raw) {
  // ...
  output(result); // Never throws, always returns structured result
}

function error(message) {
  fs.writeSync(2, 'Error: ' + message + '\n');
  process.exit(1);
}
```

## Logging

**Structured JSON logger (SDK):**
- Location: `sdk/src/logger.ts`
- Class: `GSDLogger`
- Levels: `debug`, `info`, `warn`, `error`
- Output: JSON to configurable `Writable` stream (default: `process.stderr`)
- Context: Supports phase, plan, sessionId injection

```typescript
const logger = new GSDLogger({
  level: 'info',
  output: process.stderr,
  phase: PhaseType.Execute,
  sessionId: 'sess-123'
});

logger.info('Phase started', { duration: 1500 });
```

**CLI output:**
- `output(result)` — writes JSON to stdout
- `error(message)` — writes to stderr, exits with code 1
- Large payloads (>50KB) written to temp file with `@file:` prefix

## Comments

**When to Comment:**
- Module/file-level JSDoc describing purpose
- Complex algorithm explanations
- Security-sensitive operations
- Non-obvious edge cases

**JSDoc Usage:**
```typescript
/**
 * Config reader — loads `.planning/config.json` and merges with defaults.
 *
 * Mirrors the default structure from `get-shit-done/bin/lib/config.cjs`
 * `buildNewProjectConfig()`.
 */
export async function loadConfig(projectDir: string): Promise<GSDConfig> {
  // ...
}

/**
 * Normalize markdown to fix common markdownlint violations.
 * Applied at write points so GSD-generated .planning/ files are IDE-friendly.
 *
 * Rules enforced:
 *   MD022 — Blank lines around headings
 *   MD031 — Blank lines around fenced code blocks
 */
function normalizeMd(content) {
  // ...
}
```

## Function Design

**Size:** Functions are kept focused (typically <50 lines), with complex logic broken into named helpers

**Parameters:**
- Max 3-4 parameters before grouping into options object
- Typed parameters in TypeScript
- Destructuring for complex objects

**Return Values:**
- Always return structured objects for complex operations
- Use `null` for "not found" scenarios
- Throw for actual errors

## Module Design

**Exports (CJS):**
```javascript
module.exports = {
  output,
  error,
  safeReadFile,
  loadConfig,
  // ...
};
```

**Exports (ESM/TS):**
```typescript
export class GSDLogger { /* ... */ }
export type { LogLevel, LogEntry };
export function loadConfig() { /* ... */ }
```

**Barrel Files:**
- SDK uses `index.ts` re-exports for public API
- CJS modules export directly without barrel files

## File Structure

```
sdk/src/
├── index.ts              # Public API re-exports
├── cli.ts                # CLI entry point
├── config.ts             # Config loading and defaults
├── logger.ts             # Structured logging
├── event-stream.ts       # Event emitter
├── phase-runner.ts       # Main orchestration
├── context-engine.ts     # Context file management
├── prompt-builder.ts     # Prompt assembly
├── plan-parser.ts        # PLAN.md parsing
├── types.ts              # Shared type definitions
├── ws-transport.ts      # WebSocket transport
└── cli-transport.ts     # CLI transport

get-shit-done/bin/lib/
├── core.cjs               # Shared utilities
├── state.cjs              # STATE.md operations
├── phase.cjs              # Phase management
├── roadmap.cjs            # ROADMAP.md operations
├── config.cjs             # Config operations
├── frontmatter.cjs         # Frontmatter parsing
├── security.cjs            # Security utilities
├── template.cjs           # Template operations
└── model-profiles.cjs      # Model configuration

tests/
├── helpers.cjs             # Test utilities
├── state.test.cjs         # State command tests
├── core.test.cjs          # Core utility tests
├── config.test.cjs        # Config tests
└── *.test.cjs             # Other command tests
```

## Security Patterns

**Path traversal prevention:**
```javascript
const { validatePath } = require('./security.cjs');
const pathCheck = validatePath(filePath, cwd, { allowAbsolute: true });
if (!pathCheck.safe) {
  throw new Error(`${label} path rejected: ${pathCheck.error}`);
}
```

**Field name validation:**
```javascript
const { validateFieldName } = require('./security.cjs');
const fieldCheck = validateFieldName(field);
if (!fieldCheck.valid) {
  error(`state patch: ${fieldCheck.error}`);
}
```

**Shell argument safety:**
- Use `execFileSync` with array args over `execSync` with string interpolation
- Validate paths before filesystem operations

---

*Convention analysis: 2026-04-02*
