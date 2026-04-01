# STATE.md — Project State

> **Last Updated**: 2026-04-01
> **Current Phase**: Not started
> **Active Task**: None

## Context

- Keitaro PHP source (v9.13.9) fully analyzed — 1,705 PHP files, 82K LOC
- 22-stage pipeline architecture mapped from `Traffic/Pipeline/Stage/`
- 51 component modules cataloged from `Component/`
- YellowCloaker bot detection (12 checks) analyzed
- yljary.com live TDS verified via terminal (infrastructure confirmed active)
- Existing Next.js implementation (304 TS files) to be superseded by Go rewrite
- Reference material preserved in `reference/` directory

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Language | Go | Single binary, goroutine-per-click, 100K+ req/s, ad-tech standard |
| Primary DB | PostgreSQL | Relational integrity for campaigns/streams, proven at scale |
| Cache | Redis | Sub-ms session/config reads, click dedup, rate limiting |
| Analytics | ClickHouse | Columnar storage for billions of clicks, sub-second aggregation |
| Admin UI | React + shadcn/ui | Team familiarity, embedded in Go binary |
| License | MIT | Open source, no restrictions |
