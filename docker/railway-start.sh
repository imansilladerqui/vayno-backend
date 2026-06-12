#!/bin/sh
set -e

if [ -z "${DATABASE_URL:-}" ]; then
    echo "ERROR: DATABASE_URL is not set." >&2
    echo "Railway → API service → Variables → add DATABASE_URL" >&2
    echo "Use Add Reference → your PostgreSQL service → DATABASE_URL" >&2
    exit 1
fi

case "$DATABASE_URL" in
    postgresql:*|postgres:*)
        ;;
    *)
        echo "ERROR: DATABASE_URL must use postgresql:// (found: ${DATABASE_URL%%:*}:)." >&2
        echo "This app uses PostgreSQL (pdo_pgsql). Remove any mysql:// URL." >&2
        echo "Railway → Variables → DATABASE_URL = \${{Postgres.DATABASE_URL}}" >&2
        exit 1
        ;;
esac

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
exec php -S "0.0.0.0:${PORT:-8000}" -t public public/router.php
