# Bot Rule Security & Performance Guidelines

This document outlines the best practices for creating and managing bot detection rules in the ZAI TDS. These guidelines are designed to prevent performance degradation and security vulnerabilities, specifically Regular Expression Denial of Service (ReDoS).

## Regex Safety Standards

The TDS uses a regex-based engine for user agent and click attribute filtering. Improperly crafted regex can lead to exponential backtracking, causing high CPU usage and potential system crashes.

### 1. Avoid Nested Quantifiers
**Danger**: `(a+)+`, `(a|aa)+`, `(a*)*`
**Reason**: These patterns create an astronomical number of possible matches, leading to ReDoS when faced with a non-matching string like `aaaaaaX`.

### 2. Avoid Overlapping Alternatives
**Danger**: `(admin|admin-api)`
**Better**: `admin(-api)?`
**Reason**: Overlapping patterns cause the engine to check multiple paths for the same substring.

### 3. Use Anchors where possible
**Good**: `^Mozilla/5\.0`
**Better**: If you know where the pattern starts or ends, use `^` (start) or `$` (end).

### 4. Limit Repetition
**Danger**: `.*` or `.+`
**Good**: `.{1,100}`
**Reason**: Unbounded wildcards can consume massive amounts of memory and CPU if not carefully controlled.

## ReDoS Prevention Checklist

Before adding a new Bot Rule regex, verify:
- [ ] No nested quantifiers (e.g., `(a*)*`).
- [ ] No overlapping alternatives (e.g., `(user|user-agent)`).
- [ ] No large unbounded wildcards followed by a specific character (e.g., `.*X`).
- [ ] Tested with a string that is "almost" a match to check for long-running execution.

## Performance Monitoring

The ZAI TDS system logs the execution time of bot detection rules. If you notice a spike in request latency, audit the following:
1.  **Rule Count**: Keep the total number of active bot rules below 100 for optimal performance.
2.  **Order of Execution**: Place the most likely matches (and cheapest regexes) at the top of the list.
3.  **Audit Logs**: Check `AuditLog` for entries related to `Bot Detection` failures or timeouts.

## Recommended Tooling

To test your regex patterns for ReDoS vulnerabilities:
- [Regex101](https://regex101.com/) (Check the "Debugger" for step counts)
- [SafeRegex](https://github.com/davisjam/safe-regex) (CLI tool for vulnerability scanning)

---
*Created: April 2026*
*Version: 1.0.0*
