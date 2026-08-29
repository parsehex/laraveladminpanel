#!/usr/bin/env bash
set -euo pipefail

# Restore coworker staging dump into the local dev database (laravel_admin by default).

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

DUMP_FILE="${1:-storage/app/db-dumps/coworker-staging.dump}"
TARGET_DB="${DB_DATABASE:-laravel_admin}"
PGHOST="${DB_HOST:-127.0.0.1}"
PGPORT="${DB_PORT:-5432}"
PGUSER="${DB_USERNAME:-postgres}"
export PGPASSWORD="${DB_PASSWORD:-}"

if [[ "$TARGET_DB" == *"_testing" ]]; then
  echo "Refusing to restore into test database [$TARGET_DB]." >&2
  exit 1
fi

if [[ ! -f "$DUMP_FILE" ]]; then
  echo "Dump not found: $DUMP_FILE" >&2
  exit 1
fi

echo "Restoring [$DUMP_FILE] -> [$TARGET_DB] on $PGHOST:$PGPORT ..."

psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d postgres -v ON_ERROR_STOP=1 -c \
  "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$TARGET_DB' AND pid <> pg_backend_pid();" \
  >/dev/null || true

dropdb -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" --if-exists "$TARGET_DB"
createdb -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" "$TARGET_DB"

pg_restore -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" --no-owner --no-acl -d "$TARGET_DB" "$DUMP_FILE"

php artisan config:clear
php artisan migrate --no-interaction
php artisan db:seed --class=FlowSeeder --no-interaction

echo "Restore complete on [$TARGET_DB]. Restart php artisan serve if it is running."
