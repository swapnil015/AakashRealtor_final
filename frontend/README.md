# Aakash Realtor — Web (Nuxt 3)

Premium, SSR real-estate marketplace frontend. Consumes the Laravel API at
`/api/v1`, gold/serif editorial design, fully SEO-optimized.

## Requirements

- Node **20+** (22 recommended)
- The backend API running (see `../backend`)

## Quick start

```bash
cd frontend
npm install
cp .env.example .env          # point NUXT_PUBLIC_API_BASE at your API
npm run dev                   # http://localhost:3000
```

Production:

```bash
npm run build
node .output/server/index.mjs # or: npm run start
```

## Stack

- **Nuxt 3 (SSR)** — listing & detail pages server-rendered for SEO
- **Tailwind CSS** — brand tokens in `tailwind.config.ts` (gold `#C9A227`)
- **Pinia** (+ persisted cookie) — auth store with token
- `@nuxtjs/sitemap` — dynamic `sitemap.xml` from live properties

## Structure

```
frontend/
├── assets/css/main.css        # Tailwind layer + component classes
├── components/                # AppHeader, AppFooter, PropertyCard, FilterSidebar,
│                              # Pagination, Reveal, WhatsAppButton
├── composables/useApi.ts      # envelope-unwrapping API client + ApiError
├── stores/auth.ts             # Pinia auth (login/register/me/logout)
├── middleware/auth.ts         # route guard for protected pages
├── plugins/                   # auth.client, analytics.client (GTM/GA)
├── layouts/default.vue
├── pages/
│   ├── index.vue              # home (hero search + sections + FAQ)
│   ├── [segment]/[[city]].vue # /buyHouse/Kathmandu-53 listing page
│   ├── property/[slug].vue    # detail + inquiry + schema.org JSON-LD
│   ├── post.vue               # multi-step post property (auth)
│   ├── dashboard.vue          # my listings (auth)
│   ├── login/register/forgot-password/reset-password
│   ├── requirements.vue
│   ├── exclusive.vue, about, contact, branches, team, blogs, blog/[slug], faqs
│   └── tools/{emi,land-converter,date-converter}.vue
├── server/
│   ├── api/__sitemap__/urls.ts  # dynamic sitemap source
│   └── routes/robots.txt.ts
├── types/index.ts             # mirrors the API resources
└── tests/{unit,e2e}/
```

## Routing

| URL pattern                       | Page                         |
|-----------------------------------|------------------------------|
| `/`                               | Home                         |
| `/buyHouse/Kathmandu-53`          | Listing (txn+category / city)|
| `/rentApartment`                  | Listing (no city)            |
| `/property/{slug}`                | Property detail              |
| `/tools/emi`                      | EMI calculator               |

`{segment}` = `{buy|rent}{CategoryName}` (e.g. `buyHouse`); `{city}` = `{Name}-{public_id}`.

## SEO

- SSR on all public pages, per-page `useSeoMeta` (templated "Buy House in Kathmandu")
- `schema.org/RealEstateListing` + `Offer` JSON-LD on property pages
- Dynamic `/sitemap.xml` (every active property + landing pages) and `/robots.txt`

## Tests

```bash
npx vitest run        # component tests
npx playwright test   # e2e (needs app + seeded backend running)
```
