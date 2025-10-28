#!/usr/bin/env bash
# entrypoint.sh - run migrations & start php-fpm (callable from docker compose if desired)
set -e

# run migrations on container start (optional for dev)
# php artisan migrate --force

exec "$@"
