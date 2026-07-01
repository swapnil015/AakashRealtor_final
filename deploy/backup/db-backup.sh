#!/usr/bin/env bash
# ═════════════════════════════════════════════════════════════════════════════
#  db-backup.sh — nightly PostgreSQL backup with rotation
#
#  Runs pg_dump against the compose `postgres` service, writes a gzipped custom-
#  format dump, and prunes dumps older than RETENTION_DAYS.
#
#  Schedule via cron (see crontab.example) or systemd timer. Intended to run on
#  the Docker HOST, where it can reach the compose stack.
#
#  Restore (custom format):
#    gunzip -c aakash_realtor_2026-06-30.dump.gz | \
#      docker compose exec -T postgres pg_restore -U "$DB_USERNAME" -d "$DB_DATABASE" --clean --if-exists
# ═════════════════════════════════════════════════════════════════════════════

set -Eeuo pipefail

# ── Config (override via environment) ────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMPOSE_DIR="${COMPOSE_DIR:-$SCRIPT_DIR/..}"          # deploy/ (has docker-compose.yml)
BACKUP_DIR="${BACKUP_DIR:-/var/backups/aakash/postgres}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

# Read DB creds from the api env file so we never duplicate secrets here.
API_ENV="${API_ENV:-$COMPOSE_DIR/env/api.env.production}"
# shellcheck disable=SC1090
if [ -f "$API_ENV" ]; then
  DB_DATABASE="$(grep -E '^DB_DATABASE=' "$API_ENV" | cut -d= -f2-)"
  DB_USERNAME="$(grep -E '^DB_USERNAME=' "$API_ENV" | cut -d= -f2-)"
fi
DB_DATABASE="${DB_DATABASE:-aakash_realtor}"
DB_USERNAME="${DB_USERNAME:-aakash}"

COMPOSE="docker compose"
$COMPOSE version >/dev/null 2>&1 || COMPOSE="docker-compose"

TIMESTAMP="$(date +%F_%H%M%S)"
OUT="$BACKUP_DIR/${DB_DATABASE}_${TIMESTAMP}.dump.gz"

mkdir -p "$BACKUP_DIR"

echo "[db-backup] Dumping $DB_DATABASE → $OUT"
# -Fc = custom format (compressible, supports selective pg_restore).
# --no-owner/--no-acl keep restores portable across roles.
( cd "$COMPOSE_DIR" && $COMPOSE exec -T postgres \
    pg_dump -U "$DB_USERNAME" -d "$DB_DATABASE" -Fc --no-owner --no-acl ) \
  | gzip -9 > "$OUT"

# Verify the dump isn't empty/corrupt.
if [ ! -s "$OUT" ]; then
  echo "[db-backup:ERROR] Backup is empty — removing $OUT" >&2
  rm -f "$OUT"
  exit 1
fi
echo "[db-backup] OK ($(du -h "$OUT" | cut -f1))"

# ── Rotation ─────────────────────────────────────────────────────────────────
echo "[db-backup] Pruning dumps older than ${RETENTION_DAYS} days…"
find "$BACKUP_DIR" -name "${DB_DATABASE}_*.dump.gz" -type f -mtime "+${RETENTION_DAYS}" -print -delete

# ── Optional: ship offsite (uncomment + configure) ───────────────────────────
# Durable offsite storage protects against host loss. Example to S3:
#   aws s3 cp "$OUT" "s3://aakash-backups/postgres/" --storage-class STANDARD_IA
# Or rclone to any remote:
#   rclone copy "$OUT" "backblaze:aakash-backups/postgres/"

echo "[db-backup] Done."
