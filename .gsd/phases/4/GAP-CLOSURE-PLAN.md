---
phase: 4
plan: fix-gaps-verif
wave: 1
gap_closure: true
---

# Fix Plan: Phase 4 Verification Gaps

## Problem
Verification discovered two critical bugs in the Safe Page implementation:
1. `RemoteProxyAction` is registered as a zero-value struct in `action.NewEngine()`, but its methods expect a non-nil `client`. This will cause a panic when a stream with `action_type: "Remote"` is accessed.
2. `CurlAction` incorrectly uses `fmt.Fprint(w, resp.Body)`, which streams the pointer address of the body reader instead of the body content.

## Tasks

<task type="auto">
  <name>Fix RemoteProxyAction registration</name>
  <files>internal/action/action.go</files>
  <action>Change &RemoteProxyAction{} to NewRemoteProxyAction(0) in NewEngine() to ensure client and TTL are initialized.</action>
  <verify>Run the new integration test case for Remote action.</verify>
  <done>Action engine correctly initializes the proxy with a client.</done>
</task>

<task type="auto">
  <name>Fix CurlAction body streaming</name>
  <files>internal/action/content.go</files>
  <action>Replace fmt.Fprint(w, resp.Body) with io.Copy(w, resp.Body) in CurlAction.Execute.</action>
  <verify>curl -v http://localhost:8080/campaign-with-curl-action</verify>
  <done>CurlAction properly streams remote content to the user.</done>
</task>

<task type="auto">
  <name>Add Remote/Curl Action Integration Tests</name>
  <files>test/integration/cloaking_test.go, test/integration/testdata/seed_phase4.sql</files>
  <action>Add a new test case for Remote proxy and Curl mode to ensure they don't panic and return content.</action>
  <verify>go test -v -tags integration ./test/integration/</verify>
  <done>Integration tests prove safe page modes are fully functional.</done>
</task>
