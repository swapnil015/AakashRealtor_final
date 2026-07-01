#!/usr/bin/env bash
# ═════════════════════════════════════════════════════════════════════════════
#  deploy.sh — Aakash Realtor zero-downtime-ish production deploy
#
#  Flow:
#    1. Pull latest code (git).
#    2. Build new images (api, nuxt, worker share the api image).
#    3. Start/refresh stateful services (postgres, redis) and wait healthy.
#    4. Run DB migrations with --force (no interactive prompt).
#    5. Cache config/routes/views + storage:link (warm the new code).
#    6. Recreate app containers (api, web, nuxt, worker) — web waits for api.
#    7. Restart queue workers so they pick up the NEW code (avoids stale jobs).
#    8. Health-check /up (Laravel) and /api/v1 + the Nuxt site.
#
#  "zero-downtime-ish": compose recreates containers quickly; the host nginx
#  keeps serving the old container until the new one is healthy (depends_on +
#  healthchecks). For true zero-downtime use a blue/green or rolling setup
#  (documented in RUNBOOK.md → Scaling).
#
#  Usage:
#    cd deploy && ./deploy.sh                 # full deploy
#    ./deploy.sh --skip-build                 # config/migrate only
#    ./deploy.sh --no-pull                    # deploy current checkout
# ═════════════════════════════════════════════════════════════════════════════

set -Eeuo pipefail

# ── Resolve paths so the script works from anywhere ──────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$SCRIPT_DIR"   # docker-compose.yml lives here

COMPOSE="docker compose"          # falls back below if only docker-compose v1 exists
command -v docker >/dev/null || { echo "docker not found"; exit 1; }
$COMPOSE version >/dev/null 2>&1 || COMPOSE="docker-compose"

# ── Flags ────────────────────────────────────────────────────────────────────
SKIP_BUILD=0
DO_PULL=1
for arg in "$@"; do
  case "$arg" in
    --skip-build) SKIP_BUILD=1 ;;
    --no-pull)    DO_PULL=0 ;;
    *) echo "Unknown flag: $arg"; exit 2 ;;
  esac
done

# Domains for the post-deploy smoke test (override via env).
API_HEALTH_URL="${API_HEALTH_URL:-https://api.aakashrealtor.com/up}"
API_V1_URL="${API_V1_URL:-https://api.aakashrealtor.com/api/v1}"
SITE_URL="${SITE_URL:-https://aakashrealtor.com}"

log()  { printf '\033[1;34m[deploy]\033[0m %s\n' "$*"; }
fail() { printf '\033[1;31m[deploy:ERROR]\033[0m %s\n' "$*" >&2; exit 1; }

# Helper: run an artisan command inside a one-off api container.
artisan() { $COMPOSE run --rm --no-deps api php artisan "$@"; }

# ── 0. Preflight ─────────────────────────────────────────────────────────────
[ -f "./env/api.env.production" ]  || fail "Missing env/api.env.production (copy from .example)"
[ -f "./env/nuxt.env.production" ] || fail "Missing env/nuxt.env.production (copy from .example)"

# ── 1. Pull code ─────────────────────────────────────────────────────────────
if [ "$DO_PULL" -eq 1 ]; then
  log "Pulling latest code…"
  git -C "$REPO_ROOT" pull --ff-only
fi

# ── 2. Build images ──────────────────────────────────────────────────────────
if [ "$SKIP_BUILD" -eq 0 ]; then
  log "Building images (api, nuxt)…"
  $COMPOSE build --pull
fi

# ── 3. Stateful services up + healthy ────────────────────────────────────────
log "Starting postgres + redis…"
$COMPOSE up -d postgres redis
log "Waiting for postgres to be healthy…"
for i in $(seq 1 30); do
  state="$($COMPOSE ps --format '{{.Health}}' postgres 2>/dev/null || true)"
  [ "$state" = "healthy" ] && break
  sleep 2
  [ "$i" -eq 30 ] && fail "postgres did not become healthy"
done

# ── 4. Migrations (forced, non-interactive) ──────────────────────────────────
log "Running database migrations…"
artisan migrate --force

# ── 5. Warm the framework caches on the new code ─────────────────────────────
log "Optimizing (config/route/view cache, storage:link)…"
artisan storage:link || true        # idempotent; ignore "already exists"
artisan config:cache
artisan route:cache
artisan view:cache
artisan optimize                     # combines the above + more (Laravel 11)
# Clear any stale Redis app cache so new config takes effect.
artisan cache:clear || true

# ── 6. Recreate app containers ───────────────────────────────────────────────
log "Recreating app containers (api, web, nuxt)…"
$COMPOSE up -d --remove-orphans api web nuxt

# ── 7. Restart workers so they load NEW code ─────────────────────────────────
# `queue:restart` signals running workers to gracefully die after the current
# job; supervisord then respawns them on the new image. We also recreate the
# worker container to guarantee the new image is in use.
log "Restarting queue workers…"
artisan queue:restart || true
$COMPOSE up -d worker

# ── 8. Smoke test ────────────────────────────────────────────────────────────
check() {
  local url="$1" name="$2"
  log "Health check: $name ($url)"
  for i in $(seq 1 15); do
    code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$url" || echo 000)"
    if [ "$code" = "200" ] || [ "$code" = "204" ]; then
      log "  ✓ $name responded $code"; return 0
    fi
    sleep 3
  done
  fail "$name health check failed (last HTTP $code)"
}

check "$API_HEALTH_URL" "Laravel /up"
check "$API_V1_URL"     "API /api/v1"
check "$SITE_URL"       "Nuxt site"

# Prune dangling images from old builds to reclaim disk.
log "Pruning old images…"
docker image prune -f >/dev/null || true

log "✅ Deploy complete."
