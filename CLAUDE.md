# Roni5 — B2B/B2C E-commerce

Rebuild of roni5.ge (Wix stationery/office-supply shop) as a custom Laravel app with company-tier pricing.

## Stack
- **Laravel 11** (PHP 8.4) — backend & routing
- **Filament v3** — admin panel at `/admin`
- **Livewire 3 + Blade + Tailwind 4 + Alpine** — public storefront
- **MySQL 8.4 via Docker** for local dev (`docker compose up -d`), `:memory:` SQLite for tests
- Production target: shared hosting MySQL — same connection settings, change `.env`
- **Spatie packages**: `laravel-permission`, `laravel-medialibrary`, `barryvdh/laravel-dompdf`

## Domain conventions
- Localized text columns are `_ka` suffixed (Georgian). Future `_en` / `_ru` columns will be additive.
- **Pricing is owned by `App\Services\Pricing`** — never compute B2B/discount prices inline anywhere else. Returns `['retail', 'charged', 'has_discount']`. Resolution order: per-product override (`product_group_prices`) → group `discount_percent` → retail.
- **Customers** are `User` rows. `customer_group_id IS NULL` means B2C / retail. Non-null means B2B.
- **B2B accounts are admin-created**, not self-registered. Public registration creates B2C only.
- Currency is GEL (Georgian Lari). All `decimal(10,2)`.

## Admin
- URL: `/admin`
- Login: `z.gabisonia@oritech.io` / `password` (dev only — change for production)
- Gate: only users with `admin` role and `is_active = true` can enter

## Roles
- `admin` — full admin panel access
- `b2b-customer` — assigned during B2B account creation
- `b2c-customer` — assigned on public self-registration

## Commands
- `docker compose up -d` — start MySQL on `127.0.0.1:3306` (db `roni5` / user `roni5` / pass `roni5`)
- `docker compose down` — stop it (data persists in the `roni5-mysql-data` volume)
- `php artisan migrate` — run migrations
- `php artisan db:seed` — idempotent seeder (roles, default group, admin user)
- `php artisan test` — PHPUnit suite (not Pest)
- `php artisan serve` — dev server on http://127.0.0.1:8000
- `php artisan import:roni5 --b2c=path.csv [--b2b=path.csv] [--apply]` — CSV-based migration with code-based merge.
- `php artisan import:roni5 --scrape-url=https://www.roni5.ge/category/X --category=our_slug [--apply] [--limit=N]` — live scrape mode (Browsershot + system Chrome, no Puppeteer Chromium download). One URL per `--scrape-url`; repeat for multiple categories.

## Design language
Clean modern minimal — white background, generous whitespace, subtle borders, sans-serif. Take cues from current roni5.ge layout (simple product grid, clear pricing, no decorative chrome) but execute with modern Tailwind components.

## Project plan
Full architecture + migration strategy: `/Users/user/.claude/plans/my-friend-created-website-delightful-coral.md`

## Phase status
- ✅ Phase 1 — Skeleton (migrations, models, Pricing service)
- ✅ Phase 2 — Filament admin resources (Category, Product, CustomerGroup, Order, User)
- ✅ Phase 3 — Storefront read path (home / category / product, Noto Sans Georgian, B2B-aware price block)
- ✅ Phase 4 — Cart & checkout (session cart, Livewire cart/checkout, dompdf invoice, mail notifications)
- ✅ Phase 5 — Customer accounts (Breeze auth, /account, order history, order detail)
- ✅ Phase 6 — Migration: `import:roni5` CSV-based importer with code-based merge (+ tests)

## Things to NOT do
- Don't compute prices outside `Pricing` service.
- Don't add online payment yet — checkout creates an invoice-only order.
- Don't add features the plan didn't include (no wishlists, no reviews, no multi-currency).
- Don't build i18n yet — Georgian only. The schema is ready for future languages.
