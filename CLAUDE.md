# Roni5 — B2B/B2C E-commerce

Rebuild of roni5.ge (Wix stationery/office-supply shop) as a custom Laravel app with company-tier pricing.

## Stack
- **Laravel 11** (PHP 8.4) — backend & routing
- **Filament v3** — admin panel at `/admin`
- **Livewire 3 + Blade + Tailwind 4 + Alpine** — public storefront
- **SQLite** for local dev (production target: shared hosting MySQL, switch via `.env`)
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
- `php artisan migrate` — run migrations
- `php artisan db:seed` — idempotent seeder (roles, default group, admin user)
- `php artisan test` — PHPUnit suite (not Pest)
- `php artisan serve` — dev server on http://127.0.0.1:8000
- `php artisan import:roni5` — (planned, Phase 6) scrape Wix site

## Design language
Clean modern minimal — white background, generous whitespace, subtle borders, sans-serif. Take cues from current roni5.ge layout (simple product grid, clear pricing, no decorative chrome) but execute with modern Tailwind components.

## Project plan
Full architecture + migration strategy: `/Users/user/.claude/plans/my-friend-created-website-delightful-coral.md`

## Phase status
- ✅ Phase 1 — Skeleton (migrations, models, Pricing service)
- 🔄 Phase 2 — Filament admin resources
- ⏳ Phase 3 — Storefront read path
- ⏳ Phase 4 — Cart & checkout
- ⏳ Phase 5 — Customer accounts
- ⏳ Phase 6 — Wix migration & launch prep

## Things to NOT do
- Don't compute prices outside `Pricing` service.
- Don't add online payment yet — checkout creates an invoice-only order.
- Don't add features the plan didn't include (no wishlists, no reviews, no multi-currency).
- Don't build i18n yet — Georgian only. The schema is ready for future languages.
