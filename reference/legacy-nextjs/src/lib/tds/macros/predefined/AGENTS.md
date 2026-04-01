<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# predefined

## Purpose
Subject-oriented macro implementations for campaign, stream, geo, referrer, device, network, randomization, tracking, and request metadata.

## Key Files
| File | Description |
| --- | --- |
| `campaign.ts` | Campaign-related macros. |
| `stream.ts` | Stream-related macros. |
| `geo.ts` | Geo-oriented macros. |
| `referrer.ts` | Referrer macros. |
| `tracking.ts` | Tracking/click identifiers and related macros. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Keep macro files grouped by subject area and avoid scattering overlapping names.
- Make sure new macro names remain unique across the registry.

### Testing Requirements
- Exercise a content or redirect path that expands the macro and inspect the rendered value.

### Common Patterns
- Multiple small macro classes/functions grouped by domain topic.

## Dependencies

### Internal
- `src/lib/tds/macros/registry.ts`
- `src/lib/tds/data/`
- `src/lib/tds/services/`

### External
- TypeScript only

<!-- MANUAL: Add directory-specific notes below this line. -->
