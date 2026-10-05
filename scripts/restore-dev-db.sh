#!/usr/bin/env bash
set -euo pipefail

# Restore a Postgres dump into the local dev database, then apply pending
# migrations and local-only seeders (statuses, flows, and the local admin).
#
# Supports:
#   - Dokploy backups: gzipped pg_dump -Fc (often named *.sql.gz)
#   - Uncompressed custom-format dumps (*.dump / PGDMP)
#   - Plain SQL dumps (*.sql), optionally gzipped
#
# Usage:
#   composer restore:dev
#   composer restore:dev -- path/to/backup.sql.gz
#   bash scripts/restore-dev-db.sh [dump-file]

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

DUMP_DIR="${DUMP_DIR:-storage/app/db-dumps}"
TARGET_DB="${DB_DATABASE:-laravel_admin}"
PGHOST="${DB_HOST:-127.0.0.1}"
PGPORT="${DB_PORT:-5432}"
PGUSER="${DB_USERNAME:-postgres}"
export PGPASSWORD="${DB_PASSWORD:-}"

resolve_dump_file() {
  if [[ $# -ge 1 && -n "${1:-}" ]]; then
    printf '%s\n' "$1"
    return
  fi

  if [[ ! -d "$DUMP_DIR" ]]; then
    echo "No dump path given and dump directory missing: $DUMP_DIR" >&2
    echo "Pass a file: bash scripts/restore-dev-db.sh path/to/backup.sql.gz" >&2
    exit 1
  fi

  local latest
  latest="$(find "$DUMP_DIR" -type f \( \
      -name '*.dump' -o -name '*.sql' -o -name '*.sql.gz' -o -name '*.gz' -o -name '*.backup' \
    \) -print0 2>/dev/null | xargs -0 ls -t 2>/dev/null | head -n 1 || true)"

  if [[ -z "$latest" ]]; then
    echo "No dump path given and no dumps found in $DUMP_DIR" >&2
    echo "Drop a Dokploy/Postgres backup there, or pass a path explicitly." >&2
    exit 1
  fi

  printf '%s\n' "$latest"
}

file_magic_hex() {
  od -An -tx1 -N5 "$1" | tr -d ' \n'
}

peek_bytes() {
  # $1=file $2=count — raw bytes from start of possibly-gzipped content
  local file="$1"
  local count="$2"
  local magic
  magic="$(file_magic_hex "$file")"

  if [[ "$magic" == 1f8b* ]]; then
    gunzip -c "$file" | head -c "$count"
  else
    head -c "$count" "$file"
  fi
}

detect_format() {
  local file="$1"
  local magic peek
  magic="$(file_magic_hex "$file")"

  if [[ "$magic" == 1f8b* ]]; then
    peek="$(peek_bytes "$file" 5 | tr -d '\0' || true)"
    if [[ "$peek" == PGDMP* ]]; then
      echo "gzip-custom"
    else
      echo "gzip-sql"
    fi
    return
  fi

  peek="$(peek_bytes "$file" 5 | tr -d '\0' || true)"
  if [[ "$peek" == PGDMP* ]]; then
    echo "custom"
  else
    echo "sql"
  fi
}

DUMP_FILE="$(resolve_dump_file "${1:-}")"

if [[ "$TARGET_DB" == *"_testing" ]]; then
  echo "Refusing to restore into test database [$TARGET_DB]." >&2
  exit 1
fi

if [[ ! -f "$DUMP_FILE" ]]; then
  echo "Dump not found: $DUMP_FILE" >&2
  exit 1
fi

FORMAT="$(detect_format "$DUMP_FILE")"

echo "Restoring [$DUMP_FILE] ($FORMAT) -> [$TARGET_DB] on $PGHOST:$PGPORT ..."

psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d postgres -v ON_ERROR_STOP=1 -c \
  "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$TARGET_DB' AND pid <> pg_backend_pid();" \
  >/dev/null || true

dropdb -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" --if-exists "$TARGET_DB"
createdb -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" "$TARGET_DB"

case "$FORMAT" in
  custom)
    pg_restore -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" --no-owner --no-acl -d "$TARGET_DB" "$DUMP_FILE"
    ;;
  gzip-custom)
    gunzip -c "$DUMP_FILE" | pg_restore -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" --no-owner --no-acl -d "$TARGET_DB"
    ;;
  sql)
    psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$TARGET_DB" -v ON_ERROR_STOP=1 -f "$DUMP_FILE" >/dev/null
    ;;
  gzip-sql)
    gunzip -c "$DUMP_FILE" | psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$TARGET_DB" -v ON_ERROR_STOP=1 >/dev/null
    ;;
  *)
    echo "Unrecognized dump format: $FORMAT" >&2
    exit 1
    ;;
esac

php artisan config:clear
php artisan migrate --no-interaction
php artisan db:seed --class=InventoryStatusSeeder --no-interaction
php artisan db:seed --class=FlowSeeder --no-interaction
php artisan db:seed --class=UserSeeder --no-interaction

echo "Restore complete on [$TARGET_DB]."
echo "Local developer (if APP_ENV=local): admin@yopmail.com / admin@123"
echo "Restart php artisan serve if it is running."
