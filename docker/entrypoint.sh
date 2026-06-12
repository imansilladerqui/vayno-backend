#!/bin/sh
set -e

if [ $# -gt 0 ]; then
    exec "$@"
fi

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
exec php -S 0.0.0.0:${PORT:-8000} -t public public/router.php
