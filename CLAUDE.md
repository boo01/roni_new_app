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
- ✅ Phase 7A — Mtavruli headings, image gallery + lightbox, image optimization
- ✅ Phase 7B — Multi-category products + live search box
- ✅ Phase 7C — Admin-defined attributes + storefront sidebar filters
- ✅ Phase 7D — Live scrape mode (Browsershot + system Chrome, no Puppeteer dl)
- ✅ Phase 8A — Per-audience visibility (retail/B2B) + header category whitelist
- ✅ Phase 8B — Static pages CMS (About / Contact) with locations + Google Maps embed
- ✅ Phase 9 — Real catalog seeded (Node crawler + Roni5CatalogSeeder); B2C/B2B price trees normalised (116 merges + 361 wholesale-only items); subcategory rendering everywhere (header dropdowns, mobile filter panel, category sidebar); PhotoSwipe lightbox; Wix CDN high-res URLs
- ✅ Phase 10 — Customer-selectable product options + storefront polish:
  - **Options reuse the attribute system**: `attributes.is_selectable` + `attributes.is_required` flags (toggles in Filament `AttributeResource`). A product's assigned values of a selectable attribute become the choices shown on the product page. `Product::selectableOptionGroups()` / `hasRequiredOptions()`.
  - **Cart carries options**: session cart is now keyed by `product_id + sorted value_ids` (`Cart::add($id,$qty,$options)`, `setQuantity($lineKey,…)`, `remove($lineKey)`). Same product + different options = separate lines. `order_items.options_snapshot` (JSON) snapshots the choice; rendered in cart, checkout, account order, and invoice via `<x-storefront.option-tags>`.
  - **Quick add-to-cart in grids**: `product-card` has a hover button (always visible on mobile) → AJAX POST to `cart.add` (JSON when `expectsJson()`), toast + reactive header badge (`cart-updated` window event). Products with required options link to the product page instead. `form[data-cart-add]` handler lives in `resources/js/app.js`.
  - **Invoice overhaul**: embeds Noto Sans Georgian (see Fonts note); table-based layout (dompdf has no flexbox); first product image + chosen options per line.
  - **Home category carousel** (scroll-snap + arrows, representative images) + `/categories` "see all" page (`CatalogController::categories`).
  - **Category sorting**: `?sort=price_asc|price_desc|name_asc|name_desc` (whitelisted in `CatalogController::normalizeSort/applySort`); dropdown preserves active filters.

## Current data state (post Phase 9)
- 574 products: 213 retail-visible + 361 B2B-only wholesale
- 108 categories: 8 retail header roots + their 56 subs + 5 company roots + 38 company subs + 1 "ახალი"
- 98 retail products carry a B2B price override (struck-through retail + company price)
- B2B test user: `acme@example.com` / `password` (group `კომპანიები`)
- Re-seed clean: `docker compose up -d && php artisan migrate:fresh --seed && php artisan db:seed --class=Roni5CatalogSeeder`
  - 82MB scraped images live in `database/seeders/data/roni5-catalog/` (committed)

## PDF / Georgian fonts (invoice)
- dompdf's default DejaVu Sans has **no Georgian glyphs** → garbled text. Fix: `public/fonts/NotoSansGeorgian-{Regular,Bold}.ttf` (static instances cut from the variable font via `fonttools varLib.instancer`), embedded in `pdfs/invoice.blade.php` as **base64 `data:` URIs** inside `@font-face`. Bare file-path `url()` and local `<img src>` paths fail dompdf chroot resolution — **inline as data URIs** instead (product thumbnails in the invoice do the same).
- Only weights 400 + 700 exist. Use `font-weight: 400` or `700` only in the invoice CSS — `500`/`600` fall back to a non-Georgian font and render as `?`.
- These fonts cover Georgian + Latin + digits + ₾ (U+20BE).

## Known TODOs / open items
- The "ფერი" (color) attribute is set selectable+required and attached to the test product `01000000` (SKU) as a demo of options. Owner manages attributes/options in admin → Catalog → Filters.
- Drag-drop image upload in Filament product form sometimes doesn't persist on Save — to reproduce + fix
- Confirm/polish the live search (header SearchBox livewire component) — verify it's reachable and the result UI matches the design
- Admin UI to create/manage attributes + filter values (Phase 7C added the storefront filters; admin CRUD may need surfacing)
- Multi-category assignment UX in Filament product form (model is many-to-many; confirm form uses CheckboxList or similar)
- B2B header is busy (~13 items: 8 retail roots + 5 company roots). Consider hiding/renaming company roots via admin `show_in_header` toggle once the owner picks what to feature
- Production: switch `MAIL_MAILER` from `log` to Gmail SMTP per the owner's setup; flip DB env to the Georgian host's MySQL
- The 19 cases where company price ≥ retail price were skipped (no override) — log these for the owner to review manually

## .env recent change
The user renamed `APP_NAME` to `Roni` (was `Laravel`). Keep that.

## Things to NOT do
- Don't compute prices outside `Pricing` service.
- Don't add online payment yet — checkout creates an invoice-only order.
- Don't add features the plan didn't include (no wishlists, no reviews, no multi-currency).
- Don't build i18n yet — Georgian only. The schema is ready for future languages.
