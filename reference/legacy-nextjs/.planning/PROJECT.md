# zai-yt-keitaro

## What This Is

`zai-yt-keitaro` is a Next.js/TypeScript port of a Keitaro-style traffic distribution system. It aims to preserve the original PHP product's traffic-routing and admin backend behavior while moving the implementation onto a modern App Router, Prisma, and SQLite stack.

## Core Value

Traffic must route correctly and operators must have reliable, secure backend controls for that routing engine.

## Requirements

### Validated

- ✓ Core traffic-engine stabilization shipped through Phases 1-5 in the legacy `.gsd` workflow.
- ✓ Security, auth, and validation hardening shipped in Phase 6.
- ✓ A substantial admin CRUD/reporting surface shipped in Phase 7.

### Active

- [ ] Close the remaining backend parity gaps against the original Keitaro PHP source, especially missing dispatcher surfaces and partial controller contracts.
- [ ] Build the real admin/dashboard UI on top of the stabilized backend surface.
- [ ] Use `.planning/` as the single GSD source of truth instead of the split `.planning` + `.gsd` state.

### Out of Scope

- Keitaro-adjacent platform extras such as branding, self-update, cleaner, and similar ops modules until direct traffic/admin parity needs them.
- A fresh greenfield re-initialization of the project state, because meaningful brownfield history already exists in `.gsd/`.
- New product capabilities that are neither required for Keitaro parity nor required for the immediate UI milestone.

## Context

The repo contains a real but split planning history: older execution state survives in `.gsd/`, while current GSD tooling reads `.planning/`. A direct source-level audit against `reference/Keitaro_source_php/` showed that the current backend is substantial but not fully parity-complete, so a backend parity closure phase is required before the UI phase.

## Constraints

- **Tech stack**: Next.js App Router + TypeScript + Prisma + SQLite — the port must fit the existing runtime and repo conventions.
- **Runtime**: Port 3000 only — the sandbox routing expects the app on port 3000.
- **Backend shape**: API routes only — no server actions for backend behavior in this environment.
- **Security**: Admin routes must stay header/cookie-auth protected and avoid query-string auth regressions.
- **Environment**: `bun run build` is not part of the normal workflow here; linting and direct route verification are the safe checks.
- **Parity source**: The source of truth for backend parity is the original PHP tree in `reference/Keitaro_source_php/`, not repo docs or stale reports.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| `.planning/` is the canonical GSD workspace | Current GSD commands read `.planning/`, while `.gsd/` is legacy context only | — Pending |
| Insert a backend parity closure phase before the UI phase | Direct source audit showed the backend is still incomplete vs Keitaro | — Pending |
| Judge parity by live source behavior, not naming or documentation | Same-name endpoints were proven to be only partial matches in several areas | ✓ Good |
| Prioritize traffic/admin parity over peripheral system modules | Traffic routing and admin control are the product core value | — Pending |

---
*Last updated: 2026-04-01 after direct backend parity audit and planning-state repair*
