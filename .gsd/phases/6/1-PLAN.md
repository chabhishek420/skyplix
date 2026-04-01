---
phase: 6
plan: 1
wave: 1
---

# Plan 6.1: Core Auth & Config Hardening

## Objective
Remove insecure authentication channels (query params) and implement hashed session cookies to ensure the raw API key never leaves the server-side environment in an unhashed state. Clean up operational configs (Prisma, .env).

## Context
- src/lib/auth/admin-auth.ts
- src/lib/db.ts
- .env.example
- next.config.ts

## Tasks

<task type="auto">
  <name>Harden Authentication Logic</name>
  <files>src/lib/auth/admin-auth.ts</files>
  <action>
    - Remove query-parameter (`?api_key=`) authentication from `verifyAdminAuth`.
    - Update `withAdminAuth` hint to exclude query parameters.
    - Implement a simple hash for the session cookie. Use `crypto.createHash('sha256').update(ADMIN_API_KEY).digest('hex')` (need to import `crypto`).
    - Verify that `checkAuth` correctly handles the new hashed session cookie.
    - Ensure `isLocalDevelopment` is only used when `process.env.NODE_ENV !== 'production'`.
  </action>
  <verify>Check admin-auth.ts code for removal of query-param logic and implementation of hashing.</verify>
  <done>Query param auth is gone, and admin_session contains a SHA256 hash.</done>
</task>

<task type="auto">
  <name>Operational Configuration Cleanup</name>
  <files>src/lib/db.ts, .env.example, next.config.ts</files>
  <action>
    - [MODIFY] src/lib/db.ts: Change log level to `['warn', 'error']`.
    - [NEW] .env.example: Add `ADMIN_API_KEY=your-secret-key-here` with documentation.
    - [MODIFY] next.config.ts: Attempt to set `typescript.ignoreBuildErrors` to `false` and verify if it compiles (run `npm run lint` or `tsc --noEmit`).
  </action>
  <verify>Run lint/tsc to check if build errors can be enabled.</verify>
  <done>Prisma logs are quieted, .env.example exists, and next.config.ts is hardened if possible.</done>
</task>

## Success Criteria
- [ ] curl with `?api_key=` returns 401.
- [ ] admin_session cookie is a 64-char hex string (SHA256).
- [ ] No more 'Prisma Query' spam in logs.
