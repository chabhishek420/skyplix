<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-06 | Updated: 2026-04-06 -->

# botdb

## Purpose
Bot detection IP/UA store. Maintains lists of known bot IPs and user agents for filtering.

## Key Files

| File | Purpose |
|------|---------|
| `store.go` | Main bot detection store interface |
| `store_test.go` | Store tests |
| `valkey.go` | Valkey-backed bot IP storage |
| `uastore.go` | User-agent based bot detection |

## Architecture

### Valkey Store (`valkey.go`)
- Stores bot IP ranges in Redis/Valkey sets
- O(1) lookup for IP presence
- Supports bulk operations for range imports

### UA Store (`uastore.go`)
- Known bot user-agent strings
- Pattern matching for bot identification

## For AI Agents

### Working In This Directory
- Bot detection is checked early in pipeline (before campaign selection)
- IPs and UAs are checked together for confidence scoring
- Store is read-heavy, updated periodically from external feeds

### Testing
- Use `zap.NewNop()` for test logger
- Mock external bot feeds in tests

<!-- MANUAL: -->
