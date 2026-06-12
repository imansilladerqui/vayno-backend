#!/bin/sh
set -e

echo "=== Vayno API startup ==="
echo "PORT=${PORT:-8000}"

if [ -z "${DATABASE_URL:-}" ]; then
    echo "ERROR: DATABASE_URL is not set." >&2
    echo "Railway → API service → Variables → add DATABASE_URL" >&2
    echo "Use Add Reference → your PostgreSQL service → DATABASE_URL" >&2
    exit 1
fi

if [ -z "${APP_SECRET:-}" ]; then
    echo "ERROR: APP_SECRET is not set." >&2
    echo "Railway → API service → Variables → APP_SECRET" >&2
    echo "Generate: openssl rand -hex 32" >&2
    exit 1
fi

case "$DATABASE_URL" in
    postgresql:*|postgres:*)
        echo "DATABASE_URL scheme: OK (postgresql)"
        ;;
    *)
        echo "ERROR: DATABASE_URL must use postgresql:// (found: ${DATABASE_URL%%:*}:)." >&2
        echo "This app uses PostgreSQL (pdo_pgsql). Remove any mysql:// URL." >&2
        echo "Railway → Variables → DATABASE_URL = \${{Postgres.DATABASE_URL}}" >&2
        exit 1
        ;;
esac

# Strip serverVersion (Railway/Symfony sometimes set it empty) and force 16
_db_url="$DATABASE_URL"
_db_url=$(printf '%s' "$_db_url" | sed -E 's/([?&])serverVersion=[^&]*//g; s/[?&]+$//; s/\?&/?/')
case "$_db_url" in
    *\?*)
        export DATABASE_URL="${_db_url}&serverVersion=16"
        ;;
    *)
        export DATABASE_URL="${_db_url}?serverVersion=16"
        ;;
esac
echo "DATABASE_URL normalized (serverVersion=16)"

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
echo "Migrations OK. Starting PHP server on 0.0.0.0:${PORT:-8000}..."
exec php -S "0.0.0.0:${PORT:-8000}" -t public public/router.php
