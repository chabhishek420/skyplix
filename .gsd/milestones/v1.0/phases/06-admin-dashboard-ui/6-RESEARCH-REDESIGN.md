---
phase: 6
level: 2
researched_at: 2026-04-04
---

# Phase 6 Research: "Original Clean White" Redesign

## Questions Investigated
1. What are the specific color hex codes and spacing patterns of the "Original" Keitaro/Prosper202 dashboard?
2. How to implement a high-density "Data-First" layout using Tailwind v4 and React components?
3. What visual components (Badges, Status indicators) are essential for "Original" functional parity?

## Findings

### Color & Aesthetic (The Clean White Palette)
Based on `demo.keitaro.io` and `prosper202/202-css/design-system.css`:
- **Background**: `#f8fafc` (Slate-50) or `#fcfdfe`. A subtle blue-tinted white that makes pure white cards pop.
- **Card Surfaces**: Pure `#ffffff` with a very soft shadow: `box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.06)`.
- **Primary Labels**: `#1e293b` (Slate-800) for high readability.
- **Success/Danger**: Emerald-500 and Rose-500. Not too neon; professional tones.
- **Borders**: `#e2e8f0` (Slate-200) or `#f1f5f9` (Slate-100). Essential for defining the grid without visual noise.

### Functional Density (Data-First UX)
The original UIs prioritize information over whitespace.
- **Typography**: Inter (modern) or Roboto. Standard size: `13px` or `14px` for table content. `12px` for badges.
- **Table Row Height**: Compact (`py-2` or `h-10`) to fit more campaigns on screen.
- **Zebra Striping**: Alternating row color (`bg-slate-50/50`) helps eye tracking across wide tables.
- **Status Badges**: Small, rounded (full or lg), uppercase text for binary states (ACTIVE, PAUSED, BOT).

### Layout Patterns
- **Sidebar**: Pure white background, `w-64` width, persistent. Active links use a left-border indicator and a subtle background fill.
- **Tabs**: Simple horizontal tabs with an underline accent for active states.
- **KPI Cards**: Minimalist. Number first, then label. No large illustrations; tiny sparklines if needed.

## Decisions Made
| Decision | Choice | Rationale |
|----------|--------|-----------|
| Background | Slate-50 Tint | Reduces eye fatigue while keeping the "Clean White" look |
| Table Style | Bordered Semi-Compact | High-load traffic monitoring requires maximum data visibility |
| Icons | Lucide React (Clean) | Modern, consistent, and easy to recolor for light/dark motifs |
| Transitions | Rapid (150ms) | Keeps the UI feeling "snappy" and less "lovey-dovey" |

## Dependencies Identified
- Use `@theme` in `index.css` for Tailwind v4 to manage these new variable overrides.
- No new external libraries needed; core React 19 + shadcn is sufficient for the overhaul.

## Ready for Planning
- [x] Aesthetics defined
- [x] Spacing paradigm set
- [x] Component mapping complete
