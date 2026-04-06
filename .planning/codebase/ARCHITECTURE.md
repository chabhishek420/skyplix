# Architecture

**Analysis Date:** 2026-04-06

## Overview

This codebase is a Go-based traffic distribution and analytics system with a separate React admin UI. The backend owns request routing, click pipeline execution, async analytics writes, admin APIs, and observability. The frontend is embedded and served from the same backend process under `/admin`.

## High-Level Layers

1. Entry and bootstrap
- CLI entrypoint initializes config, logger, and server (`cmd/zai-tds/main.go`).
- `server.New(...)` wires all dependencies and pipelines (`internal/server/server.go`).

2. HTTP/API layer
- Chi router and middlewares in `internal/server/routes.go`.
- Public endpoints: health, readiness, click routes, postbacks, login, metrics.
- Protected endpoints: admin CRUD/reporting under `/api/v1/*`.

3. Domain/service layer
- Core services live under `internal/*` by domain (cache, filter, geo, ratelimit, session, attribution, etc.).
- Admin handlers delegate to repository abstractions (`internal/admin/handler`, `internal/admin/repository`).

4. Pipeline execution layer
- Generic pipeline engine in `internal/pipeline/pipeline.go`.
- Stage implementations in `internal/pipeline/stage/*.go`.
- Two assembled pipelines in `internal/server/server.go`:
  - L1 (full campaign flow)
  - L2 (landing token to offer flow)

5. Data layer
- PostgreSQL for core entities via pgx.
- Valkey for cache/session/limits and bot state.
- ClickHouse for click/conversion analytics ingestion and reporting reads.

## Request/Data Flow (Click Path)

1. `GET /{alias}` or `GET /` enters `handleClick` (`internal/server/routes.go`).
2. Payload object is pooled and initialized (`internal/pipeline/pipeline.go`).
3. Ordered stages enrich data, resolve campaign/stream, apply filters, select destination, and execute action (`internal/pipeline/stage/*.go`).
4. `StoreRawClicksStage` sends records to async queue channels consumed by ClickHouse writer (`internal/queue/writer.go`).
5. Metrics and structured logs are emitted (`internal/metrics/metrics.go`, `internal/server/routes.go`).

## Concurrency Model

- HTTP server runs with graceful shutdown orchestration (`internal/server/server.go`).
- ClickHouse writer runs in background goroutine and drains channels until context cancel.
- Background workers (cache warmup, session janitor, hit-limit reset) run under manager control (`internal/worker/*.go`).
- Pipeline payloads and raw click structs are reused with `sync.Pool` (`internal/pipeline/pipeline.go`).

## Architectural Characteristics

- Dependency injection is constructor-based from `server.New(...)`.
- Stage-based pipeline isolates click decision logic into composable units.
- Service boundaries are package-based under `internal/` (one package per concern).
- Admin APIs are resource-oriented and grouped by entity (`/campaigns`, `/streams`, etc.).
