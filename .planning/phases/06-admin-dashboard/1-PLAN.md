---
phase: 6
plan: 1
wave: 1
autonomous: true
---

# Phase 6 Plan 1: Admin Dashboard — JWT Login Flow

<objective>
Make the Admin UI authenticate via `/api/v1/auth/login` and use JWT (`Authorization: Bearer ...`) for subsequent API calls.
</objective>

## Context
- Backend already exposes `POST /api/v1/auth/login` and protects `/api/v1/*` with JWT middleware.
- Admin UI currently uses an API-key check against `/api/v1/settings` and stores `api_key` only.
- Axios client already prefers `auth_token` and sets `Authorization: Bearer ...`, but the UI never sets `auth_token`.

## Tasks
1. Update `admin-ui` login to call `POST /auth/login` with `{ "api_key": "..." }`.
2. Store returned JWT as `localStorage.auth_token` and use it for session auth.
3. Keep X-Api-Key fallback behavior in the axios interceptor for backward compatibility.

## Success Criteria
- Admin UI logs in using the login endpoint and persists `auth_token`.
- Admin UI can load `/api/v1/settings` after login without manually setting headers.
- `go test ./...` passes.
