# Technology Stack

**Analysis Date:** 2026-03-31

## Languages

**Primary:**
- TypeScript 5.x (strict mode) — All src/ code, ~28,019 lines across 203 files

**Secondary:**
- CSS — globals.css (Tailwind CSS 4 with CSS variables)
- Prisma Schema DSL — prisma/schema.prisma (693 lines, 22 models)

## Runtime
- Bun 1.3.10 — Package manager & runtime
- Node.js v24.13.0 — Available in environment
- Package Manager: Bun
- Lockfile: bun.lock (present)

## Frameworks

**Core:**
- Next.js ^16.1.1 — App Router framework (standalone output)
- React ^19.0.0 — UI library
- Prisma ^6.11.1 — ORM (SQLite provider)

**UI/Styling:**
- Tailwind CSS 4 — Utility-first CSS (@tailwindcss/postcss plugin)
- shadcn/ui (New York variant) — Component library (26+ Radix UI primitives)
- tailwindcss-animate ^1.0.7 — Animation utilities
- tw-animate-css ^1.3.5 — CSS animation library
- Lucide React ^0.525.0 — Icon library
- Recharts ^2.15.4 — Charting library
- class-variance-authority ^0.7.1 — Variant styling
- clsx ^2.1.1 + tailwind-merge ^3.3.1 — Class name utilities

**State Management:**
- Zustand ^5.0.6 — Client state (installed, no active imports in src/)
- TanStack React Query ^5.82.0 — Server state (installed, no active imports in src/)
- TanStack React Table ^8.21.3 — Table component (installed, no active imports in src/)

**Forms & Validation:**
- React Hook Form ^7.60.0 — Form management
- @hookform/resolvers ^5.1.1 — Form validation resolvers
- Zod ^4.0.2 — Schema validation

**Animation:**
- Framer Motion ^12.23.2 — Animations (installed, no active imports in src/)

**Testing:**
- None — No test framework, no test/spec files detected

## Key Dependencies (67 runtime)

| Package | Version | Purpose |
|---------|---------|---------|
| next | ^16.1.1 | App Router framework |
| react | ^19.0.0 | UI library |
| react-dom | ^19.0.0 | React DOM renderer |
| @prisma/client | ^6.11.1 | Prisma ORM client |
| prisma | ^6.11.1 | Prisma CLI (also runtime) |
| next-auth | ^4.24.11 | Auth library (installed, not actively used; custom auth in use) |
| z-ai-web-dev-sdk | ^0.0.17 | AI SDK for chat, image, vision, TTS, ASR, search, video |
| zustand | ^5.0.6 | Client state management |
| @tanstack/react-query | ^5.82.0 | Server state management |
| @tanstack/react-table | ^8.21.3 | Headless table component |
| framer-motion | ^12.23.2 | Animation library |
| recharts | ^2.15.4 | Charting library |
| lucide-react | ^0.525.0 | Icon library |
| react-hook-form | ^7.60.0 | Form management |
| @hookform/resolvers | ^5.1.1 | Form validation bridge |
| zod | ^4.0.2 | Schema validation |
| date-fns | ^4.1.0 | Date utilities |
| next-intl | ^4.3.4 | Internationalization (installed, no active imports) |
| next-themes | ^0.4.6 | Dark/light theme toggle |
| sonner | ^2.0.6 | Toast notifications |
| cmdk | ^1.1.1 | Command menu (cmd+K) |
| vaul | ^1.1.2 | Drawer component |
| input-otp | ^1.4.2 | OTP input component |
| embla-carousel-react | ^8.6.0 | Carousel component |
| react-day-picker | ^9.8.0 | Date picker |
| react-resizable-panels | ^3.0.3 | Resizable panel layout |
| react-markdown | ^10.1.0 | Markdown rendering |
| @mdxeditor/editor | ^3.39.1 | MDX editor |
| react-syntax-highlighter | ^15.6.1 | Code syntax highlighting |
| sharp | ^0.34.3 | Image processing |
| bcrypt | ^6.0.0 | Password hashing |
| uuid | ^11.1.0 | UUID generation |
| class-variance-authority | ^0.7.1 | Component variant styling |
| clsx | ^2.1.1 | Conditional class names |
| tailwind-merge | ^3.3.1 | Tailwind class merging |
| tailwindcss-animate | ^1.0.7 | Tailwind animation plugin |
| @radix-ui/react-accordion | ^1.2.11 | Accordion primitive |
| @radix-ui/react-alert-dialog | ^1.1.14 | Alert dialog primitive |
| @radix-ui/react-aspect-ratio | ^1.1.7 | Aspect ratio primitive |
| @radix-ui/react-avatar | ^1.1.10 | Avatar primitive |
| @radix-ui/react-checkbox | ^1.3.2 | Checkbox primitive |
| @radix-ui/react-collapsible | ^1.1.11 | Collapsible primitive |
| @radix-ui/react-context-menu | ^2.2.15 | Context menu primitive |
| @radix-ui/react-dialog | ^1.1.14 | Dialog primitive |
| @radix-ui/react-dropdown-menu | ^2.1.15 | Dropdown menu primitive |
| @radix-ui/react-hover-card | ^1.1.14 | Hover card primitive |
| @radix-ui/react-label | ^2.1.7 | Label primitive |
| @radix-ui/react-menubar | ^1.1.15 | Menubar primitive |
| @radix-ui/react-navigation-menu | ^1.2.13 | Navigation menu primitive |
| @radix-ui/react-popover | ^1.1.14 | Popover primitive |
| @radix-ui/react-progress | ^1.1.7 | Progress primitive |
| @radix-ui/react-radio-group | ^1.3.7 | Radio group primitive |
| @radix-ui/react-scroll-area | ^1.2.9 | Scroll area primitive |
| @radix-ui/react-select | ^2.2.5 | Select primitive |
| @radix-ui/react-separator | ^1.1.7 | Separator primitive |
| @radix-ui/react-slider | ^1.3.5 | Slider primitive |
| @radix-ui/react-slot | ^1.2.3 | Slot primitive |
| @radix-ui/react-switch | ^1.2.5 | Switch primitive |
| @radix-ui/react-tabs | ^1.1.12 | Tabs primitive |
| @radix-ui/react-toast | ^1.2.14 | Toast primitive |
| @radix-ui/react-toggle | ^1.1.9 | Toggle primitive |
| @radix-ui/react-toggle-group | ^1.1.10 | Toggle group primitive |
| @radix-ui/react-tooltip | ^1.2.7 | Tooltip primitive |
| @dnd-kit/core | ^6.3.1 | Drag and drop core |
| @dnd-kit/sortable | ^10.0.0 | Sortable DnD |
| @dnd-kit/utilities | ^3.2.2 | DnD utilities |
| @reactuses/core | ^6.0.5 | React hooks collection |

## Configuration Files

| File | Purpose |
|------|---------|
| package.json | Project manifest, scripts, dependencies |
| tsconfig.json | TypeScript config (ES2017 target, strict, bundler resolution) |
| next.config.ts | Next.js config (standalone output, no strict mode) |
| tailwind.config.ts | Tailwind CSS config (dark mode, shadcn/ui theme tokens) |
| postcss.config.mjs | PostCSS config (@tailwindcss/postcss plugin) |
| eslint.config.mjs | ESLint flat config (Next.js core-web-vitals + TypeScript) |
| components.json | shadcn/ui config (New York style, RSC, CSS variables) |
| .env.example | Environment variable template |
| prisma/schema.prisma | Database schema (22 models, SQLite) |
| CLAUDE.md | Claude Code session config & project conventions |

## Platform Requirements
- Dev: Port 3000 only (Caddy gateway routes external traffic to port 3000)
- DB: SQLite at file:./db/custom.db (via DATABASE_URL env var)
- No build step required — dev server only (`bun run dev`)
- Standalone output configured for potential production deployment
