# Roni5 — Server provisioning & deployment

Production host: **152.53.14.199** (Debian 12), user **gothem**.
Stack: **Docker** (Laravel php-fpm + MySQL 8.4 + queue worker + scheduler) behind
**host Caddy** on `:8000` (HTTP). CI/CD via **GitHub Actions** on push to `main`.

```
GitHub push→main → Actions (composer/npm/test) → rsync over SSH → /srv/roni5 → deploy.sh
Host Caddy :8000 ──php_fastcgi──▶ app(php-fpm, 127.0.0.1:9000)   ┐
                                  mysql (:3311 ext → 3306)        ├ docker compose
                                  worker (queue:work)             │
                                  scheduler (schedule:work)       ┘
```

## What runs where
- **Code** lives on the host at `/srv/roni5` and is **bind-mounted** into the
  containers at the identical path, so host Caddy and php-fpm share paths.
- **`.env`** lives at `/srv/roni5/.env` (never committed, never rsynced). Holds
  both Laravel and docker-compose (`DB_*`) secrets.
- **`storage/`** and uploaded media live on the host and are excluded from rsync,
  so deploys never wipe the logo / page images / product media.
- All drivers are DB-backed (session/cache/queue) — no Redis.

## One-time provisioning (already done)
Installed Docker CE + compose plugin, Caddy, iptables-persistent. Created
`/srv/roni5`, the `storage/` skeleton, and the firewall. Ran as root:
`provision.sh` then `firewall-setup.sh` (kept in the deploy history).

### Firewall — MySQL access control ONLY (no host firewall)
- The host **INPUT** chain is left **open** (policy ACCEPT) — no port-level host
  firewall, by request. SSH/`8000`/etc. are reachable normally.
- The **only** restriction is on **MySQL**: the published `3311` → container
  `3306` is DNAT'd and traverses the Docker `FORWARD`/`DOCKER-USER` path, where
  access is limited to allow-listed IPs (plus Docker's internal `172.16/12`).
- Source of truth: `/usr/local/sbin/roni5-firewall.sh` + systemd unit
  `roni5-firewall.service` (runs after `docker.service`, so it re-applies on
  every boot). Allowed DB IPs: `/etc/roni5/mysql-allowed-ips.txt`.

### Add / remove an IP allowed to reach MySQL
```bash
# add
sudo bash /srv/roni5/deploy/mysql-allow-ip.sh <NEW_IP>
# or edit the list and re-apply
sudo nano /etc/roni5/mysql-allowed-ips.txt
sudo systemctl restart roni5-firewall
# view
cat /etc/roni5/mysql-allowed-ips.txt
sudo iptables -S DOCKER-USER
```

## GitHub Actions setup (one-time)
Add these repo secrets (Settings → Secrets and variables → Actions):
| Secret | Value |
|---|---|
| `SSH_HOST` | `152.53.14.199` |
| `SSH_USER` | `gothem` |
| `SSH_KEY`  | contents of the deploy **private** key (ed25519) |

The deploy public key is already in the server's `~gothem/.ssh/authorized_keys`.
The server never talks to GitHub — code is pushed **to** it by the runner via
rsync, so no git credentials ever land on the box.

## Deploy cycle (automatic on push to `main`)
`.github/workflows/deploy.yml`: checkout → composer install → `npm run build` →
`php artisan test` (**gate**) → prune dev deps → rsync to `/srv/roni5` →
`ssh … deploy.sh`. `deploy.sh` builds the image, `up -d`, `migrate --force`,
`db:seed --force` (idempotent), `storage:link`, rebuilds caches, restarts
`app`/`worker`/`scheduler`.

## Manual operations
```bash
ssh gothem@152.53.14.199
cd /srv/roni5 && export COMPOSE_FILE=docker-compose.prod.yml
docker compose ps
docker compose logs -f app        # or worker / scheduler / mysql
bash deploy/deploy.sh             # full redeploy
docker compose exec app php artisan <cmd>
# one-time catalog seed (573 products/613 images):
docker compose exec app php artisan db:seed --class=Roni5CatalogSeeder --force
```

## Remote DB access (from your PC)
Host `152.53.14.199`, port **3311**, user `roni5`, database `roni5`, password from
`.env` (`DB_PASSWORD`). Only allow-listed IPs can connect.

## Adding a domain + HTTPS later
Point DNS at `152.53.14.199`, then in `/etc/caddy/Caddyfile` replace `:8000`
with the domain (e.g. `shop.roni5.ge`), open `80`+`443` in the host firewall
(add to `roni5-firewall.sh`), and `sudo systemctl reload caddy`. Caddy
auto-provisions Let's Encrypt TLS.
