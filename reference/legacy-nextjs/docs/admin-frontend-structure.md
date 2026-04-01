# Admin Frontend Structure

This document records the empty Next.js admin/frontend scaffold created from the decoded Keitaro PHP reference under `reference/Keitaro_source_php`.

## Route structure

```text
src/app/
├── page.tsx
├── (auth)/
│   └── login/
│       └── page.tsx
└── (admin)/
    └── admin/
        ├── layout.tsx
        ├── page.tsx
        ├── affiliate-networks/
        │   └── page.tsx
        ├── bot-detection/
        │   └── page.tsx
        ├── campaigns/
        │   └── page.tsx
        ├── clicks/
        │   └── page.tsx
        ├── conversions/
        │   └── page.tsx
        ├── diagnostics/
        │   └── page.tsx
        ├── domains/
        │   └── page.tsx
        ├── landings/
        │   └── page.tsx
        ├── offers/
        │   └── page.tsx
        ├── reports/
        │   └── page.tsx
        ├── settings/
        │   └── page.tsx
        ├── streams/
        │   └── page.tsx
        ├── system/
        │   └── page.tsx
        ├── traffic-sources/
        │   └── page.tsx
        ├── trends/
        │   └── page.tsx
        └── users/
            └── page.tsx
```

## Component structure

```text
src/components/admin/
├── dashboard/
│   └── dashboard-overview.tsx
├── layout/
│   ├── admin-content.tsx
│   ├── admin-header.tsx
│   ├── admin-page.tsx
│   ├── admin-shell.tsx
│   └── admin-sidebar.tsx
├── nav/
│   ├── admin-nav-config.ts
│   ├── admin-nav-primary.tsx
│   └── admin-nav-secondary.tsx
└── shared/
    ├── empty-state.tsx
    ├── module-placeholder.tsx
    ├── page-section.tsx
    └── page-title.tsx
```

## Supporting admin files

```text
src/lib/admin/
├── auth.ts
├── js-config.ts
├── module-registry.ts
└── navigation.ts

src/types/admin/
├── config.ts
└── navigation.ts
```

## Notes

- The files above were created as **empty files**.
- The structure is based on the decoded Keitaro admin module layout, not the previous monolithic `src/app/page.tsx` approach.
- Existing backend API routes under `src/app/api/**` were not changed.
