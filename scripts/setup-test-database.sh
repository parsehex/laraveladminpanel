#!/usr/bin/env bash
set -euo pipefail

# Creates an isolated Postgres database for PHPUnit and optionally clones dev data into it.
# Safe to re-run. Your dev DB (laravel_admin) is never touched by tests after this is configured.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

DEV_DB="${DB_DATABASE:-laravel_admin}"
TEST_DB="${TEST_DB:-laravel_admin_testing}"
PGHOST="${DB_HOST:-127.0.0.1}"
PGPORT="${DB_PORT:-5432}"
PGUSER="${DB_USERNAME:-postgres}"
export PGPASSWORD="${DB_PASSWORD:-}"

if [[ "$DEV_DB" == "$TEST_DB" ]]; then
  echo "Dev and test database names must differ (got: $DEV_DB)." >&2
  exit 1
fi

echo "Creating test database [$TEST_DB] on $PGHOST:$PGPORT as $PGUSER ..."
createdb -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" "$TEST_DB" 2>/dev/null || true

if psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -lqt | cut -d \| -f 1 | grep -qw "$DEV_DB"; then
  echo "Cloning schema + data from [$DEV_DB] -> [$TEST_DB] ..."
  pg_dump -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" --clean --if-exists --no-owner --no-privileges "$DEV_DB" \
    | psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -v ON_ERROR_STOP=1 "$TEST_DB" >/dev/null
  echo "Clone complete."
else
  echo "Dev database [$DEV_DB] not found; running migrations on empty test DB instead ..."
  php artisan migrate --env=testing --force --no-interaction
fi

echo "Done. PHPUnit will use [$TEST_DB] only."
