# Phase 3: Actions & Landers

## Status: 🔲 NOT STARTED

## Goal
Support landing pages, offers, and various redirection methods.

## Verified Implementation

### What Exists (Verified by Code Inspection)

| Component | File | Status |
|-----------|------|--------|
| **Action Interface** | `internal/action/action.go` | ✅ Base interface defined |
| **Redirect Action** | `internal/action/redirect.go` | ✅ 302/Meta/JS redirects |
| **Proxy Action** | `internal/action/proxy.go` | ✅ CURL-based proxying |
| **Content Action** | `internal/action/content.go` | ✅ Direct content serving |
| **Special Actions** | `internal/action/special.go` | ✅ Non-standard actions |
| **Macro Expansion** | `internal/macro/macro.go` | ✅ 40+ macros defined |
| **Landing Selection** | `internal/pipeline/stage/11_choose_landing.go` | ✅ Weight-based rotation |
| **Offer Selection** | `internal/pipeline/stage/12_choose_offer.go` | ✅ Weight-based rotation |
| **Action Execution** | `internal/pipeline/stage/20_execute_action.go` | ✅ Executes selected action |

### Macro Support (Verified)
Available macros in `internal/macro/macro.go`:
- `{click_id}`, `{clickid}`, `{click_id_prefix}`
- `{sub1}` through `{sub15}`
- `{source}`, `{campaign_id}`, `{campaign_name}`
- `{stream_id}`, `{landing_id}`, `{offer_id}`
- `{geo_country}`, `{geo_region}`, `{geo_city}`
- `{device_os}`, `{device_browser}`, `{device_type}`
- `{ip}`, `{ip_encoded}`, `{user_agent}`
- And 25+ more...

## Requirements to Implement
- [ ] FEAT-05: Rotation engine (appears to exist, verify weights)
- [x] FEAT-06: Action handlers (redirect, proxy, content - ✅)
- [x] FEAT-07: Macro substitution (40+ macros - ✅)

## Success Criteria
- [ ] Support 302, Meta-refresh, JS-based redirects
- [ ] Rotation between landers/offers using weights
- [ ] Dynamic macros expanded in destination URLs

## Dependencies
- Phase 2 (Campaign Engine)
