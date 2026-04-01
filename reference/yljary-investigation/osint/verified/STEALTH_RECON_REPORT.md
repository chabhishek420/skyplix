# STEALTH RECONNAISSANCE REPORT

## Executive Summary

Stealth reconnaissance was conducted using varied timing, different user agents, passive intelligence gathering, and alternative request patterns. The operator has modified specific behaviors but the core infrastructure remains intact.

---

## KEY FINDINGS

### 1. Behavioral Changes Confirmed

| Behavior | Phase 1 | Stealth Recon | Change |
|----------|---------|---------------|--------|
| `debug=1` parameter | Redirect to safe page | Normal affiliate redirect | ✅ MODIFIED |
| `zh-CN` with trailing slash | HTTP 200 | HTTP 301 | ✅ MODIFIED |
| `zh-CN` without slash | HTTP 200 | HTTP 200 | ⚠️ UNCHANGED |
| Mobile user agents | Unknown | Normal redirect | ✅ WORKING |
| Referrer filtering | Unknown | None detected | ✅ WORKING |

**Conclusion:** Operator modified specific trigger behaviors but core cloaker functionality remains.

---

### 2. Safe Page Infrastructure Still Active

**Language-based safe pages discovered:**

| Path | HTTP Status | Content |
|------|-------------|---------|
| `/zh-CN` | 200 | Empty (masked) |
| `/zh-TW` | 301 | Redirect |
| `/ru` | 200 | Empty (masked) |
| `/de` | 200 | Empty (masked) |
| `/ja` | 200 | Empty (masked) |
| `/ar` | 200 | Empty (masked) |
| `/es` | 200 | Empty (masked) |
| `/fr` | 200 | Empty (masked) |
| `/pt` | 200 | Empty (masked) |

**Pattern:**
- Without trailing slash: HTTP 200 (safe page exists)
- With trailing slash: HTTP 301 (redirect)

**Conclusion:** Cloaker safe page infrastructure is **FULLY OPERATIONAL** - only the debug parameter trigger was disabled.

---

### 3. Infrastructure Timeline (Certificate Transparency)

#### yljary.com
```
2026-01-10: First certificate issued (Sectigo)
2026-01-27: Google Trust Services certificate
2026-03-11: Certificate renewed (Sectigo, expires 2026-06-09)
```

#### do4g.com
```
2026-02-01: First certificate (Sectigo)
2026-03-11: Google Trust Services certificate
2026-03-19: Certificate renewed (Sectigo, expires 2026-10-03)
```

**Analysis:**
- yljary.com operational since at least **January 2026**
- do4g.com operational since at least **February 2026**
- Both domains maintained with dual CA certificates
- Recent certificate updates indicate active management

---

### 4. IP Address Update

| Domain | Previous IP | Current IP | Change |
|--------|-------------|------------|--------|
| hostg.xyz | 54.151.61.68 | 54.176.201.197 | ⚠️ CHANGED |
| do4g.com | 157.245.80.13 | 157.245.80.13 | Same |
| hostinder.com | 172.67.141.61 | 172.67.141.61 | Same |

**Note:** hostg.xyz IP address changed during our investigation.

---

### 5. User-Agent Testing Results

| User Agent Type | Result | Notes |
|-----------------|--------|-------|
| Desktop Chrome | ✅ Normal redirect | |
| Desktop Firefox | ✅ Normal redirect | |
| Desktop Safari | ✅ Normal redirect | |
| iPhone Safari | ✅ Normal redirect | |
| Android Chrome | ✅ Normal redirect | |
| iPad Safari | ✅ Normal redirect | |
| Googlebot | ✅ Normal redirect | No bot detection |
| bingbot | ✅ Normal redirect | No bot detection |
| curl/wget | ✅ Normal redirect | No bot detection |
| Selenium/Headless | ✅ Normal redirect | No bot detection |

**Conclusion:** User-Agent is **NOT** used for traffic filtering.

---

### 6. Referrer Testing Results

| Referrer | Result |
|----------|--------|
| Google search | ✅ Normal redirect |
| Facebook | ✅ Normal redirect |
| Direct (none) | ✅ Normal redirect |

**Conclusion:** Referrer header is **NOT** used for traffic filtering.

---

### 7. Campaign Discovery (Stealth Mode)

Using randomized delays and varied campaign IDs:

| Campaign | Destination |
|----------|-------------|
| 10065 | dreamhost.com |
| 10250 | tofflondon.com |
| 10297 | zanducare.com |
| 10316 | wajeez.com |

**Conclusion:** Campaign enumeration still works with stealthy request patterns.

---

## OPERATOR COUNTERMEASURES ANALYSIS

### What Was Modified

1. **Debug parameter trigger** - `debug=1` no longer triggers safe page
2. **URL normalization** - Trailing slash behavior changed

### What Was NOT Modified

1. Safe page infrastructure (8+ language paths active)
2. Core redirect functionality
3. Campaign routing
4. Affiliate tracking parameters
5. Publisher ID validation

### Operator Response Pattern

```
Detection: Operator noticed repeated debug parameter testing
Response: Disabled debug parameter → safe page trigger
Result: Cloaker still functional, just harder to trigger
```

---

## INFRASTRUCTURE SUMMARY

### Active Domains

| Domain | Status | IPs |
|--------|--------|-----|
| yljary.com | Active | Cloudflare (2 IPs) |
| trakr.yljary.com | Active | Cloudflare (2 IPs) |
| click.yljary.com | Active | Cloudflare (2 IPs) |
| hostg.xyz | Active | 54.176.201.197 (AWS) |
| do4g.com | Active | 157.245.80.13 (DO) |
| hostinder.com | Active | Cloudflare (2 IPs) |

### Safe Page Endpoints

```
/zh-CN  - Chinese (Simplified)
/zh-TW  - Chinese (Traditional)
/ru     - Russian
/de     - German
/ja     - Japanese
/ar     - Arabic
/es     - Spanish
/fr     - French
/pt     - Portuguese
```

---

## OPERATIONAL TIMELINE

```
January 2026:  yljary.com domain activated
February 2026: do4g.com domain activated
March 2026:    Certificate renewals
Phase 1:       Aggressive testing detected
Phase 3:       Operator disables debug parameter
Stealth Recon: Safe pages still active, IP changed
```

---

## RECOMMENDATIONS

### For Continued Research

1. **Use stealthy request patterns** - Random delays, varied headers
2. **Avoid repeated trigger parameters** - Operator monitors for affiliate fraud
Phase 1:       Aggressive testing detected
Phase 3:       Operator disables debug parameter
Stealth Recon: Safe pages still active, IP changed
```

---

## RECOMMENDATIONS

### For Continued Research

1. **Use stealthy request patterns** - Random delays, varied headers
2. **Avoid repeated trigger parameters** - Operator monitors for patterns
3. **Test safe page access directly** - `/[lang]` paths still work
4. **Monitor for further changes** - Operator is actively managing

### For Intelligence Collection

1. **Passive collection preferred** - Certificate transparency, DNS
2. **Indirect infrastructure analysis** - Related domains, hosting
3. **Behavioral baseline** - Document normal vs modified behavior

---

## VERIFICATION STATUS UPDATE

| Finding | Previous | Current |
|---------|----------|---------|
| Debug parameter trigger | Active | DISABLED |
| Safe page infrastructure | Active | ACTIVE |
| User-Agent detection | None | NONE |
| Referrer filtering | None | NONE |
| hostg.xyz IP | 54.151.61.68 | 54.176.201.197 |
| Campaign enumeration | Working | WORKING |

---

*Stealth reconnaissance completed: 2026-03-24*
*Operator countermeasures active but partial*
*Core cloaker infrastructure fully operational*
