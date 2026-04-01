<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-04-01 | Updated: 2026-04-01 -->

# admin

## Purpose
Admin dashboard route tree. Each child folder maps to a feature page such as campaigns, streams, offers, reports, and system diagnostics.

## Key Files
| File | Description |
| --- | --- |
| `layout.tsx` | Admin layout entry; currently an empty scaffold placeholder. |
| `page.tsx` | Admin landing page; currently an empty scaffold placeholder. |

## Subdirectories
| Directory | Purpose |
| --- | --- |
| `affiliate-networks/` | Affiliate network management screen. |
| `bot-detection/` | Bot-detection administration screen. |
| `campaigns/` | Campaign list and editor screen. |
| `clicks/` | Tracked-click log screen. |
| `conversions/` | Conversion reporting screen. |
| `diagnostics/` | Operational diagnostics screen. |
| `domains/` | Domain management screen. |
| `landings/` | Landing-page management screen. |
| `offers/` | Offer management screen. |
| `reports/` | Aggregated reporting screen. |
| `settings/` | System settings screen. |
| `streams/` | Stream routing management screen. |
| `system/` | System/ops dashboard screen. |
| `traffic-sources/` | Traffic-source configuration screen. |
| `trends/` | Trend-analysis screen. |
| `users/` | Admin user management screen. |

## For AI Agents

### Working In This Directory
- Keep page modules thin and push repeated layout or state concerns into `src/components/admin`.
- When adding a new admin feature page, update both this directory and the navigation registry.

### Testing Requirements
- Verify the target page route renders without hydration or import errors.
- If a page consumes admin APIs, exercise both the page and its matching `app/api/admin/*` route.

### Common Patterns
- Feature-per-folder routing with a single `page.tsx` entry per admin screen.
- Several files are placeholders, so new work may need to establish the first concrete implementation pattern.

## Dependencies

### Internal
- `src/components/admin/`
- `src/app/api/admin/`
- `src/lib/admin/`

### External
- Next.js pages/layouts

<!-- MANUAL: Add directory-specific notes below this line. -->
