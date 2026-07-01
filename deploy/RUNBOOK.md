# Aakash Realtor — Deployment Runbook (Department 15: DevOps)

Production deployment guide for the Aakash Realtor stack.

- **Backend** — Laravel 11 API (PHP 8.2 / php-fpm), Postgres 16, Redis 7, queue
  workers + scheduler.
- **Frontend** — Nuxt 3 SSR (Node 22).
- **Edge** — host nginx terminates TLS and routes the two domains.

```
                 Internet (HTTPS)
                       │
              ┌────────┴─────────┐
   aakashrealtor.com        api.aakashrealtor.com
      (+ www → apex)               │
              │                    │
        host nginx ───────────► host nginx
              │ :3000              │ :8080
              ▼                    ▼
        ┌──────────┐        ┌────────────┐
        │  nuxt    │        │   web      │  (nginx, in-stack)
        │  (SSR)   │        │  → api     │  (php-fpm :9000)
        └──────────┘        └────────────┘
                                   │
                      ┌────────────┼─────────────┐
                      ▼            ▼              ▼
                  postgres      redis         worker
                  (16)          (7)         (supervisord:
                                              queue:work ×N
                                              schedule:work)
```

All services are defined in [`docker-compose.yml`](./docker-compose.yml). The
`api` and `worker` containers share one image (`aakash/api`); `worker` just runs
supervisord instead of php-fpm.

---

## 1. Prerequisites

On the host (Ubuntu 22.04+ recommended):

- Docker Engine 24+ and the Compose v2 plugin (`docker compose version`).
- nginx + certbot on the **host** (TLS terminator). `apt install nginx certbot python3-certbot-nginx`.
- DNS A/AAAA records pointing to the host:
  - `aakashrealtor.com`, `www.aakashrealtor.com` → host IP
  - `api.aakashrealtor.com` → host IP
- Open firewall ports 80 + 443 only (Postgres/Redis stay on the internal docker network).
- A non-root deploy user in the `docker` group.
- Recommended Docker log caps in `/etc/docker/daemon.json`:
  ```json
  { "log-driver": "json-file", "log-opts": { "max-size": "10m", "max-file": "5" } }
  ```

---

## 2. First-time setup

```bash
# 1. Clone to a stable path (paths in crontab assume /opt/aakash).
sudo git clone <repo-url> /opt/aakash
cd /opt/aakash/deploy

# 2. Create the real env files from templates and fill in secrets.
cp env/api.env.production.example  env/api.env.production
cp env/nuxt.env.production.example env/nuxt.env.production
$EDITOR env/api.env.production      # set DB/REDIS/MAIL/Cloudinary/Sentry…
$EDITOR env/nuxt.env.production

# 3. Also export the values compose itself needs (DB_*, REDIS_PASSWORD,
#    NUXT_PUBLIC_API_BASE). Easiest: a deploy/.env file next to the compose file:
cat > .env <<'EOF'
DB_DATABASE=aakash_realtor
DB_USERNAME=aakash
DB_PASSWORD=<same as api.env.production>
REDIS_PASSWORD=<same as api.env.production>
NUXT_PUBLIC_API_BASE=https://api.aakashrealtor.com/api/v1
EOF
chmod 600 .env env/*.env.production

# 4. Generate the Laravel APP_KEY and paste it into api.env.production.
docker compose build api
docker compose run --rm api php artisan key:generate --show
#   → paste "base64:..." into APP_KEY in env/api.env.production
```

### Host nginx + TLS

```bash
# Copy the host server blocks.
sudo cp nginx-host/aakashrealtor.com.conf      /etc/nginx/sites-available/
sudo cp nginx-host/api.aakashrealtor.com.conf  /etc/nginx/sites-available/
sudo mkdir -p /var/www/certbot
# (Temporarily comment out the 443 blocks for the first cert issue, or use the
#  certbot --nginx plugin which edits them in place.)
sudo certbot --nginx -d aakashrealtor.com -d www.aakashrealtor.com
sudo certbot --nginx -d api.aakashrealtor.com
sudo ln -s ../sites-available/aakashrealtor.com.conf     /etc/nginx/sites-enabled/
sudo ln -s ../sites-available/api.aakashrealtor.com.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 3. Build & deploy

```bash
cd /opt/aakash/deploy
chmod +x deploy.sh backup/db-backup.sh
./deploy.sh
```

`deploy.sh` does: git pull → build images → start postgres/redis (wait healthy)
→ `migrate --force` → cache config/routes/views + `storage:link` →
recreate api/web/nuxt → `queue:restart` + recreate worker → smoke-test
`/up`, `/api/v1`, and the Nuxt site → prune old images.

Useful flags: `--skip-build` (config/migrate only), `--no-pull` (deploy current checkout).

First-ever boot (before DNS/TLS is live) — bring the stack up directly:
```bash
docker compose up -d --build
docker compose ps          # all should be "healthy"
docker compose logs -f api
```

---

## 4. Migrations & seeders

```bash
# Migrations run automatically in deploy.sh. Manually:
docker compose run --rm api php artisan migrate --force

# Seed reference data (first deploy only — seeders are usually not idempotent):
docker compose run --rm api php artisan db:seed --force

# Inspect status / roll back the last batch:
docker compose run --rm api php artisan migrate:status
docker compose run --rm api php artisan migrate:rollback --force
```

---

## 5. Scaling workers

Two scaling levers for background jobs (`MatchRequirementsToProperty`, queued
mail/notifications):

```bash
# A) More workers PER container — edit numprocs in docker/supervisord.conf
#    ([program:queue-worker] numprocs=N), then:
docker compose up -d worker

# B) More worker CONTAINERS:
docker compose up -d --scale worker=3 worker
```

**Important — the scheduler must stay singleton.** `schedule:work` runs inside
supervisord with `numprocs=1`. If you scale to multiple `worker` containers,
every container would run its own scheduler → duplicate scheduled jobs. For >1
worker container, either:
- split the scheduler into its own dedicated 1-replica service, **or**
- move the scheduler to host cron (see `backup/crontab.example`, last line) and
  remove `[program:scheduler]` from supervisord.

Scale php-fpm web capacity by raising `pm.max_children` in `docker/php/www.conf`
(and the container's memory), then rebuild/redeploy.

### Toward true zero-downtime
The default flow is "fast recreate". For real rolling deploys: run two API
stacks (blue/green), point host nginx `upstream` at the active one, deploy to the
idle one, run migrations (keep them backward-compatible!), health-check, then
switch the upstream and reload nginx.

---

## 6. Rollback

```bash
# Code rollback: deploy a previous commit/tag.
git -C /opt/aakash checkout <previous-tag>
cd /opt/aakash/deploy && ./deploy.sh --no-pull

# If a migration broke production and is reversible:
docker compose run --rm api php artisan migrate:rollback --force

# If a migration is NOT safely reversible: restore the DB from the nightly dump
# (see §7) and redeploy the matching previous code. This is why backups run
# BEFORE risky releases — take a manual dump first:
./backup/db-backup.sh
```

Always pair an irreversible/destructive migration with a fresh manual backup
immediately before deploying.

---

## 7. Backups & restore

- **Database** — `backup/db-backup.sh` runs nightly via cron (`backup/crontab.example`),
  writes gzipped `pg_dump -Fc` to `/var/backups/aakash/postgres`, keeps 14 days.
- **Media** — see `backup/media-backup.md`. With `MEDIA_DISK=cloudinary` (default)
  no host backup is needed; only the local `public` disk requires archiving.
- **Logs** — rotated by `backup/aakash.logrotate` (host) + Docker log caps (containers).

Install the cron + logrotate:
```bash
sudo mkdir -p /var/log/aakash /var/backups/aakash/postgres
crontab backup/crontab.example
sudo cp backup/aakash.logrotate /etc/logrotate.d/aakash
```

**Restore the database:**
```bash
gunzip -c /var/backups/aakash/postgres/aakash_realtor_<ts>.dump.gz | \
  docker compose exec -T postgres \
    pg_restore -U aakash -d aakash_realtor --clean --if-exists
```

---

## 8. Sentry wiring

### API (Laravel) — `sentry/sentry-laravel`
1. `composer require sentry/sentry-laravel` and publish its config (one-time,
   in the backend repo — outside this department's scope but documented here).
2. Set in `env/api.env.production`:
   ```
   SENTRY_LARAVEL_DSN=https://<key>@<org>.ingest.sentry.io/<project>
   SENTRY_TRACES_SAMPLE_RATE=0.1
   ```
   The `.env.example` already exposes both keys. Empty DSN = disabled.
3. Confirm capture after deploy:
   ```bash
   docker compose run --rm api php artisan sentry:test
   ```
4. The image logs to stderr (`LOG_STACK=stderr`); Sentry is the aggregation
   layer for errors/exceptions on top of the raw container logs.

### Frontend (Nuxt) — `@sentry/nuxt`
1. `npm i @sentry/nuxt` and add the module in `nuxt.config.ts` (frontend repo).
2. Set in `env/nuxt.env.production`:
   ```
   NUXT_PUBLIC_SENTRY_DSN=https://<key>@<org>.ingest.sentry.io/<project>
   NUXT_PUBLIC_SENTRY_ENVIRONMENT=production
   SENTRY_AUTH_TOKEN=<token>   # build-time only, for source-map upload
   SENTRY_ORG=...  SENTRY_PROJECT=aakash-realtor-web
   ```
   Source maps upload during `nuxt build` (the Docker build stage) when the auth
   token is present, so stack traces de-minify in Sentry.

---

## 9. Uptime & health monitoring

Health endpoints:
- `GET https://api.aakashrealtor.com/up` — Laravel framework health (boots app + DB).
- `GET https://api.aakashrealtor.com/healthz` — nginx-only liveness (no PHP).
- `GET https://aakashrealtor.com/` — Nuxt SSR.

Set up external monitoring (any of):
- **UptimeRobot / Better Stack / Pingdom** — HTTP checks every 1–5 min on the
  three URLs above, alert to email/Slack/SMS.
- **Sentry Crons** — wrap scheduled jobs with check-ins to detect a stalled
  scheduler.
- **Container health** — `docker compose ps` shows per-service health; pipe
  `docker events` or a Prometheus `cAdvisor` exporter into Grafana for graphs.

Quick local check after deploy:
```bash
curl -fsS https://api.aakashrealtor.com/up && echo OK
curl -fsS -o /dev/null -w '%{http_code}\n' https://aakashrealtor.com/
docker compose ps
```

---

## 10. Troubleshooting

**`could not find driver` / `pdo_pgsql missing`**
The runtime image must have `pdo_pgsql`. It's installed in `Dockerfile.api`.
Verify: `docker compose run --rm api php -m | grep -i pdo_pgsql`. If missing,
you're running an old image — `docker compose build --no-cache api` and redeploy.

**Queue not processing jobs**
- Workers alive? `docker compose ps worker` (healthcheck greps for `queue:work`)
  and `docker compose logs worker`.
- `QUEUE_CONNECTION=redis` and Redis reachable? `docker compose exec api php artisan tinker --execute="dump(Redis::ping());"`
- Stuck on old code after deploy? `docker compose run --rm api php artisan queue:restart`.
- Inspect failures: `docker compose run --rm api php artisan queue:failed`, retry
  with `queue:retry all`.

**Scheduled tasks not firing**
Exactly one `schedule:work` must run. Check `docker compose logs worker | grep -i schedul`.
If you scaled worker containers, see §5 (singleton scheduler warning).

**CORS errors in the browser**
CORS is owned by Laravel (`config/cors.php` + `CORS_ALLOWED_ORIGINS`), **not**
nginx. Ensure `CORS_ALLOWED_ORIGINS` includes the exact site origins
(`https://aakashrealtor.com`, `https://www.aakashrealtor.com`) and that
`SANCTUM_STATEFUL_DOMAINS` + `SESSION_DOMAIN=.aakashrealtor.com` are set. Do NOT
add `Access-Control-*` headers in nginx — that causes duplicate-header failures.
After changing env: `docker compose up -d api && docker compose run --rm api php artisan config:cache`.

**413 Request Entity Too Large on image upload**
Raise `client_max_body_size` in BOTH the host block (`nginx-host/api.*.conf`)
and the in-stack `docker/nginx/api.conf`, and PHP `upload_max_filesize`/`post_max_size`
in `docker/php/php-prod.ini`. All three are currently aligned at ~32M.

**`500` with blank response / config changes ignored**
Cached config is stale. `docker compose run --rm api php artisan optimize:clear`
then `optimize` (deploy.sh does this). Confirm `APP_DEBUG=false` in prod.

**Postgres/Redis "connection refused" on boot**
App started before the datastore was healthy. compose `depends_on:
service_healthy` plus the healthchecks handle ordering; if you see this, check
`docker compose logs postgres redis` and that `DB_PASSWORD`/`REDIS_PASSWORD`
match between `.env` and `env/api.env.production`.

---

## File index

| Path | Purpose |
|------|---------|
| `docker/Dockerfile.api` | Multi-stage PHP 8.2-fpm image (pdo_pgsql, gd, zip, intl, opcache, redis, supervisor) |
| `docker/Dockerfile.nuxt` | Multi-stage Node 22 → slim SSR runtime |
| `docker/nginx/api.conf` | In-stack nginx → php-fpm |
| `docker/nginx/nuxt.conf` | Optional nginx in front of Nuxt SSR |
| `docker/php/*.ini`, `www.conf` | opcache + prod php + fpm pool tuning |
| `docker/supervisord.conf` | Queue workers + scheduler for the worker container |
| `docker-compose.yml` | Full stack: api, web, nuxt, postgres, redis, worker |
| `env/*.example` | Documented production env templates |
| `deploy.sh` | Build + migrate + cache + restart + health-check |
| `nginx-host/*.conf` | Host TLS terminators for the two domains |
| `backup/db-backup.sh` | Nightly pg_dump + rotation |
| `backup/crontab.example` | Host cron (backup, certbot, prune) |
| `backup/aakash.logrotate` | Host log rotation |
| `backup/media-backup.md` | Media durability per MEDIA_DISK |
