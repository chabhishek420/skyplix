---
phase: 5
plan: 1
subsystem: analytics
tags: [clickhouse, migrations]
provides: [stats-mvs]
tech-stack: [clickhouse]
key-files:
  - db/clickhouse/migrations/005_create_stats_materialized_views.sql
one_liner: "Added ClickHouse stats tables + materialized views for real-time aggregation."
metrics:
  completed_date: "2026-04-03"
---

# Plan 5.1 Summary: ClickHouse Materialized Views & Stats Tables

## Accomplished
- Added ClickHouse DDL for real-time stats aggregation using SummingMergeTree target tables and insert-triggered materialized views.
- Covered hourly + daily aggregation for both click stats and conversion stats, using LowCardinality dimensions for efficient storage.

## Code Changes
- [NEW] `db/clickhouse/migrations/005_create_stats_materialized_views.sql`: Stats tables (`stats_hourly`, `stats_daily`, `conv_stats_hourly`, `conv_stats_daily`) + materialized views from `clicks` and `conversions`.

## Verification Result
- SQL migration created and committed; Go build/tests unaffected.
