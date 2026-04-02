# Testing Patterns

**Analysis Date:** 2026-04-02

## Test Framework

**SDK (TypeScript):**
- Framework: Vitest 4.x
- Config: `sdk/vitest.config.ts` (root), `sdk/src/vitest.config.ts`
- Assertion: Vitest built-in (`expect`)

**CLI Tools (CommonJS):**
- Framework: Node.js built-in `node:test` module
- Assertion: Node.js built-in `assert` module
- Test runner: `node scripts/run-tests.cjs`

## Test File Organization

**Location:**
- SDK tests: Co-located with source — `sdk/src/*.test.ts`, `sdk/src/*.integration.test.ts`
- CLI tests: Centralized — `tests/*.test.cjs`

**Naming:**
- Unit tests: `*.test.ts` or `*.test.cjs`
- Integration tests: `*.integration.test.ts`
- Test helpers: `tests/helpers.cjs`

**Structure:**
```
sdk/src/
├── phase-runner.ts
├── phase-runner.test.ts        # Unit tests
└── phase-runner.integration.test.ts  # Integration tests

tests/
├── helpers.cjs                 # Shared test utilities
├── state.test.cjs              # State command tests
├── core.test.cjs               # Core utility tests
└── ...
```

## Test Run Commands

**SDK (TypeScript):**
```bash
npm run test              # Run all tests (via vitest)
npm run test:unit         # Unit tests only (vitest --project unit)
npm run test:integration  # Integration tests only (vitest --project integration)
```

**CLI Tools (CommonJS):**
```bash
npm test                  # Runs node scripts/run-tests.cjs
npm run test:coverage     # Coverage with c8 (70% line threshold)
```

**Root GSD Source:**
```bash
cd .gsd-source
npm test                  # Runs scripts/run-tests.cjs
```

## Test Structure

**Vitest (SDK):**
```typescript
import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { loadConfig, CONFIG_DEFAULTS } from './config.js';
import { mkdir, writeFile, rm } from 'node:fs/promises';

describe('loadConfig', () => {
  let tmpDir: string;

  beforeEach(async () => {
    tmpDir = join(tmpdir(), `gsd-config-test-${Date.now()}`);
    await mkdir(join(tmpDir, '.planning'), { recursive: true });
  });

  afterEach(async () => {
    await rm(tmpDir, { recursive: true, force: true });
  });

  it('returns all defaults when config file is missing', async () => {
    const config = await loadConfig(tmpDir);
    expect(config).toEqual(CONFIG_DEFAULTS);
  });
});
```

**Node Test (CLI):**
```javascript
const { test, describe, beforeEach, afterEach } = require('node:test');
const assert = require('node:assert');
const fs = require('fs');
const { runGsdTools, createTempProject, cleanup } = require('./helpers.cjs');

describe('state-snapshot command', () => {
  let tmpDir;

  beforeEach(() => {
    tmpDir = createTempProject();
  });

  afterEach(() => {
    cleanup(tmpDir);
  });

  test('missing STATE.md returns error', () => {
    const result = runGsdTools('state-snapshot', tmpDir);
    assert.ok(result.success);
    const output = JSON.parse(result.output);
    assert.strictEqual(output.error, 'STATE.md not found');
  });
});
```

## Test Helpers

**Location:** `tests/helpers.cjs`

```javascript
/**
 * Run gsd-tools command.
 * @param {string|string[]} args - Command string or array of arguments
 * @param {string} cwd - Working directory
 * @param {object} [env] - Optional env overrides
 */
function runGsdTools(args, cwd = process.cwd(), env = {}) {
  // Array args: safe for JSON and dollar signs
  // String args: shell-interpreted
}

function createTempDir(prefix = 'gsd-test-') { /* ... */ }
function createTempProject(prefix = 'gsd-test-') { /* ... */ }
function createTempGitProject(prefix = 'gsd-test-') { /* ... */ }
function cleanup(tmpDir) { /* ... */ }
```

**Usage in tests:**
```javascript
const { runGsdTools, createTempProject, cleanup } = require('./helpers.cjs');

test('my test', () => {
  const tmpDir = createTempProject();
  // ... test code ...
  cleanup(tmpDir);
});
```

## Mocking

**Framework:** Vitest built-in mocking

**Mock modules:**
```typescript
import { vi, describe, it, expect } from 'vitest';
import { GSDLogger } from './logger.js';
import { Writable } from 'node:stream';

// Mock custom stream for output capture
class BufferStream extends Writable {
  lines: string[] = [];
  _write(chunk, _encoding, callback) {
    const str = chunk.toString();
    this.lines.push(...str.split('\n').filter(l => l.length > 0));
    callback();
  }
}

describe('GSDLogger', () => {
  it('captures output', () => {
    const output = new BufferStream();
    const logger = new GSDLogger({ output, level: 'debug' });
    logger.info('test message');
    expect(output.lines).toHaveLength(1);
  });
});
```

**Mock fs operations (temp directory pattern):**
```typescript
import { mkdtemp, mkdir, writeFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';

beforeEach(async () => {
  tmpDir = await mkdtemp(join(tmpdir(), 'gsd-test-'));
  await mkdir(join(tmpDir, '.planning'), { recursive: true });
});

afterEach(async () => {
  await rm(tmpDir, { recursive: true, force: true });
});
```

## Fixtures and Factories

**Inline fixtures for plan parsing:**
```typescript
const FULL_PLAN = `---
phase: 03-features
plan: 01
type: execute
wave: 2
depends_on: [01-01, 01-02]
files_modified: [src/models/user.ts]
autonomous: true
requirements: [R001, R003]
must_haves:
  truths:
    - "User can see existing messages"
---

<objective>
Implement complete User feature.
</objective>

<tasks>
<task type="auto">
  <name>Task 1: Create User model</name>
  <files>src/models/user.ts</files>
  <action>Define User type...</action>
  <verify>tsc --noEmit passes</verify>
</task>
</tasks>
`;

it('parses full plan frontmatter', () => {
  const result = parsePlan(FULL_PLAN);
  expect(result.frontmatter.phase).toBe('03-features');
});
```

**Temp project factory:**
```javascript
function createTempProject(prefix = 'gsd-test-') {
  const tmpDir = fs.mkdtempSync(path.join(require('os').tmpdir(), prefix));
  fs.mkdirSync(path.join(tmpDir, '.planning', 'phases'), { recursive: true });
  return tmpDir;
}
```

## Coverage

**Requirements:** 70% line coverage enforced for SDK

**View Coverage:**
```bash
cd .gsd-source/sdk
npm run test:coverage
```

**Coverage config (SDK package.json):**
```json
{
  "scripts": {
    "test:coverage": "c8 --check-coverage --lines 70 --reporter text --include 'get-shit-done/bin/lib/*.cjs' --exclude 'tests/**' --all node scripts/run-tests.cjs"
  }
}
```

## Test Types

**Unit Tests:**
- Location: `sdk/src/*.test.ts`
- Scope: Single function/class/module
- Isolation: Mock dependencies, use temp directories
- Examples: `config.test.ts`, `logger.test.ts`, `plan-parser.test.ts`

**Integration Tests:**
- Location: `sdk/src/*.integration.test.ts`
- Scope: Multi-component workflows, real filesystem operations
- Integration with: Actual `gsd-tools.cjs` CLI
- Examples: `phase-runner.integration.test.ts`, `lifecycle-e2e.integration.test.ts`

**CLI Command Tests:**
- Location: `tests/*.test.cjs`
- Scope: Command-line interface end-to-end
- Method: Spawn actual CLI process, inspect stdout/stderr
- Examples: `state.test.cjs`, `core.test.cjs`, `roadmap.test.cjs`

## Common Patterns

**Async Testing (Vitest):**
```typescript
it('loads valid config', async () => {
  await writeFile(configPath, JSON.stringify({ model_profile: 'fast' }));
  const config = await loadConfig(tmpDir);
  expect(config.model_profile).toBe('fast');
});
```

**Error Testing:**
```typescript
it('throws on malformed JSON', async () => {
  await writeFile(configPath, '{bad json');
  await expect(loadConfig(tmpDir)).rejects.toThrow(/Failed to parse config/);
});

it('returns error in output', () => {
  const result = runGsdTools('state-snapshot', tmpDir);
  assert.ok(!result.success);
  assert.ok(result.error.includes('STATE.md'));
});
```

**Snapshot Testing (JSON output):**
```typescript
it('captures structured log entries', () => {
  const output = new BufferStream();
  const logger = new GSDLogger({ output, level: 'debug' });
  logger.info('test message', { count: 42 });

  const entry = JSON.parse(output.lines[0]);
  expect(entry).toMatchObject({
    level: 'info',
    message: 'test message',
    data: { count: 42 }
  });
});
```

**Round-trip Testing:**
```typescript
it('round-trip: write then read', () => {
  runGsdTools('state update Status "In progress"', tmpDir);
  const result = runGsdTools('state json', tmpDir);
  const output = JSON.parse(result.output);
  expect(output.status).toBe('in progress');
});
```

**Negative Testing:**
```typescript
it('preserves unknown keys without error', async () => {
  const userConfig = { totally_unknown: true };
  await writeFile(configPath, JSON.stringify(userConfig));
  const config = await loadConfig(tmpDir);
  expect((config as Record<string, unknown>).totally_unknown).toBe(true);
});
```

**File System State Testing:**
```typescript
it('adds frontmatter to STATE.md', () => {
  runGsdTools('state update Status "Executing"', tmpDir);
  const content = fs.readFileSync(statePath, 'utf-8');
  assert.ok(content.startsWith('---\n'));
  assert.ok(content.includes('gsd_state_version: 1.0'));
});
```

## Test Organization

**Describe blocks:**
```typescript
describe('loadConfig', () => { /* tests */ });
describe('GSDLogger', () => { /* tests */ });
describe('Integration: PhaseRunner', () => { /* integration tests */ });
```

**Naming tests:**
- Use sentence case: `'returns all defaults when config file is missing'`
- Focus on behavior, not implementation
- Include expected outcome: `'should report missing file'`

## Integration Test Patterns

**Setup real CLI:**
```typescript
const GSD_TOOLS_PATH = join(homedir(), '.claude', 'get-shit-done', 'bin', 'gsd-tools.cjs');

beforeAll(async () => {
  tools = new GSDTools({
    projectDir: tmpDir,
    gsdToolsPath: GSD_TOOLS_PATH,
    timeoutMs: 10_000,
  });
});
```

**Event emission testing:**
```typescript
const events = [];
eventStream.on('event', (e) => events.push(e));

await runner.run('01');

const phaseStartEvents = events.filter(e => e.type === GSDEventType.PhaseStart);
expect(phaseStartEvents).toHaveLength(1);
```

**Timeout handling:**
```typescript
it('handles long operations', { timeout: 300_000 }, async () => {
  // Long-running test with custom timeout
});
```

---

*Testing analysis: 2026-04-02*
