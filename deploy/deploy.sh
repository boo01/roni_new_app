#!/usr/bin/env bash
# Runs on the server (invoked by GitHub Actions over SSH, or manually).
# Assumes code has already been rsynced to /srv/roni5 and /srv/roni5/.env exists.
set -euo pipefail

cd /srv/roni5
export COMPOSE_FILE=docker-compose.prod.yml
DC="docker compose"
# Run artisan as the web user (uid 1000 = www-data = host gothem) so files it
# writes (config cache, storage:link, logs, media conversions) stay writable by
# php-fpm. Running as root would create root-owned files that break runtime
# writes (e.g. Livewire uploads failing to generate image conversions → 500).
ARTISAN="docker compose exec -T --user 1000:1000 app php artisan"

echo "==> Building app image"
$DC build

echo "==> Starting containers"
$DC up -d --remove-orphans

echo "==> Waiting for MySQL to be healthy"
for i in $(seq 1 30); do
  if $DC exec -T mysql mysqladmin ping -h 127.0.0.1 --silent >/dev/null 2>&1; then break; fi
  sleep 2
done

echo "==> Migrating"
$ARTISAN migrate --force

echo "==> Seeding (idempotent DatabaseSeeder: roles, default group, admin)"
$ARTISAN db:seed --force

echo "==> storage:link"
$ARTISAN storage:link 2>/dev/null || true

echo "==> Rebuilding caches"
$ARTISAN optimize:clear
$ARTISAN config:cache
$ARTISAN route:cache
$ARTISAN view:cache

echo "==> Reloading app + workers (picks up new code / clears opcache)"
$DC restart app worker scheduler

echo "==> Deploy complete"
