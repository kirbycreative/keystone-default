#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required" >&2
    exit 1
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec "$@"
