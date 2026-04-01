# Keitaro TDS TypeScript Translation

A complete Traffic Distribution System (TDS) translated from Keitaro PHP to TypeScript/Next.js.

## Quick Start

```bash
# Install dependencies
bun install

# Initialize database
bun run db:push

# Start development server
bun run dev
```

## Project Structure

```
/home/z/my-project/
├── src/                    # Main Next.js application
│   ├── app/                # App router pages & APIs
│   │   ├── api/            # API endpoints
│   │   │   ├── click/      # Click processing
│   │   │   ├── postback/   # Conversion tracking
│   │   │   └── admin/      # Admin APIs
│   │   ├── page.tsx        # Home page
│   │   └── layout.tsx      # Root layout
│   ├── components/         # UI components (shadcn/ui)
│   ├── hooks/              # React hooks
│   └── lib/                # Business logic
│       ├── tds/            # TDS core library
│       │   ├── actions/    # Action types (18)
│       │   ├── filters/    # Stream filters (24)
│       │   ├── macros/     # Macro system (55+)
│       │   ├── pipeline/   # Pipeline stages (24)
│       │   ├── services/   # Services (6)
│       │   └── utils/      # Utilities
│       ├── auth/           # Authentication
│       └── db.ts           # Database client
│
├── prisma/                 # Database schema
├── public/                 # Static assets
├── db/                     # Database files
│
├── docs/                   # Documentation
│   ├── reference/          # Reference materials
│   │   ├── keitaro_source/ # Original PHP code
│   │   ├── osint/          # Research data
│   │   └── tds-rebuild/    # PRD & specs
│   └── *.md                # Status & reports
│
├── skills/                 # AI skills (do not modify)
├── examples/               # Example implementations
└── mini-services/          # Auxiliary services
```

## API Endpoints

### Traffic Endpoints
- `GET /api/click` - Process incoming clicks
- `POST /api/click` - Process incoming clicks
- `GET /api/click/json` - JSON API for clicks
- `POST /api/postback` - Handle conversion postbacks
- `POST /api/lp/offer` - LP → Offer flow

### Admin Endpoints
- `GET/POST /api/admin/stats` - Dashboard statistics
- `GET/POST/PUT/DELETE /api/admin/campaigns` - Campaign CRUD
- `GET/POST/PUT/DELETE /api/admin/streams` - Stream CRUD
- `GET/POST/PUT/DELETE /api/admin/offers` - Offer CRUD
- `GET/POST/PUT/DELETE /api/admin/landings` - Landing CRUD
- `GET/POST/PUT/DELETE /api/admin/publishers` - Publisher CRUD
- `GET/POST/PUT/DELETE /api/admin/domains` - Domain CRUD
- `GET/POST /api/admin/clicks` - Click viewing
- `GET/POST /api/admin/conversions` - Conversion viewing
- `GET/POST/PUT/DELETE /api/admin/bot-rules` - Bot detection rules
- `GET/POST/PUT/DELETE /api/admin/settings` - System settings
- `GET/POST/PUT/DELETE /api/admin/users` - User management
- `GET/POST /api/admin/reports` - Analytics reports

## TDS Features

### Pipeline Stages (24)
1. DomainRedirect → CheckPrefetch → BuildRawClick → CheckBot
2. FindCampaign → CheckDefaultCampaign → UpdateRawClick
3. CheckParamAliases → UpdateCampaignUniqueness → ChooseStream
4. UpdateStreamUniqueness → ChooseLanding → ChooseOffer
5. GenerateToken → FindAffiliateNetwork → UpdateHitLimit
6. UpdateCosts → UpdatePayout → SaveUniquenessSession
7. SetCookie → ExecuteAction → PrepareRawClickToStore
8. CheckSendingToAnotherCampaign → StoreRawClicks

### Bot Detection Methods
- User-Agent analysis (known bot patterns)
- Header analysis (automation indicators)
- IP analysis (datacenter ranges: AWS, GCP, Azure, etc.)
- Referer analysis (suspicious patterns)
- Parameter analysis (debug flags)
- Database BotRule matching

### Action Types (18)
- Redirect: HttpRedirect, Http301, Meta, DoubleMeta, Js, Remote, Curl
- Embed: Iframe, Frame, FormSubmit
- Content: ShowHtml, ShowText
- Status: Status404, DoNothing
- Special: ToCampaign, SubId, BlankReferrer, LocalFile

## Development

```bash
# Run linter
bun run lint

# Database operations
bun run db:push      # Push schema changes
bun run db:generate  # Generate Prisma client
bun run db:migrate   # Run migrations
```

## Translation Status

- Pipeline Stages: 100% (24/24)
- Actions: 100% (18/18)
- Macros: 95% (55+/60+)
- Filters: 100% (24/24)
- Services: 100% (6/6)
- Models: 100% (18/18)
- Admin APIs: 100% (16/16)

Overall: ~98% complete
