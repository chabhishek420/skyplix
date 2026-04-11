<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-06 | Updated: 2026-04-06 -->

# handler

## Purpose
HTTP handlers for admin API endpoints. Provides REST API for managing campaigns, offers, streams, and viewing reports.

## Key Files

| File | Purpose |
|------|---------|
| `handler.go` | Main router setup, middleware |
| `campaigns.go` | Campaign CRUD operations |
| `offers.go` | Offer CRUD operations |
| `streams.go` | Stream CRUD operations |
| `landings.go` | Landing page management |
| `domains.go` | Domain management |
| `networks.go` | Affiliate network CRUD |
| `sources.go` | Traffic source management |
| `users.go` | User management |
| `settings.go` | System settings |
| `bots.go` | Bot detection rules |
| `reports.go` | Analytics reports |
| `postback.go` | Server-to-server postback |
| `postback_test.go` | Postback tests |
| `helpers.go` | Shared handler utilities |

## API Structure

### RESTful Endpoints
```
/api/campaigns/*     - Campaign management
/api/offers/*       - Offer management
/api/streams/*      - Stream management
/api/landings/*     - Landing pages
/api/domains/*      - Domain management
/api/networks/*     - Affiliate networks
/api/sources/*      - Traffic sources
/api/users/*        - User management
/api/reports/*      - Analytics data
/api/postback       - Postback endpoint
```

## Response Format

All handlers return JSON:
```json
{
  "data": {...},
  "error": null
}
```

## For AI Agents

### Working In This Directory
- Chi router for HTTP routing
- JSON response helpers in `helpers.go`
- PostgreSQL queries via repository layer
- Use `zap.NewNop()` in tests

### Error Handling
- Return appropriate HTTP status codes
- Wrap errors with context: `fmt.Errorf("handler: %w", err)`

<!-- MANUAL: -->
