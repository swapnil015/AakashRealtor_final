# Aakash Realtor — Real-Estate Marketplace

A full-stack property marketplace for Nepal (buy / sell / rent across house, land,
flat, apartment, commercial, residential). Built as a monorepo.

| Part        | Stack                                              | Path         |
|-------------|----------------------------------------------------|--------------|
| **API**     | Laravel 11 · PostgreSQL · Sanctum · Filament       | `backend/`   |
| **Web**     | Nuxt 3 (SSR) · Tailwind · Pinia                     | `frontend/`  |
| **Mobile**  | Flutter (same REST API)                             | `mobile/`    |
| **Deploy**  | Docker · nginx · Postgres · Redis · runbook         | `deploy/`    |
| **Docs**    | ER diagram, schema                                  | `docs/`      |

Brand: gold **#C9A227** on near-black ink, Cormorant Garamond + Manrope.

---

## Core domain model

- Every listing has a **`transaction_type`** (`buy`/`rent`) and a **`category`**
  (house/land/flat/apartment/commercial/residential).
- Locations are **cities** (each with a `public_id` used in URLs, e.g. `Kathmandu-53`)
  and optional **areas**.
- Moderation lifecycle: **`pending → active → sold/rented`** (or `rejected`).
- Homepage flags: `is_featured`, `is_exclusive`, `is_emerging`, `is_open_house`, `is_by_owner`.
- Roles: **`user`**, **`agent`**, **`admin`**.

## API contract (every endpoint)

```jsonc
{ "success": true, "data": {…}, "message": "OK",
  "meta": { "pagination": {…} }, "errors": { "field": ["…"] } }
```

Base URL: `/api/v1`. Auth: Sanctum bearer tokens. Full route list in
`backend/routes/api.php`.

---

## Run it locally

### 1. Backend
```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate
# create a PostgreSQL db "aakash_realtor", enable pdo_pgsql, set DB_* in .env
php artisan migrate --seed
php artisan storage:link
php artisan serve            # http://localhost:8000
php artisan queue:work       # background jobs (matcher, mail, images)
```
Admin panel: **http://localhost:8000/admin** — `admin@aakashrealtor.com` / `password`.

### 2. Frontend
```bash
cd frontend
npm install
cp .env.example .env         # NUXT_PUBLIC_API_BASE=http://localhost:8000/api/v1
npm run dev                  # http://localhost:3000
```

### 3. Mobile / Deploy
See `mobile/README.md` and `deploy/RUNBOOK.md`.

---

## Department build status

| # | Department                         | Status | Where |
|---|------------------------------------|--------|-------|
| 1 | Project setup & architecture       | ✅ | `backend/` (bootstrap, config, ApiResponse envelope, middleware) |
| 2 | Database & schema                  | ✅ | `backend/database/migrations`, `database/schema.sql`, `docs/ER-diagram.md` |
| 3 | Properties API (filter engine)     | ✅ | `PropertyController`, `Filters/PropertyFilter`, Resources |
| 4 | Auth & authorization               | ✅ | `AuthController`, Sanctum, Policies, role middleware, rate limits |
| 5 | Leads (inquiries & requirements)   | ✅ | `InquiryController`, `RequirementController`, `Jobs/MatchRequirementsToProperty` |
| 6 | Tools (EMI, land units, BS dates)  | ✅ | `Services/Tools/*`, `ToolsController`, Nuxt `pages/tools/*` |
| 7 | Media / image handling             | ✅ | `Services/MediaService`, `PropertyImageController` (variants + webp) |
| 8 | Admin panel (Filament)             | ✅ | `backend/app/Filament/*` (11 resources, 3 widgets, approval flow) |
| 9 | Frontend shell (Nuxt)              | ✅ | `useApi`, `stores/auth`, header/footer, shared components |
| 10| Frontend pages                     | ✅ | `frontend/pages/*` (home, listing, detail, post, auth, tools, content) |
| 11| Mobile app (Flutter)               | ✅ | `mobile/` (48 files: screens, models, services, providers, push) |
| 12| Notifications & integrations       | ✅ | Notifications, `WhatsAppService`, GTM/GA plugin |
| 13| Search & SEO                       | ✅ | tsvector full-text, SSR, JSON-LD, dynamic sitemap + robots |
| 14| Testing & QA                       | ✅ | Pest (filters/auth/tools), vitest, Playwright e2e, GitHub Actions CI |
| 15| Deployment / DevOps                | ✅ | `deploy/` (Docker, compose, nginx, backups, RUNBOOK) |

### Notes / follow-ups
- **`{{DOMAIN}}`** is `aakashrealtor.com` as a placeholder — swap via `.env`
  (`CORS_ALLOWED_ORIGINS`, `FRONTEND_URL`, `NUXT_PUBLIC_*`) when hosting is chosen.
- `pdo_pgsql` must be enabled in `php.ini` before migrating (tests run on sqlite).
- Wire `ListingStatusChanged` notification into the Filament approve/reject actions
  to email owners on moderation (class is ready in `app/Notifications`).
- All 120 backend PHP files pass `php -l`. Run `composer install` then
  `php artisan test` for the full suite.
