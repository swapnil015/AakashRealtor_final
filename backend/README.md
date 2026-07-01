# Aakash Realtor — API (Laravel 11 + PostgreSQL)

Real-estate marketplace REST API. JSON-only, versioned under `/api/v1`, Sanctum
token auth, Filament admin panel, media on Cloudinary/S3.

## Requirements

- PHP **8.2+** with extensions: `pdo_pgsql`, `mbstring`, `openssl`, `gd`, `fileinfo`, `curl`, `zip`
- Composer 2
- PostgreSQL 14+
- Redis (optional in dev; recommended in prod for cache + queues)

## Quick start

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# create the database, then:
php artisan migrate --seed
php artisan storage:link

php artisan serve            # http://localhost:8000
php artisan queue:work       # background jobs (matcher, mail, image processing)
```

> **Note:** enable `extension=pdo_pgsql` in your `php.ini` if migrations report
> "could not find driver".

## API contract

Every endpoint returns the same envelope:

```jsonc
{
  "success": true,
  "data": { /* resource or array */ },
  "message": "OK",
  "meta": { "pagination": { "current_page": 1, "per_page": 12, "total": 240, "last_page": 20, "has_more": true } },
  "errors": { "field": ["message"] }   // 422 only
}
```

Status codes: `422` validation · `401` unauthenticated · `403` forbidden ·
`404` not found · `429` throttled · `500` server error — all in the envelope.

## Folder structure

```
backend/
├── app/
│   ├── Exceptions/
│   ├── Filters/                 # PropertyFilter — testable query builder
│   ├── Http/
│   │   ├── Controllers/Api/V1/  # versioned API controllers
│   │   ├── Middleware/          # ForceJson, role, active, honeypot
│   │   ├── Requests/            # FormRequest validation
│   │   └── Resources/           # API Resources (clean JSON)
│   ├── Jobs/                    # MatchRequirementsToProperty, image jobs
│   ├── Mail/                    # queued mailables
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/                # ownership / role authorization
│   ├── Providers/               # App, Auth, Route (rate limiters)
│   ├── Services/                # MediaService, WhatsAppService, EMI, units, dates
│   └── Support/                 # ApiResponse envelope factory
├── bootstrap/
│   ├── app.php                  # routing + middleware + JSON exception handler
│   └── providers.php
├── config/                      # app, auth, cors, database, sanctum, filesystems, services
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── routes/
│   ├── api.php                  # /api/v1 route group
│   └── console.php
├── tests/{Feature,Unit}/
├── public/index.php
├── artisan
├── composer.json
└── .env.example
```

## Configuration map

| Concern        | File                         | Driven by env |
|----------------|------------------------------|---------------|
| Database       | `config/database.php`        | `DB_*`        |
| Auth tokens    | `config/sanctum.php`         | `SANCTUM_*`   |
| CORS           | `config/cors.php`            | `CORS_ALLOWED_ORIGINS` |
| Media disk     | `config/filesystems.php`     | `MEDIA_DISK`, `CLOUDINARY_*`, `AWS_*` |
| Integrations   | `config/services.php`        | `WHATSAPP_*`, `GOOGLE_*` |
| Rate limits    | `app/Providers/RouteServiceProvider.php` | `AUTH_RATE_LIMIT` |
