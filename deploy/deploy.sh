#!/usr/bin/env bash
# Runs on the server (invoked by GitHub Actions over SSH, or manually).
# Assumes code has already been rsynced to /srv/roni5 and /srv/roni5/.env exists.
set -euo pipefail

cd /srv/roni5
export COMPOSE_FILE=docker-compose.prod.yml
DC="docker compose"

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
$DC exec -T app php artisan migrate --force

echo "==> Seeding (idempotent DatabaseSeeder: roles, default group, admin)"
$DC exec -T app php artisan db:seed --force

echo "==> storage:link"
$DC exec -T app php artisan storage:link 2>/dev/null || true

echo "==> Rebuilding caches"
$DC exec -T app php artisan optimize:clear
$DC exec -T app php artisan config:cache
$DC exec -T app php artisan route:cache
$DC exec -T app php artisan view:cache

echo "==> Reloading app + workers (picks up new code / clears opcache)"
$DC restart app worker scheduler

echo "==> Deploy complete"
