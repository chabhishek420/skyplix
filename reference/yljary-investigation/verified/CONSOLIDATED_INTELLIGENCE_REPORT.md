# CONSOLIDATED INTELLIGENCE REPORT
## yljary.com Keitaro TDS Infrastructure

**Classification:** Security Research
**Date:** 2026-03-24
**Status:** COMPLETE (Phases 1-3 + Stealth Recon)

---

## EXECUTIVE SUMMARY

A comprehensive multi-phase investigation of yljary.com's Keitaro Traffic Distribution System (TDS) has been completed. The system operates an affiliate traffic routing network targeting 500+ brands across hosting, VPN, e-commerce, travel, and finance sectors.

**Critical Finding:** The operator detected our testing and modified cloaker behavior in real-time.

---

## KEY FINDINGS

### Infrastructure

| Component | Value | Verification |
|-----------|-------|--------------|
| Primary Domain | yljary.com | ✅ Verified |
| Active Subdomains | trakr.yljary.com, click.yljary.com | ✅ Verified |
| CDN | Cloudflare | ✅ Verified |
| Backend | Google Cloud Platform | ✅ Verified |
| Software | Keitaro TDS | ✅ Verified |

### Scale

| Metric | Value |
|--------|-------|
| Active Campaigns | 500-700+ |
| Unique Brands | 457+ |
| Active Publishers | 31+ |
| Typosquat Domains | 4 |
| Safe Page Languages | 8 |

### Affiliate IDs (Active)

| ID | Type | Status |
|----|------|--------|
| 1636 | Hostinger Affiliate | ✅ Active |
| 151905 | Hostinger Affiliate | ✅ Active |
| 1REQUIREFOR51 | Hostinger Referral | ✅ Active |

---

## IOCs

### Domains
```
yljary.com, trakr.yljary.com, click.yljary.com
hostg.xyz, do4g.com
hostinder.com, hostiinger.com, hostinnger.com, hostingerr.com
```

### IPs
```
172.67.175.86, 104.21.91.157 (Cloudflare)
54.176.201.197 (AWS - hostg.xyz)
157.245.80.13 (DigitalOcean - do4g.com)
```

### Affiliate IDs
```
1636, 151905, 1REQUIREFOR51
```

---

## BEHAVIORAL MODIFICATION EVIDENCE

During Phase 3 testing:
```
Before: debug=1 → HTTP 302 to zh-CN safe page
After:  debug=1 → HTTP 302 to normal affiliate redirect
```

The operator modified the cloaker in real-time.

---

## RECOMMENDATIONS

### For Brands
1. Check affiliate programs for listed affiliate IDs
2. Monitor for traffic from yljary.com network
3. Review conversion patterns for fraud indicators

### For Hostinger
1. Investigate affiliate IDs 1636, 151905
2. Review referral code 1REQUIREFOR51
3. Consider UDRP for typosquat domains

---

*Report generated: 2026-03-24*
