---
phase: 2
plan: 2
wave: 1
---

# Plan 2.2: Session, Cookie, and Uniqueness Infrastructure

## Objective
Build the visitor identity and uniqueness tracking infrastructure:
1. **Cookie manager** — read/write `_zai_vid` (visitor code) and `_zai_sess` (session) cookies, providing the persistent visitor identity across Level 1 and Level 2 pipelines.
2. **Session/uniqueness service** — Valkey-backed uniqueness tracking at campaign and stream levels, determining if a visitor is "unique" (first visit) or "returning".
3. **Hit limit service** — Valkey counter-based daily/total click caps per stream.

## Context
- .gsd/ARCHITECTURE.md — Valkey key namespaces (`sess:{visitor_code}`, `hitlimit:{stream_id}:{date}`)
- .gsd/SPEC.md — Uniqueness tracking, hit limit enforcement
- internal/model/models.go — RawClick uniqueness fields (IsUniqueGlobal, IsUniqueCampaign, IsUniqueStream)
- internal/pipeline/pipeline.go — Payload struct (needs VisitorCode field)

## Tasks

<task type="auto">
  <name>Cookie Manager</name>
  <files>
    internal/cookie/cookie.go
  </files>
  <action>
    Create `internal/cookie/cookie.go` — visitor identity cookie management.

    **Core functions:**
    ```go
    const (
        VisitorCodeCookie = "_zai_vid"
        SessionCookie     = "_zai_sess"
    )

    // GetOrCreateVisitorCode reads the _zai_vid cookie from the request.
    // If not present, generates a new UUID v4 visitor code.
    // Returns (visitorCode string, isNew bool).
    func GetOrCreateVisitorCode(r *http.Request) (string, bool)

    // SetVisitorCodeCookie writes the _zai_vid cookie on the response.
    // HttpOnly, Secure, SameSite=Lax, Path=/, MaxAge=2 years.
    func SetVisitorCodeCookie(w http.ResponseWriter, visitorCode string)

    // SetSessionCookie writes the _zai_sess cookie with a session token.
    // HttpOnly, Secure, SameSite=Lax, Path=/, MaxAge=30 minutes.
    func SetSessionCookie(w http.ResponseWriter, sessionToken string)

    // GetVisitorCode reads the _zai_vid cookie. Returns "" if not found.
    func GetVisitorCode(r *http.Request) string
    ```

    **Cookie attributes:**
    - `_zai_vid`: HttpOnly=true, Secure=true (configurable for dev), SameSite=Lax, MaxAge=63072000 (2 years), Path=/
    - `_zai_sess`: HttpOnly=true, Secure=true, SameSite=Lax, MaxAge=1800 (30 min), Path=/

    **Add `VisitorCode` field to pipeline.Payload** so stages can access the visitor identity without re-parsing cookies.

    **Important:** Visitor code must be a UUID v4 string (not the cookie value itself). This is the key used for Valkey uniqueness lookups and entity binding.
  </action>
  <verify>go build ./internal/cookie/...</verify>
  <done>cookie.go compiles, GetOrCreateVisitorCode + SetVisitorCodeCookie + SetSessionCookie exist, Payload has VisitorCode field</done>
</task>

<task type="auto">
  <name>Uniqueness + Hit Limit Services</name>
  <files>
    internal/session/session.go
    internal/hitlimit/hitlimit.go
  </files>
  <action>
    **Session/Uniqueness service (`internal/session/session.go`):**

    ```go
    type Service struct {
        vk     *redis.Client
        logger *zap.Logger
    }

    func New(vk *redis.Client, logger *zap.Logger) *Service

    // CheckCampaignUniqueness returns true if this visitor has NOT visited this campaign before.
    // Sets the uniqueness flag in Valkey if unique.
    // Key: sess:{visitor_code}:campaign:{campaign_id} — TTL 24h
    func (s *Service) CheckCampaignUniqueness(ctx context.Context, visitorCode string, campaignID uuid.UUID) (bool, error)

    // CheckStreamUniqueness returns true if this visitor has NOT visited this stream before.
    // Key: sess:{visitor_code}:stream:{stream_id} — TTL 24h
    func (s *Service) CheckStreamUniqueness(ctx context.Context, visitorCode string, streamID uuid.UUID) (bool, error)

    // SaveSession persists the current session state to Valkey.
    // Called by SaveUniquenessSession stage (stage 18).
    func (s *Service) SaveSession(ctx context.Context, visitorCode string, data map[string]string) error
    ```

    **Valkey implementation:**
    - Use `SETNX` (SetNX) for uniqueness — returns true only on first set
    - TTL 24h for campaign/stream uniqueness keys
    - Session data stored as Valkey HASH with TTL

    **Hit Limit service (`internal/hitlimit/hitlimit.go`):**

    ```go
    type Service struct {
        vk     *redis.Client
        logger *zap.Logger
    }

    func New(vk *redis.Client, logger *zap.Logger) *Service

    // Check verifies if the stream has exceeded its click cap.
    // Returns (allowed bool, currentCount int64).
    // Key: hitlimit:{stream_id}:{YYYYMMDD} — TTL 25h (auto-expire next day)
    func (s *Service) Check(ctx context.Context, streamID uuid.UUID, dailyLimit int64) (bool, int64, error)

    // Increment bumps the counter for this stream today.
    // Called after stream selection confirms the click is allowed.
    func (s *Service) Increment(ctx context.Context, streamID uuid.UUID) error

    // Reset clears all hit limit counters for a stream.
    // Called by the HitLimitReset background worker.
    func (s *Service) Reset(ctx context.Context, streamID uuid.UUID) error
    ```

    **Valkey implementation:**
    - Use `INCR` for atomic counter increment
    - Key format: `hitlimit:{stream_id}:{20260402}` with 25-hour TTL
    - `Check` does `GET` and compares to limit; `Increment` does `INCR`
    - Daily limit of 0 = unlimited (always allowed)

    **Add `DailyLimit` and `TotalLimit` fields to `model.Stream`** — needed for hit limit enforcement.
  </action>
  <verify>go build ./internal/session/... && go build ./internal/hitlimit/...</verify>
  <done>session.go + hitlimit.go compile, uniqueness checking via SETNX, hit limits via INCR, Stream model has DailyLimit/TotalLimit</done>
</task>

## Success Criteria
- [ ] `go build ./internal/cookie/...` clean
- [ ] `go build ./internal/session/...` clean
- [ ] `go build ./internal/hitlimit/...` clean
- [ ] `go build ./...` clean (full project)
- [ ] `go vet ./...` clean
- [ ] Cookie manager reads/writes _zai_vid and _zai_sess with correct attributes
- [ ] Uniqueness service uses SETNX for campaign/stream-level tracking
- [ ] Hit limit service uses INCR counters with daily TTL
