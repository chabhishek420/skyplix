<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-06 | Updated: 2026-04-06 -->

# ratelimit

## Purpose
Redis-based rate limiting for API endpoints. Prevents abuse and ensures fair usage.

## Key Files

| File | Purpose |
|------|---------|
| `ratelimit.go` | Rate limiting implementation |

## Algorithm

Sliding window rate limiting using Redis:
- Key: `ratelimit:{endpoint}:{ip}`
- TTL: window duration
- INCR for each request

## Configuration

Rate limits configured per endpoint:
- Admin API: configurable per-route
- Tracking endpoints: campaign-specific limits

## For AI Agents

### Working In This Directory
- Uses Valkey/Redis for distributed rate limiting
- Sliding window algorithm
- Returns 429 Too Many Requests when exceeded

<!-- MANUAL: -->
