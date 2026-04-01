<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# data

## Purpose
Static lookup datasets used by filters, macros, and bot detection: countries, browsers, languages, connection types, operators, and search engines.

## Key Files
| File | Description |
| --- | --- |
| `bot-signatures.ts` | Known signatures used by bot detection. |
| `countries.ts` | Country metadata used across targeting and macros. |
| `browsers.ts` | Browser lookup data. |
| `languages.ts` | Language metadata. |
| `index.ts` | Barrel exports for static datasets. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Prefer additive edits and keep data normalized for predictable lookups.
- If a dataset grows substantially, document its source or refresh path in comments or docs.

### Testing Requirements
- Run the consuming filter/macro flow to verify lookup keys still match runtime expectations.

### Common Patterns
- Static data modules with barrel exports.

## Dependencies

### Internal
- `src/lib/tds/filters/`
- `src/lib/tds/macros/`
- `src/lib/tds/bot-detection.ts`

### External
- None beyond TypeScript runtime

<!-- MANUAL: Add directory-specific notes below this line. -->
