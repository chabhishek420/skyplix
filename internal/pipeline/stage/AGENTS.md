<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-06 | Updated: 2026-04-06 -->

# stage

## Purpose
Individual pipeline stages - reusable processing steps in the click/event pipeline. Stages are numbered to enforce execution order.

## Pipeline Architecture

### Level 1 (Pre-click, ~15 stages)
Early processing: IP normalization, campaign lookup, parameter handling

### Level 2 (Post-click, ~13 stages)
Action execution: token generation, uniqueness, cookies, action dispatch

## Key Files

### Initialization
| File | Stage | Purpose |
|------|-------|---------|
| `0_normalize_ip.go` | 0 | IPv6 normalization, 16-byte form required |
| `1_domain_redirect.go` | 1 | Domain-based redirects |

### Campaign Selection
| File | Stage | Purpose |
|------|-------|---------|
| `2_check_prefetch.go` | 2 | Prefetch detection |
| `3_build_raw_click.go` | 3 | Raw click data construction |
| `4_find_campaign.go` | 4 | Campaign lookup |
| `5_check_default_campaign.go` | 5 | Fallback to default campaign |

### Parameter Handling
| File | Stage | Purpose |
|------|-------|---------|
| `6_update_raw_click.go` | 6 | Update click with params |
| `7_check_param_aliases.go` | 7 | Parameter alias resolution |

### Stream/Offer Selection
| File | Stage | Purpose |
|------|-------|---------|
| `8_update_global_uniqueness.go` | 8 | Global uniqueness check |
| `9_choose_stream.go` | 9 | Stream selection |
| `10_update_stream_uniqueness.go` | 10 | Stream-level uniqueness |
| `11_choose_landing.go` | 11 | Landing page selection |
| `12_choose_offer.go` | 12 | Offer selection |
| `12_save_lp_token.go` | 12 | Save LP token |

### Token & Network
| File | Stage | Purpose |
|------|-------|---------|
| `13_generate_token.go` | 13 | Generate tracking token |
| `13_generate_token_test.go` | 13 | Token generation tests |
| `14_find_affiliate_network.go` | 14 | Affiliate network lookup |

### Limits & Costs
| File | Stage | Purpose |
|------|-------|---------|
| `15_update_hit_limit.go` | 15 | Hit limit enforcement |
| `16_update_costs.go` | 16 | Cost calculation |
| `17_update_payout.go` | 17 | Payout calculation |

### Session & Cookies
| File | Stage | Purpose |
|------|-------|---------|
| `18_save_uniqueness_session.go` | 18 | Session uniqueness |
| `19_set_cookie.go` | 19 | Cookie setting |

### Final Processing
| File | Stage | Purpose |
|------|-------|---------|
| `20_execute_action.go` | 20 | Execute redirect/proxy/content |
| `21_prepare.go` | 21 | Final response preparation |
| `22_checks.go` | 22 | Final validation checks |
| `23_store_raw_clicks.go` | 23 | Store to ClickHouse |

### Utilities
| File | Purpose |
|------|---------|
| `identify_visitor.go` | Visitor identification |
| `update_params.go` | Parameter updates |
| `l2_find_campaign.go` | Level 2 campaign lookup |
| `noop.go` | No-op stage |

## Anti-Patterns (THIS PACKAGE)

- Never block click response when channel full — log warning only (stage 23)
- Always use 16-byte IPv6 form for IPv4-mapped addresses (stage 0)
- Never send empty Location header — Safari retries loop

## For AI Agents

### Working In This Directory
- Stages execute in numerical order
- Each stage receives context from previous stages
- Stages can short-circuit pipeline by setting response
- Use `zap.NewNop()` in tests for deterministic output

<!-- MANUAL: -->
