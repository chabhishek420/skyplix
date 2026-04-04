---
phase: 4
plan: 1
wave: 1
---

# Plan 4.1: Bot IP Range/CIDR Management Engine

## Objective
Create `internal/botdb/` — a high-performance IP range management package that supports CIDR, single IP, and range notation with binary search lookups over sorted uint32 intervals. This is the core data structure that all advanced bot detection queries against. Modeled directly on Keitaro's `UserBotsService.php` (sorted int arrays, merge overlapping, exclude ranges).

## Context
- `.gsd/SPEC.md` — Bot detection requirements
- `.gsd/DECISIONS.md` — ADR-008 (inline bot detection), ADR-011 (multi-layer cloaking)
- `reference/Keitaro_source_php/application/Component/BotDetection/Service/UserBotsService.php` — IP range management reference
- `internal/pipeline/stage/3_build_raw_click.go` — Current bot detection (hardcoded patterns, to be upgraded)
- `internal/model/models.go` — Domain model types

## Tasks

<task type="auto">
  <name>Create internal/botdb/store.go — IP Range Store</name>
  <files>internal/botdb/store.go</files>
  <action>
    Create `internal/botdb/store.go` with:

    1. **Types:**
       - `IPRange struct { Min, Max uint32; Raw string }` — Single range entry (mirrors Keitaro's `[min_ip, max_ip, raw_value]` tuple)
       - `Store struct` — Thread-safe IP range store with `sync.RWMutex` protecting a `[]IPRange` sorted by Min

    2. **Core Methods:**
       - `New() *Store` — Constructor
       - `Contains(ip net.IP) bool` — Binary search over sorted ranges. Convert IP to uint32 via `binary.BigEndian.Uint32(ip.To4())`. Use `sort.Search()` to find the first range where `Min > ipUint`, then check the range before it (`ranges[i-1].Max >= ipUint`).
       - `Add(input string) error` — Parse input (supports formats: single IP "1.2.3.4", CIDR "10.0.0.0/8", range "1.2.3.0-1.2.3.255", multi-line/comma-separated). For each entry: parse to IPRange, merge into existing sorted list, merge overlapping intervals.
       - `Exclude(input string) error` — Parse input same as Add, then crop existing ranges to exclude the parsed ranges (port Keitaro's `_cropRanges` logic).
       - `Replace(input string) error` — Clear and replace with new list.
       - `Clear()` — Empty the store.
       - `List() []IPRange` — Return snapshot of all ranges.
       - `Count() int` — Number of ranges.

    3. **Internal helpers:**
       - `parseInput(content string) ([]IPRange, error)` — Split by newlines and commas, call `parseEntry` on each
       - `parseEntry(entry string) (IPRange, error)` — Dispatch to parseCIDR/parseRange/parseSingle based on "/" or "-" detection
       - `parseCIDR(cidr string) (IPRange, error)` — `net.ParseCIDR()` → extract network/broadcast as uint32
       - `parseRange(rang string) (IPRange, error)` — Split by "-", parse both IPs
       - `parseSingle(ip string) (IPRange, error)` — Single IP → Min == Max
       - `ipToUint32(ip net.IP) uint32` — `binary.BigEndian.Uint32(ip.To4())`
       - `mergeOverlapping(ranges []IPRange) []IPRange` — Sort by Min, merge overlapping/adjacent intervals (mirrors Keitaro's `_mergeIntersectedInSortedList`)
       - `cropRanges(src IPRange, excludes []IPRange) []IPRange` — Split a source range around excluded ranges (mirrors Keitaro's `_cropRanges`)

    **Design choices to follow:**
    - IPv4-only for bot IP ranges (Keitaro is IPv4-only). IPv6 IPs should be silently skipped.
    - All mutations must hold write lock, Contains must hold read lock.
    - Merged list must be re-sorted after every mutation.
    - Do NOT import any external packages — this is pure stdlib.
  </action>
  <verify>go build ./internal/botdb/...</verify>
  <done>store.go compiles, Contains() uses binary search, Add/Exclude/Replace/Clear all handle locking correctly</done>
</task>

<task type="auto">
  <name>Create internal/botdb/store_test.go — Unit Tests</name>
  <files>internal/botdb/store_test.go</files>
  <action>
    Create `internal/botdb/store_test.go` with comprehensive unit tests:

    1. **TestContains_SingleIP** — Add "1.2.3.4", assert Contains("1.2.3.4") == true, Contains("1.2.3.5") == false
    2. **TestContains_CIDR** — Add "192.168.1.0/24", assert Contains("192.168.1.1") == true, Contains("192.168.2.1") == false
    3. **TestContains_Range** — Add "10.0.0.1-10.0.0.100", assert Contains("10.0.0.50") == true, Contains("10.0.0.101") == false
    4. **TestContains_MultiInput** — Add "1.1.1.1\n2.2.2.0/24\n3.3.3.1-3.3.3.10", assert all three type ranges work
    5. **TestMergeOverlapping** — Add "10.0.0.0-10.0.0.50" then Add "10.0.0.40-10.0.0.100", assert Count() == 1 (merged), Contains("10.0.0.75") == true
    6. **TestExclude** — Add "10.0.0.0/8", Exclude "10.0.0.0/24", assert Contains("10.0.0.1") == false, Contains("10.0.1.1") == true
    7. **TestReplace** — Add "1.1.1.0/24", Replace "2.2.2.0/24", assert Contains("1.1.1.1") == false, Contains("2.2.2.1") == true
    8. **TestClear** — Add entries, Clear(), assert Count() == 0
    9. **TestInvalidInput** — Ensure invalid IPs/CIDRs return errors gracefully
    10. **TestEmptyStore** — Contains() on empty store returns false
    11. **TestIPv6Ignored** — Add "::1", assert no panic, Count() == 0

    Do NOT use `//go:build integration` — these are pure unit tests.
  </action>
  <verify>go test -v ./internal/botdb/...</verify>
  <done>All 11 test cases pass, binary search correctness verified by boundary checks</done>
</task>

## Success Criteria
- [ ] `internal/botdb/store.go` compiles with zero external dependencies
- [ ] `go test -v ./internal/botdb/` — All tests pass
- [ ] Contains() performs O(log n) binary search (verified by `sort.Search` usage)
- [ ] Merge/exclude operations match Keitaro's `UserBotsService` behavior
- [ ] Thread-safety via `sync.RWMutex`
