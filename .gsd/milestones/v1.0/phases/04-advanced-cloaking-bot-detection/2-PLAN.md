---
phase: 4
plan: 2
wave: 1
---

# Plan 4.2: Valkey Persistence + Pipeline Integration + Admin API

## Objective
Wire the `botdb.Store` into the live system: persist IP ranges to Valkey (so they survive restarts), integrate as check #4 in `BuildRawClickStage`, and expose admin CRUD endpoints for managing bot IPs. Also expand UA signatures from 43 → 54+ by porting the full Keitaro `UserBotListService` list.

## Context
- `internal/botdb/store.go` — Created in Plan 4.1
- `internal/pipeline/stage/3_build_raw_click.go` — Current bot detection (to be enhanced)
- `internal/server/server.go` — Server wiring (needs botdb.Store injection)
- `internal/admin/handler/` — Admin API handlers (new bot handler needed)
- `internal/cache/cache.go` — Valkey client patterns
- `reference/Keitaro_source_php/application/Component/BotDetection/Service/UserBotListService.php` — Full 54 UA signature list

## Tasks

<task type="auto">
  <name>Add Valkey persistence to botdb.Store + load on startup</name>
  <files>internal/botdb/valkey.go, internal/botdb/store.go</files>
  <action>
    1. Create `internal/botdb/valkey.go` with:
       - `ValkeyStore struct` — Wraps `Store` + `*redis.Client`
       - `NewValkeyStore(client *redis.Client) *ValkeyStore` — Constructor, calls `loadFromValkey()`
       - `loadFromValkey() error` — Read key `botdb:ips` (stored as JSON array of raw strings), call `store.Replace(joined)`
       - `saveToValkey() error` — Serialize current `store.List()` raw values to JSON, `SET botdb:ips`
       - Override `Add/Exclude/Replace/Clear` to call `saveToValkey()` after mutation
       - `Contains(ip net.IP) bool` — Delegate to inner store (no Valkey on hot path!)

    2. Modify `internal/server/server.go`:
       - Add `botDB *botdb.ValkeyStore` field to `Server` struct
       - Initialize in `New()`: `s.botDB = botdb.NewValkeyStore(vk)`
       - Pass to `BuildRawClickStage` as a new field: `BotDB: s.botDB`

    **Key design decision:**
    - Hot-path reads (`Contains`) NEVER touch Valkey — pure in-memory binary search
    - Only admin mutations (Add/Exclude/Replace/Clear) persist to Valkey
    - On restart, `loadFromValkey()` restores the full list into memory
  </action>
  <verify>go build ./internal/botdb/... && go build ./internal/server/...</verify>
  <done>ValkeyStore compiles, server wires it correctly, hot-path Contains() remains in-memory</done>
</task>

<task type="auto">
  <name>Integrate into BuildRawClickStage + expand UA signatures to 54+</name>
  <files>internal/pipeline/stage/3_build_raw_click.go</files>
  <action>
    1. **Add BotDB field** to `BuildRawClickStage`:
       ```go
       type BuildRawClickStage struct {
           BotDB interface{ Contains(net.IP) bool } // accepts botdb.ValkeyStore or nil
       }
       ```
       Use interface so stage works with nil (backward compatible for tests).

    2. **Modify `detectBot()`** to accept the BotDB parameter:
       - Change signature to `detectBot(ip net.IP, ua string, botDB interface{ Contains(net.IP) bool }) bool`
       - Add check #4 after existing IP prefix check: if `botDB != nil && botDB.Contains(ip)` return true
       - Keep the hardcoded `botIPPrefixes` check (#3) as a fast fallback when botDB is nil/empty

    3. **Expand `botUAPatterns`** from 43 to 54+ entries by adding the missing Keitaro signatures:
       Add these from `UserBotListService.php` that are NOT already in the list:
       - "advisorbot", "obot", "ezooms", "flipboardproxy", "chtml proxy", "tweetmemebot",
       - "sputnikbot", "webindex", "adsbot", "/bots", "ru_bot", "orangebot",
       - "synapse", "seostats", "owler", "ltx71", "winhttprequest", "pageanalyzer",
       - "openlinkprofiler", "bot for jce", "bubing", "nutch", "megaindex",
       - "coccoc", "sleuth", "cmcm.com", "yandexmobilebot", "google-youtube-links",
       - "mailruconnect", "surveybot", "appengine", "netcraftsurveyagent",
       - "exabot-thumbnails", "bingpreview"
       Remove any duplicates. The final list should have 54+ unique entries (all lowercase).

    4. **Add user-defined UA signature support** — Add a `CustomUA interface{ Patterns() []string }` field to `BuildRawClickStage`. If non-nil, iterate custom patterns after hardcoded list. This allows admin-managed custom UA signatures stored in Valkey (wired in Plan 4.3).
  </action>
  <verify>go build ./internal/pipeline/...</verify>
  <done>BuildRawClickStage uses botdb for IP checks, UA list has 54+ patterns, custom UA interface ready</done>
</task>

<task type="auto">
  <name>Admin API endpoints for bot IP management</name>
  <files>internal/admin/handler/bots.go, internal/server/routes.go</files>
  <action>
    1. Create `internal/admin/handler/bots.go`:
       - `type BotHandler struct { BotDB *botdb.ValkeyStore; Logger *zap.Logger }`
       - `POST /api/v1/bots/ips` — Body: `{"ips": "1.2.3.4\n10.0.0.0/8"}`. Calls `BotDB.Add(body.IPs)`. Returns `{"count": N}`.
       - `DELETE /api/v1/bots/ips` — Body: `{"ips": "1.2.3.4"}`. Calls `BotDB.Exclude(body.IPs)`. Returns `{"count": N}`.
       - `PUT /api/v1/bots/ips` — Body: `{"ips": "..."}`. Calls `BotDB.Replace(body.IPs)`. Returns `{"count": N}`.
       - `GET /api/v1/bots/ips` — Returns `{"ranges": [...], "count": N}`.
       - `DELETE /api/v1/bots/ips/all` — Calls `BotDB.Clear()`. Returns `{"count": 0}`.
       - `POST /api/v1/bots/ips/check` — Body: `{"ip": "1.2.3.4"}`. Returns `{"is_bot": true/false}`. Useful for debugging.

    2. Add `BotHandler` to `handler.Handler` struct with getter.

    3. Wire routes in `internal/server/routes.go`:
       - Under the existing admin API group: `r.Route("/bots", func(r chi.Router) { ... })`
       - All endpoints require API key auth (existing middleware)

    4. Wire `BotHandler` in `server.go` — Pass `s.botDB` to handler constructor.
  </action>
  <verify>go build ./... && curl -s localhost:8080/api/v1/bots/ips (after server start)</verify>
  <done>6 bot IP admin endpoints compile and are wired, full CRUD available</done>
</task>

## Success Criteria
- [ ] `go build ./...` — full project compiles
- [ ] Bot IP ranges persist to Valkey key `botdb:ips` and reload on restart
- [ ] `BuildRawClickStage` checks botdb.Contains() as check #4
- [ ] UA pattern list expanded to 54+ entries
- [ ] Admin API: POST/DELETE/PUT/GET endpoints for bot IP management
- [ ] Hot-path `Contains()` never touches Valkey (pure in-memory)
