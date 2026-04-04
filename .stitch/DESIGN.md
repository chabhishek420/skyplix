# Design System: SkyPlix TDS Admin Dashboard
**Project ID:** 12633691151817062447

## 1. Visual Theme & Atmosphere
**"The Architectural Ledger."**
A high-precision, data-first instrument that treats the user interface as a silent supporting layer. The atmosphere is clinical yet professional, favoring **High-Density Minimalism**. We achieve a premium feel through intentional asymmetry in sidebars, hyper-tight typographic scales, and a commitment to tonal layering over structural lines. No glassmorphism, no neon glows.

## 2. Color Palette & Roles
- **Canvas White (#F8FAFC)** — Primary foundation background background (Slate-50).
- **Pure Surface (#FFFFFF)** — Card, sidebar, and container fill. Transition from Canvas to Pure Surface defines boundaries without borders.
- **Precision Blue (#2563EB)** — Primary brand color for high-intent actions, active navigation states, and focus rings.
- **Charcoal Ink (#1E293B)** — Primary text (Slate-800).
- **Muted Steel (#64748B)** — Secondary text, descriptions, and metadata (Slate-500).
- **Whisper Border (#E2E8F0)** — interior dividers and interior layout lines (Slate-200).
- **Emerald Signal (#10B981)** — Success states, active status badges, positive trend indicators.
- **Rose Alert (#EF4444)** — Danger states, paused status badges, negative trend indicators.
- **Amber Warning (#F59E0B)** — Auxiliary costs or warning states in charts.

## 3. Typography Rules
- **Display/Headlines:** Geist or Inter — Track-tight, controlled scale, weight-driven hierarchy.
- **Body Data:** Geist or Inter — 13px base for high-density data rows. Line-height 1.25.
- **Metric Monospace:** All numerical data, timestamps, and IP addresses must use a Monospaced variant (e.g., Geist Mono or JetBrains Mono). Tabular numerals for perfect vertical alignment.
- **Labels:** 11px Uppercase with +0.05em letter-spacing. Metadata feel.

## 4. Component Stylings
* **Buttons:** Flat, 4px rounded-md. Primary: Precision Blue with white text. Secondary: Surface Container (#E7EEFF) with Precision Blue text. No outer glow.
* **Cards:** 4px rounded-md. Defined by 1px "Whisper Border" (#E2E8F0) and whisper-soft shadow (0 1px 3px rgba(30, 41, 59, 0.05)).
* **KPI Row:** Cards with a 3px colored top border accent corresponding to the metric importance (Blue/Emerald/Slate).
* **Data Tables:** 13px text, zebra striping (alternate rows bg-slate-50/50). On hover, shift to bg-slate-100. Sticky headers with border-b.
* **Status Chips/Badges:** 10% opacity backgrounds of the signal color (e.g., Emerald Signal at 10% for "Active"). Text is the 100% opaque signal color. 11px Uppercase.

## 5. Layout Principles
- **Cockpit Density (9/10):** Maximize data visibility. Reduce cell padding, minimize whitespace while maintaining alignment.
- **Asymmetric Sidebar:** 256px wider sidebar with 10px uppercase tracking section labels.
- **No-Line Rule:** Prohibit 1px solid borders for sectioning layouts where tonal layering (White on Slate-50) is sufficient.
- **Responsive:** Strict single-column collapse below 768px. Mobile-first.

## 6. Motion & Interaction
- **Spring Physics:** Stiffness: 100, Damping: 20 for interactive elements.
- **Tactile Feedback:** -1px vertical translate on active button state.
- **Hardware Acceleration:** Animate via `transform` and `opacity` only.

## 7. Anti-Patterns (Banned)
- No emojis.
- No Inter (unless Geist is unavailable, then use Inter with caution).
- No pure black (#000000).
- No neon/outer glow shadows.
- No 3-column equal card feature sections (use asymmetric grids).
- No "Elevate", "Seamless", "Unleash" or similar AI copywriting clichés.
- No placeholder data numbers like "99.9%" — use real metrics or `[metric]` labels.
