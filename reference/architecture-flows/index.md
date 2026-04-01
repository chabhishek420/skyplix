# Architecture Flows Index

## System Overview

**Target:** yljary.com Keitaro TDS Infrastructure
**Classification:** Security Research
**Date:** 2026-03-24
**Status:** VERIFIED (HTTP-level observations only)

---

## ⚠️ Important Notice

**These flows document VERIFIED HTTP-level behavior ONLY.**

Internal system architecture (database schema, caching, message queues, detection algorithms) is **UNKNOWN** and not documented here.

See [CRITICAL_EVALUATION.md](./CRITICAL_EVALUATION.md) for analysis of fabricated claims that were removed from these documents.

---

## System Summary

yljary.com operates a **Keitaro Traffic Distribution System (TDS)** used for affiliate traffic routing. The system processes traffic from multiple sources, performs bot detection, and routes legitimate users through affiliate tracking networks to various brand destinations.

### Key Statistics (Verified)

| Metric | Value | Verification |
|--------|-------|--------------|
| Active Campaigns | 529 | ✅ Verified via enumeration |
| Unique Brands Targeted | 457 | ✅ Verified from extracted data |
| Typosquat Domains | 4 | ✅ Verified via redirect testing |
| Affiliate IDs Active | 3 | ✅ Verified (1636, 151905, 1REQUIREFOR51) |
| Publisher IDs Active | 31 | ✅ Verified (102200-102230 range) |

---

## Flow Inventory

### Core Flows

| Flow | Description | Status | File |
|------|-------------|--------|------|
| **Click Flow** | Primary traffic entry point - HTTP behavior and redirect chain | ✅ Verified | [click-flow.md](./click-flow.md) |
| **Campaign Redirect Flow** | Campaign routing and error handling | ✅ Verified | [campaign-redirect-flow.md](./campaign-redirect-flow.md) |
| **Affiliate Tracking Flow** | Tracking parameters and cookie lifecycle | ✅ Verified | [affiliate-tracking-flow.md](./affiliate-tracking-flow.md) |
| **Postback Flow** | Conversion tracking mechanism | ⚠️ PARTIAL | [postback-flow.md](./postback-flow.md) |
| **Bot Detection Flow** | Cloaker behavior and safe page outcomes | ✅ Verified (outcomes only) | [bot-detection-flow.md](./bot-detection-flow.md) |
| **Typosquat Redirect Flow** | Typosquatting network redirect chains | ✅ Verified | [typosquat-redirect-flow.md](./typosquat-redirect-flow.md) |
| **Critical Evaluation** | Analysis of errors and corrections | ✅ Complete | [CRITICAL_EVALUATION.md](./CRITICAL_EVALUATION.md) |

---

## System Architecture (Verified Components Only)

```
┌─────────────────────────────────────────────────────────────────────┐
│                         TRAFFIC SOURCES                             │
├─────────────────────────────────────────────────────────────────────┤
│  Typosquat Domains          │  Direct Traffic      │  Ad Networks  │
│  - hostinder.com           │  - trakr.yljary.com  │  - Unknown    │
│  - hostiinger.com          │  - click.yljary.com  │               │
│  - hostinnger.com          │                       │               │
│  - hostingerr.com          │                       │               │
└─────────────┬───────────────┴──────────┬──────────┴───────────────┘
              │                          │
              ▼                          ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        CLOUDFLARE CDN/WAF                           │
│  - IP: 172.67.175.86, 104.21.91.157                                 │
│  - Bot Detection (some)                                             │
│  - DDoS Protection                                                  │
└─────────────────────────────────┬───────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     KEITARO TDS (GCP Backend)                       │
│  - Via: 1.1 google header confirms GCP                              │
│  - X-RT header indicates Keitaro                                    │
│  - INTERNAL ARCHITECTURE UNKNOWN                                    │
└─────────────────────────────────┬───────────────────────────────────┘
                                  │
              ┌───────────────────┼───────────────────┐
              │                   │                   │
              ▼                   ▼                   ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│    hostg.xyz    │  │    do4g.com     │  │    href.li      │
│   (AWS Proxy)   │  │ (DigitalOcean)  │  │ (Referrer Strip)│
│   aff_id=1636   │  │ Referral Codes  │  │                 │
└────────┬────────┘  └────────┬────────┘  └─────────────────┘
         │                    │
         ▼                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      AFFILIATE NETWORKS                             │
│  HasOffers (Tune): hostinger-elb.go2cloud.org                      │
└─────────────────────────────────┬───────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       BRAND DESTINATIONS                            │
│  457 unique brands (hosting, VPN, e-commerce, travel, finance)     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Entry Points (Verified)

### HTTP Endpoints

| Endpoint | Method | Response | Verification |
|----------|--------|----------|--------------|
| `/click.php` | GET | 302 redirect or error | ✅ Verified |
| `/click` | GET | 302 redirect or error | ✅ Verified |
| `/` | GET | Empty 200 OK | ✅ Verified |
| `/.env` | GET | Empty 200 OK (masking) | ✅ Verified |
| `/admin` | GET | Empty 200 OK (masking) | ✅ Verified |

### Parameters

| Parameter | Required | Purpose | Verification |
|-----------|----------|---------|--------------|
| `campaign_id` | Yes | Campaign identifier | ✅ Verified |
| `pub_id` | Yes | Publisher identifier | ✅ Verified |
| `debug` | No | Triggers safe page | ✅ Verified |

### External Entry Points (Typosquat)

| Domain | Redirect Target | Verification |
|--------|-----------------|--------------|
| `hostinder.com` | href.li → hostg.xyz | ✅ Verified |
| `hostiinger.com` | do4g.com | ✅ Verified |
| `hostinnger.com` | do4g.com | ✅ Verified |
| `hostingerr.com` | do4g.com | ✅ Verified |

---

## Security Findings (Verified)

| Finding | Severity | Verification |
|---------|----------|--------------|
| GCP backend exposed via Via header | Medium | ✅ Verified |
| Security headers missing | Medium | ✅ Verified |
| No rate limiting | Medium | ✅ Verified |
| Session cookie not Secure | Low | ✅ Verified |
| Device fingerprint in cookie | Low | ✅ Verified |

### NOT Vulnerabilities (Corrected)

| Finding | Status | Notes |
|---------|--------|-------|
| Command injection | FALSE | Tested - not vulnerable |
| Neural Partners LLC attribution | FALSE | No evidence of ownership |

---

## IOCs Summary

### Domains

```
yljary.com
trakr.yljary.com
click.yljary.com
hostg.xyz
do4g.com
hostinder.com
hostiinger.com
hostinnger.com
hostingerr.com
```

### IPs

```
172.67.175.86 (Cloudflare)
104.21.91.157 (Cloudflare)
54.151.61.68 (AWS - hostg.xyz)
18.144.110.9 (AWS - hostg.xyz)
157.245.80.13 (DigitalOcean - do4g.com)
```

### Affiliate IDs

```
1636 (Hostinger - Primary)
151905 (Hostinger - Secondary)
1REQUIREFOR51 (Hostinger Referral)
```

---

## Coverage Notes

### Fully Documented (Verified)

- ✅ HTTP request/response behavior
- ✅ Redirect chains
- ✅ Error codes
- ✅ Cookie behavior (observable parts)
- ✅ Affiliate ID routing
- ✅ Typosquat redirect networks

### UNKNOWN (Not Documented)

- ❌ Operator identity
- ❌ Database schema
- ❌ Caching mechanism
- ❌ Message queue implementation
- ❌ Detection algorithms
- ❌ Commission rates
- ❌ Attribution window logic
- ❌ Internal message events
- ❌ Financial transaction details

---

*Index updated 2026-03-24*
*All flows contain only VERIFIED HTTP-level observations*
*Internal system architecture is UNKNOWN*
