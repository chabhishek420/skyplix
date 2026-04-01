# yljary.com Keitaro TDS Investigation

## Overview

Complete investigation of yljary.com Traffic Distribution System (TDS) used for affiliate fraud targeting Hostinger and 457+ other brands.

**Investigation Period:** January - March 2026
**Status:** COMPLETE (Phases 1-3 + Stealth Recon)

---

## Quick Facts

| Metric | Value |
|--------|-------|
| Primary Target | yljary.com |
| Software | Keitaro TDS |
| Backend | Google Cloud Platform |
| CDN/WAF | Cloudflare |
| Active Campaigns | 500-700+ |
| Brands Targeted | 457+ |
| Typosquat Domains | 4 |
| Affiliate IDs Abused | 3 |

---

## Folder Structure

```
yljary-investigation/
├── README.md                 # This file
├── worklog.md               # Complete investigation log
├── architecture-flows/      # System architecture documentation
│   └── index.md
├── extracted-data/          # All extracted JSON, CSV, TXT files
│   ├── *.json              # API responses, DNS data, etc.
│   └── *.csv/*.txt         # Campaign lists, brand lists
├── reports/                 # All investigation reports
│   ├── SECURITY_ASSESSMENT_REPORT.md
│   ├── INFORMATION_GATHERING_REPORT.md
│   ├── COMPREHENSIVE_SECURITY_REPORT.md
│   ├── OWASP_WSTG_COMPLETE_REPORT.md
│   ├── FINAL_DEEP_INTEL_REPORT.md
│   └── POST_MORTEM_REPORT.md
└── verified/                # Verified, corrected reports
    └── STEALTH_RECON_REPORT.md
```

---

## IOCs (Indicators of Compromise)

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
54.176.201.197 (AWS - hostg.xyz)
157.245.80.13 (DigitalOcean - do4g.com)
```

### Affiliate IDs
```
1636 (Hostinger Primary)
151905 (Hostinger Secondary)
1REQUIREFOR51 (Hostinger Referral)
```

### Safe Page Paths
```
/zh-CN, /ru, /de, /ja, /ar, /es, /fr, /pt
```

---

## Key Findings

### Behavioral Modification Detected
The operator detected our testing and modified cloaker behavior:
- `debug=1` parameter trigger was disabled
- Safe page infrastructure remains active (8 languages)
- hostg.xyz IP changed during investigation

### Bot Detection Methodology
- User-Agent: NOT used for filtering
- Referrer: NOT used for filtering
- Debug parameter: WAS used (now disabled)

### Click ID Format (Verified)
```
Structure: [8 hex timestamp][16 hex random]
Example: 69c2e1fe2556a7034a8a2040
         ^^^^^^^^ ^^^^^^^^^^^^^^^^
         Unix TS  Random bytes
```

---

## Investigation Timeline

| Date | Event |
|------|-------|
| January 2026 | yljary.com domain activated |
| February 2026 | do4g.com domain activated |
| March 2026 | Our investigation begins |
| Phase 1 | Discovery, error correction |
| Phase 2 | Verification of UNKNOWN aspects |
| Phase 3 | Deep testing, behavioral change detected |
| Stealth Recon | Safe pages confirmed, IP changed |

---

## Recommendations

### For Brands
1. Check affiliate programs for listed affiliate IDs
2. Monitor for traffic from yljary.com network
3. Review conversion patterns for fraud indicators

### For Hostinger
1. Investigate affiliate IDs 1636, 151905
2. Review referral code 1REQUIREFOR51
3. Consider UDRP for typosquat domains

### For Researchers
1. Use verified data only
2. Operator is actively monitoring
3. Expect countermeasures

---

*Generated: 2026-03-24*
*Classification: Security Research*
