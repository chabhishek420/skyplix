<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# predefined

## Purpose
Built-in action implementations that model common Keitaro delivery behaviors such as HTTP redirects, meta refreshes, frames, content responses, remote fetching, and special campaign hand-offs.

## Key Files
| File | Description |
| --- | --- |
| `http-redirect.ts` | HTTP 301/302 redirect implementations. |
| `meta.ts` | Meta refresh action implementations. |
| `iframe.ts` | Iframe/frame-style action implementations. |
| `content.ts` | HTML/text/404/do-nothing content actions. |
| `to-campaign.ts` | Action that forwards flow into another campaign. |

## Subdirectories
None currently documented.

## For AI Agents

### Working In This Directory
- Match the repository keys and payload expectations used by upstream action selection.
- Be explicit about headers/content types when an action stops being a plain redirect.

### Testing Requirements
- Trigger the exact action via a route or pipeline fixture and inspect the final response semantics.

### Common Patterns
- One action family per file, often exporting multiple closely related classes.

## Dependencies

### Internal
- `src/lib/tds/actions/base.ts`
- `src/lib/tds/macros/`

### External
- Standard web response primitives

<!-- MANUAL: Add directory-specific notes below this line. -->
