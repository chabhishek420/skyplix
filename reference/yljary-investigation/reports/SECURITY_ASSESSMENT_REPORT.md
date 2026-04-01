# Security Assessment Report: yljary.com Cloaker Infrastructure

**Assessment Date:** March 23, 2026  
**Classification:** CONFIDENTIAL - THREAT INTELLIGENCE  
**Assessment Type:** Web Application Security Testing & OSINT

---

## Executive Summary

A comprehensive security assessment was conducted against the yljary.com cloaker infrastructure, revealing a sophisticated multi-layered traffic distribution system engaged in Hostinger affiliate fraud. The investigation uncovered **multiple related domains**, **additional affiliate accounts**, and a **broader typosquatting ecosystem** targeting Hostinger's affiliate and referral programs.

### Key Findings

| Finding | Severity | Status |
|---------|----------|--------|
| Active Keitaro TDS Cloaker | CRITICAL | Confirmed |
| Hostinger Affiliate Fraud (aff_id=1636, 151905) | CRITICAL | Confirmed |
| Google Cloud Platform Backend Exposure | HIGH | Confirmed |
| Multiple Affiliate Account Abuse | HIGH | Confirmed |
| Typosquatting Network (8+ domains) | HIGH | Confirmed |
| Multi-layer Redirect Obfuscation | MEDIUM | Confirmed |
| Wildcard SSL Certificate | LOW | Confirmed |

---

## 1. Reconnaissance Results

### 1.1 Subdomain Enumeration

**Methodology:** DNS brute-force + Certificate Transparency logs

**Discovered Subdomains:**

| Subdomain | IP Address | Provider |
|-----------|------------|----------|
| www.yljary.com | 104.21.91.157 | Cloudflare |
| trakr.yljary.com | 172.67.175.86 | Cloudflare |
| trk.yljary.com | 104.21.91.157 | Cloudflare |
| click.yljary.com | 172.67.175.86 | Cloudflare |
| rdt.yljary.com | 172.67.175.86 | Cloudflare |

**SSL Certificate:**
```
Subject: CN=yljary.com
Issuer: C=US; O=Google Trust Services; CN=WE1
Certificate Type: Wildcard (*.yljary.com)
Valid: Jan 27, 2026 - Apr 27, 2026
```

### 1.2 DNS Records

```
yljary.com (Apex):
  NS: kay.ns.cloudflare.com, dexter.ns.cloudflare.com
  SOA: dexter.ns.cloudflare.com
  
Note: Apex domain has no A record (Cloudflare proxy only)
```

### 1.3 Port Scanning

**Target IPs:** 104.21.91.157, 172.67.175.86 (Cloudflare)

| Port | Status | Service |
|------|--------|---------|
| 80 | OPEN | HTTP |
| 443 | OPEN | HTTPS |
| 8080 | OPEN | HTTP-Alt |
| 8443 | OPEN | HTTPS-Alt |

*Note: These are Cloudflare edge ports, not origin server.*

---

## 2. HTTP Probing Results

### 2.1 Response Analysis

| Endpoint | Status | Size | Notes |
|----------|--------|------|-------|
| www.yljary.com | 404 | Variable | Fake Google 404 (Safe Page) |
| trakr.yljary.com/ | 200 | 0 bytes | Empty response |
| trakr.yljary.com/click | 200 | 81 bytes | **ACTIVE TRACKER** |
| trakr.yljary.com/admin | 200 | 0 bytes | Cloaker response |
| trakr.yljary.com/.env | 200 | 0 bytes | No leak (cloaker returns empty) |
| All other paths | 200 | 0 bytes | Cloaker masking |

**Critical Discovery:** The `/click` endpoint is the only active path returning real content.

### 2.2 HTTP Headers Analysis

**Key Security-Relevant Headers:**

```
Server: cloudflare
Via: 1.1 google          <-- CRITICAL: Reveals GCP backend
X-RT: 108                <-- Keitaro internal metric
Referrer-Policy: no-referrer
```

**OPSEC Failure:** The `Via: 1.1 google` header reveals the origin server is hosted on Google Cloud Platform, bypassing Cloudflare's IP hiding.

---

## 3. Directory & File Fuzzing

### 3.1 Endpoint Discovery

**Method:** Wordlist-based enumeration with response size analysis

**Keitaro TDS Endpoints Identified:**

```
/click         - Active click tracking
/postback      - Server-to-server postback
/redirect      - Redirect handler
/go            - Alternative redirect
```

**Security Test Endpoints (All Return Empty 200):**

```
/admin         - Admin panel (protected)
/.env          - Environment file (no leak)
/config.php    - Configuration (no leak)
/phpinfo.php   - PHP info (no leak)
/adminer.php   - Database admin (no leak)
```

**Conclusion:** The cloaker returns HTTP 200 with empty body for all unknown paths to prevent enumeration.

---

## 4. Parameter Discovery

### 4.1 Keitaro TDS Parameters

**Confirmed Parameters:**

| Parameter | Purpose | Example Value |
|-----------|---------|---------------|
| campaign_id | Campaign identifier | 10115 |
| pub_id | Publisher ID | 102214 |
| p1 | Custom parameter 1 | {click_id} |
| p2 | Custom parameter 2 | {sub_id} |
| source | Traffic source | {source} |
| keyword | Search keyword | (varies) |

### 4.2 Redirect Chain Analysis

**Full Redirect Chain Captured:**

```
1. https://trakr.yljary.com/click?campaign_id=10115&pub_id=102214
   ↓ HTTP 302
   
2. https://www.hostg.xyz/aff_c?offer_id=753&aff_id=1636&aff_sub=102214&aff_sub2=[HASH]
   ↓ HTTP 302
   
3. https://www.hostinger.com/geo?utm_medium=affiliate&utm_source=aff1636&utm_campaign=753&session=[SESSION]
   ↓ HTTP 302
   
4. https://www.hostinger.com/hk?utm_medium=affiliate&utm_source=aff1636&utm_campaign=753&session=[SESSION]
   ↓ HTTP 200
   
   Title: "Hostinger - Få dine idéer online med en hjemmeside" (Danish)
```

**Affiliate Tracking Cookies Set:**

```
utm_medium=affiliate
utm_source=aff1636
utm_campaign=753
hasoffers_session=[SESSION_ID]
```

---

## 5. OSINT Results

### 5.1 URLScan Intelligence

**Scan Found:** 2026-03-23T19:33:43.367Z

```
URL: https://trakr.yljary.com/click?campaign_id=10115&pub_id=102214
Final URL: https://www.hostinger.com/dk
Title: Hostinger - Få dine idéer online med en hjemmeside
Server: cloudflare
Redirected: off-domain
Uniq IPs: 20
Uniq Countries: 6
Requests: 112
```

### 5.2 Related Domain Discovery

**CRITICAL: Related Typosquatting Network Discovered**

#### Primary Network (hostg.xyz intermediary):

| Domain | Affiliate ID | Offer ID | Method |
|--------|--------------|----------|--------|
| yljary.com → trakr | 1636 | 753 | Keitaro TDS |
| hostinder.com | 151905 | 6 | Direct redirect |

#### Secondary Network (do4g.com intermediary):

| Domain | Redirect Target | Referral Code |
|--------|-----------------|---------------|
| hostiinger.com | do4g.com → hostinger.com | 1REQUIREFOR51 |
| hostinnger.com | do4g.com → hostinger.com | 1REQUIREFOR51 |
| hostingerr.com | do4g.com → hostinger.com | 1REQUIREFOR51 |

### 5.3 Infrastructure Correlation

**Shared Infrastructure:**

```
Cloudflare Nameservers:
- kay.ns.cloudflare.com / dexter.ns.cloudflare.com (yljary.com)
- rob.ns.cloudflare.com / dora.ns.cloudflare.com (hostinder.com)
- ajay.ns.cloudflare.com / hadlee.ns.cloudflare.com (do4g.com)

Cloudflare IPs:
- 104.21.91.157 (yljary.com, trk.yljary.com)
- 172.67.175.86 (trakr.yljary.com, click.yljary.com, rdt.yljary.com)
- 172.67.141.61 (hostinder.com)
- 104.21.26.30 (hostiinger.com)
- 172.67.190.66 (hostinnger.com)
- 172.67.135.18 (hostingerr.com)
```

---

## 6. Vulnerability Assessment

### 6.1 Information Disclosure

**Finding:** Backend hosting provider exposed via HTTP headers

**Evidence:**
```
Via: 1.1 google
```

**Impact:** Reveals origin server is on Google Cloud Platform, enabling targeted attacks.

**Severity:** HIGH

### 6.2 Affiliate Fraud Infrastructure

**Finding:** Active cloaker distributing fraudulent affiliate traffic

**Evidence:**
- Live redirect chain to Hostinger with affiliate tracking
- Multiple affiliate accounts (1636, 151905)
- Referral code abuse (1REQUIREFOR51)

**Impact:** Financial fraud against Hostinger's affiliate program

**Severity:** CRITICAL

### 6.3 Typosquatting Campaign

**Finding:** Coordinated typosquatting campaign targeting Hostinger brand

**Evidence:** 8+ domains with similar redirect patterns

**Severity:** HIGH

---

## 7. Indicators of Compromise (IOCs)

### 7.1 Domain IOCs

```
# Primary Infrastructure
yljary.com
trakr.yljary.com
trk.yljary.com
click.yljary.com
rdt.yljary.com
www.yljary.com

# Affiliate Intermediary
hostg.xyz
www.hostg.xyz

# Related Typosquatting
hostinder.com
hostinder.com.br
hostiinger.com
hostinnger.com
hostingerr.com

# Secondary Intermediary
do4g.com
```

### 7.2 IP IOCs

```
# Cloudflare IPs
104.21.91.157
172.67.175.86
172.67.141.61
104.21.26.30
172.67.190.66
172.67.135.18

# do4g.com Origin
157.245.80.13 (DigitalOcean)
```

### 7.3 Affiliate IDs

```
aff_id=1636      # Primary account
aff_id=151905    # Secondary account
offer_id=753     # Primary offer
offer_id=6       # Secondary offer
REFERRALCODE=1REQUIREFOR51
```

### 7.4 URL Patterns

```
/click?campaign_id=*&pub_id=*
/aff_c?offer_id=*&aff_id=*
utm_source=aff*
utm_medium=affiliate
hasoffers_session=*
```

---

## 8. Detection Rules

### 8.1 Sigma Rule

```yaml
title: Yljary Cloaker Infrastructure Detection
status: experimental
description: Detects traffic to yljary.com cloaker infrastructure
author: Security Assessment Team
date: 2026/03/23
logsource:
  category: proxy
detection:
  selection:
    c-uri|contains:
      - 'yljary.com'
      - 'hostg.xyz'
      - 'hostinder.com'
      - 'hostiinger.com'
      - 'hostinnger.com'
      - 'hostingerr.com'
  condition: selection
fields:
  - src_ip
  - c-uri
  - dst_ip
falsepositives:
  - Unlikely
level: high
```

### 8.2 Suricata Rule

```
alert http any any -> any any (msg:"YLJARY CLOAKER Affiliate Fraud"; flow:established,to_server; content:"yljary.com"; nocase; http_host; classtype:trojan-activity; sid:1000001; rev:1;)
alert http any any -> any any (msg:"HOSTG.XYZ Affiliate Intermediary"; flow:established,to_server; content:"hostg.xyz"; nocase; http_host; classtype:trojan-activity; sid:1000002; rev:1;)
alert http any any -> any any (msg:"HOSTINGER TYPOSQUAT DETECTED"; flow:established,to_server; content:"hostinder.com"; nocase; http_host; classtype:trojan-activity; sid:1000003; rev:1;)
```

---

## 9. Recommendations

### 9.1 Immediate Actions

1. **Report to Hostinger:**
   - Affiliate IDs 1636 and 151905 are engaged in fraud
   - Referral code 1REQUIREFOR51 is abused
   - Provide full redirect chain documentation

2. **Report to Cloudflare:**
   - Abuse report for all identified domains
   - ToS violation (affiliate fraud infrastructure)

3. **Report to Google Cloud:**
   - Platform abuse for hosting cloaker backend

### 9.2 Detection Enhancements

1. Deploy provided Sigma and Suricata rules
2. Monitor for typosquatting domain registrations
3. Track affiliate ID usage patterns

### 9.3 Further Investigation

1. Monitor affiliate account 1636 for additional domains
2. Investigate do4g.com operator connection
3. Track SSL certificate changes via CT logs

---

## 10. Attack Chain Summary

```
┌─────────────────────────────────────────────────────────────────┐
│                    ATTACK CHAIN DIAGRAM                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [Victim]                                                       │
│     │                                                           │
│     ▼                                                           │
│  [Ad Platform: Facebook/Bing/Spotify]                          │
│     │                                                           │
│     ▼                                                           │
│  [yljary.com Keitaro TDS] ◄─── Cloudflare Protected            │
│     │                                                           │
│     ├──► Bot/Scanner ──► www.yljary.com ──► Fake 404          │
│     │                                                           │
│     └──► Real User ──► trakr.yljary.com/click                  │
│                              │                                  │
│                              ▼                                  │
│                    [Google Cloud Backend]                       │
│                              │                                  │
│                              ▼                                  │
│                    [hostg.xyz HasOffers]                        │
│                              │                                  │
│                              ▼                                  │
│                    [hostinger.com]                              │
│                              │                                  │
│                              ▼                                  │
│                    [Affiliate Commission $$$$$]                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Appendix A: Response Headers

### trakr.yljary.com/click (302 Redirect)

```
HTTP/2 302 
date: Mon, 23 Mar 2026 20:49:26 GMT
content-length: 0
location: https://www.hostg.xyz/aff_c?offer_id=753&aff_id=1636&aff_sub=102214&aff_sub2=[HASH]
server: cloudflare
referer: 
referrer-policy: no-referrer
x-rt: 108
set-cookie: sess_[ID]=[VALUE]; expires=Mon, 30 Mar 2026; path=/; HttpOnly
via: 1.1 google
cf-cache-status: DYNAMIC
```

### hostinder.com (301 Redirect)

```
HTTP/2 301 
location: https://href.li?https://www.hostg.xyz/aff_c?offer_id=6&aff_id=151905&source=buildinpublic-der
server: cloudflare
```

---

## Appendix B: Session Tracking

**Keitaro Session Cookie:**
```
Name: sess_64566fa148714a3a0f517fbe
Value: 6458f31b90598127b526a074
Expiry: 7 days
Attributes: HttpOnly
```

**HasOffers Session Cookie:**
```
Name: hasoffers_session
Value: 10257806b5459d2f1e335849cbe1d7
Expiry: 60 days
Domain: hostinger.com
```

---

**Report Classification:** CONFIDENTIAL  
**Distribution:** Security Team, Threat Intelligence, Legal  
**Retention:** Permanent

*End of Report*
