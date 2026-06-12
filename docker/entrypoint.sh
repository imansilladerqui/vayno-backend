#!/bin/sh
set -e

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec php -S 0.0.0.0:${PORT:-8000} -t public
