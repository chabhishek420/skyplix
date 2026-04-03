---
phase: 4.9
plan: 1
wave: 1
depends_on: []
files_modified:
  - internal/action/action.go
  - internal/filter/filter.go
  - internal/botdb/valkey.go
  - .gsd/TODO.md
autonomous: true
must_haves:
  truths:
    - "Deprecated strings.Title is removed from the codebase."
    - "TODO.md is synchronized with actual implementation state."
  artifacts:
    - ".gsd/TODO.md (updated)"
---

# Plan 4.9.1: Technical Debt & Audit Cleanup

<objective>
Resolve cross-cutting technical debt and synchronize documentation.
- Replace deprecated `strings.Title` with `golang.org/x/text/cases.Title` to comply with Go 1.18+ standards.
- Cleanup `TODO.md` to remove completed Phase 1-4 items that were left checked but cluttering.

Output:
- Cleaned and compliant codebase.
- Accurate TODO tracking.
</objective>

<context>
Load for context:
- .gsd/TODO.md
- internal/action/action.go
- internal/filter/filter.go
</context>

<tasks>

<task type="auto">
  <name>Replace strings.Title Deprecation</name>
  <files>internal/action/action.go, internal/filter/filter.go, internal/botdb/valkey.go</files>
  <action>
    Replace all occurrences of `strings.Title(strings.ToLower(val))` with `cases.Title(language.Und).String(strings.ToLower(val))`.
    Install `golang.org/x/text` dependency if missing.
    AVOID: Using `strings.Title` as it is deprecated and handles Unicode incorrectly.
  </action>
  <verify>go test -v ./internal/action/... ./internal/filter/...</verify>
  <done>Codebase builds without strings.Title usage.</done>
</task>

<task type="auto">
  <name>Cleanup TODO.md</name>
  <files>.gsd/TODO.md</files>
  <action>
    Remove or mark as [x] all items that are confirmed finished in Phase 4 (MaxMind ASN, Bot UA signatures, IP range binary search, Enhanced RemoteProxy).
    Recategorize remaining security debt to a "Post-Launch Hardening" section if they are no longer blockers.
  </action>
  <verify>Check TODO.md for accuracy against reality.</verify>
  <done>TODO.md reflects the current state of the project (Phase 5 ready).</done>
</task>

</tasks>

<verification>
After all tasks, verify:
- [ ] go build ./... passes.
- [ ] `grep -r "strings.Title" .` returns zero results in project code.
</verification>

<success_criteria>
- [ ] No more deprecated Title usage.
- [ ] TODO.md is 100% accurate.
</success_criteria>
