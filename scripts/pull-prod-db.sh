#!/usr/bin/env bash
set -euo pipefail

# Pull a Postgres dump from a remote database URL into storage/app/db-dumps/.
# Output is gzipped custom-format (pg_dump -Fc), matching what restore-dev-db.sh expects.
#
# Usage:
#   composer pull:prod
#   bash scripts/pull-prod-db.sh
#   bash scripts/pull-prod-db.sh 'postgresql://user:pass@host:5432/dbname'
#
# The URL is never printed. Prefer the interactive prompt so the password
# does not land in shell history.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

DUMP_DIR="${DUMP_DIR:-storage/app/db-dumps}"
mkdir -p "$DUMP_DIR"

if ! command -v pg_dump >/dev/null 2>&1; then
  echo "pg_dump not found. Install PostgreSQL client tools first." >&2
  exit 1
fi

DATABASE_URL="${1:-}"

if [[ -z "$DATABASE_URL" ]]; then
  echo "Paste the full Postgres URL (input hidden), then press Enter:"
  echo "Example: postgresql://user:password@host:5432/dbname"
  # -s keeps credentials off the screen; -r preserves backslashes in passwords.
  read -r -s DATABASE_URL
  echo
fi

if [[ -z "$DATABASE_URL" ]]; then
  echo "No database URL provided." >&2
  exit 1
fi

case "$DATABASE_URL" in
  postgres://*|postgresql://*)
    ;;
  *)
    echo "URL must start with postgres:// or postgresql://" >&2
    unset DATABASE_URL
    exit 1
    ;;
esac

STAMP="$(date +%Y%m%d-%H%M%S)"
OUT_FILE="${DUMP_DIR}/prod-${STAMP}.sql.gz"

echo "Dumping remote database -> ${OUT_FILE} ..."

# Custom format + gzip matches Dokploy-style dumps that restore-dev-db.sh auto-detects.
if ! pg_dump \
  --dbname="$DATABASE_URL" \
  --format=custom \
  --no-owner \
  --no-acl \
  | gzip >"$OUT_FILE"; then
  rm -f "$OUT_FILE"
  unset DATABASE_URL
  echo "pg_dump failed." >&2
  exit 1
fi

unset DATABASE_URL

SIZE="$(du -h "$OUT_FILE" | awk '{print $1}')"
echo "Dump complete: ${OUT_FILE} (${SIZE})"
echo "Restore into local dev with: composer restore:dev -- ${OUT_FILE}"
