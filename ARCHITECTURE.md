# SkyPlix TDS — Architecture Overview

SkyPlix is a high-performance Traffic Distribution System (TDS) written in Go. It is designed to replace Keitaro PHP with superior performance (p99 < 5ms) and modern observability.

## Key Components

### 1. The Click Hot Path (Pipeline)

Every click request flows through a linear, non-blocking pipeline of 23 stages:

- **Stage 1-5**: Request normalization, bot detection, and campaign lookup.
- **Stage 6-8**: GeoIP lookup, UA parsing, and uniqueness checks.
- **Stage 9-14**: 3-tier stream selection (Forced → Regular → Default), landing, and offer selection.
- **Stage 15-18**: Hit limit updates, cost calculation, and payout estimation.
- **Stage 19-23**: Token generation, cookie setting, and action execution (302/meta/JS).

### 2. The Ingestion Engine (Async Batching)

Clicks and conversions are buffered in memory (using internal Go channels) and flushed in batches to ClickHouse every 2 seconds or when the batch size (5000 records) is reached. This ensures that slow ClickHouse writes never block the tracking hot path.

### 3. Data Flow Architecture

- **PostgreSQL**: Stores persistent entities (Campaigns, Offers, Streams, Users).
- **Valkey (L1/L2 Cache)**:
  - **L1 (Campaign/Stream Cache)**: O(1) in-memory lookup of routing entities.
  - **L2 (Session Cache)**: Stores visitor uniqueness, hit limits, and click-to-conversion attribution.
- **ClickHouse**: Optimized for OLAP queries, storing immutable click and conversion events for real-time reporting.

## Scaling Strategy

- **Stateless App Servers**: SkyPlix nodes can be scaled horizontally without session stickiness.
- **Zero-Allocation Hot Path**: Uses `sync.Pool` for `pipeline.Payload` objects to minimize GC pressure under high RPS (50k+).
- **ClickHouse Materialized Views**: Pre-roll up stats by hour and day to provide sub-second dashboard performance.

## Security

- **JWT Auth**: RS256 asymmetric signatures for Admin API access.
- **API Key Auth**: SHA-256 hashed keys for automated access.
- **RBAC**: Workspace-level tenant isolation enforced at the database layer.
