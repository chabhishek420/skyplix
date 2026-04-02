<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-02 | Updated: 2026-04-02 -->

# test

## Purpose
Test suites including unit tests and integration tests.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `unit/` | Unit tests (see `unit/AGENTS.md`) |
| `integration/` | Integration tests with testdata (see `integration/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Run all tests: `go test ./...`
- Unit tests use standard Go testing package
- Integration tests may require docker-compose services

### Test Patterns
- `*_test.go` files for test implementations
- Use testify for assertions
- Testdata in subdirectories for fixtures

<!-- MANUAL: -->