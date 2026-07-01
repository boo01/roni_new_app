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
- ✅ Phase 11 — **Admin in Georgian + language switch**:
  - `laravel-lang/common` (dev) published `lang/ka.json` (~449 keys) + `lang/ka/*`. All Filament resources/pages use `__()` with English keys → Georgian via `lang/ka.json`. **When adding admin labels, wrap in `__()` and add the Georgian to `lang/ka.json`.**
  - ქარ/ENG switch in the admin topbar: `App\Http\Middleware\SetAdminLocale` (session key `admin_locale`, default `ka`, persistent middleware on the panel so Livewire AJAX keeps the locale), route `admin.locale`, view `resources/views/filament/locale-switch.blade.php` injected via `PanelsRenderHook::USER_MENU_BEFORE` in `AdminPanelProvider`.
- ✅ Phase 12 — **Order admin view + product card polish**:
  - Order view (`OrderResource` infolist) now has a **Products** section — custom view `resources/views/filament/infolists/order-items.blade.php` (inline styles so it renders regardless of Filament's Tailwind build) showing per line: image with **prev/next arrows that fade in on hover** (Alpine; `top:40px` fixed-center — never use transform there, `x-transition` fights it), name, SKU, chosen options (amber pills), qty, unit/line price, "open product" link. `ViewOrder` has a **Print / Invoice** header action → the invoice PDF.
  - Product card add-to-cart button moved **off the image overlay to beside the price** (round icon button; `product-card.blade.php`).
- ✅ Phase 13 — **Settings page, menu manager, rich editor, SEO, footer**:
  - **Global Settings** (`/admin/manage-settings`, custom page `App\Filament\Pages\ManageSettings`) → single-row `site_settings` table + `SiteSetting` model (`current()` request-cached singleton, `whatsappNumber()`). Holds: logo upload (public disk `branding/`), SEO `meta_title`/`meta_description`, contact phone/email/whatsapp, `social_links` (JSON: facebook/instagram/youtube/tiktok/telegram/linkedin), `locations` (repeater). **Contact + locations were MOVED off the `pages` table into settings** (migration dropped `pages.contact_*`). `/contact` view + footer read from `SiteSetting::current()`.
  - **Menu manager** (`/admin/manage-menu`, custom page `App\Filament\Pages\ManageMenu`) → `menu_items` table (parent_id, location, type `page|category|link`, page_id/category_id/url, target_blank, is_active, sort_order) + `MenuItem` model (`resolveLabel()`/`resolveUrl()`). Nested **2-level reorderable repeaters** (no plugin). `App\Support\Menu::header($audience)` builds the storefront header from menu_items, filters category/page items by audience visibility, **falls back to top-level categories when empty**. `nav.blade.php` renders from it. Items whose category is retail-hidden are tagged **"B2B only"** in the admin (labels + category picker).
  - **`categories.show_in_header` + `header_sort_order` columns were DROPPED** — header is now menu-driven; home carousel + menu fallback use top-level categories (`parent_id IS NULL`).
  - **Rich text editor**: `Page.body_ka` uses Filament `RichEditor` with image upload (public disk `pages/`). `pages/page.blade.php` + `contact.blade.php` render HTML (`{!! !!}`).
  - **SEO**: storefront layout (`components/layouts/storefront.blade.php`) accepts a `:seo` prop and renders `<title>` + meta description + `og:title/description/image` (site defaults from `SiteSetting`, or page-level). **Per-product SEO**: `products.meta_title/meta_description/meta_keywords` (nullable overrides) + `Product::seoMeta()` auto-generates from name / description (strip+160) / categories / first image. `product.blade.php` passes `:seo="$product->seoMeta()"`; `ProductResource` has a collapsed **SEO** section (override fields with placeholders showing the auto value).
  - **Footer** (`footer.blade.php`): contact icons (phone/email/WhatsApp) + social brand-icon links (only render when that URL is set). Logo in `nav.blade.php` (falls back to "Roni5" text).

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

## Session state (uncommitted — read before continuing)
- **EVERYTHING from Phases 10–13 is UNCOMMITTED.** Git is on `main`, **no remote** configured, last commit predates this work. User will push themselves to `git@github.com:boo01/roni_new_app.git` (must run `git add -A`, not `git add README.md`; create the GitHub repo empty first; SSH key `~/.ssh/id_ed25519` exists).
- Migrations added this session (all already run on dev DB): `2026_06_08_000001/000002` (options flags, options_snapshot), `2026_06_15_000001` (site_settings + move contact off pages), `000002` (menu_items + seed), `000003` (drop categories header flags), `000004` (settings logo/SEO/social), `000005` (products SEO). `php artisan test` = **47 passing**. Assets built (`npm run build`).
- Test/demo data on dev: product `01000000` (id 574) has the color-options demo; product `12323123213` (id 575) is a user-made SEO test; order `R-20260610-XBLEH` is a real checkout test.

## Known TODOs / open items
- **Hosting not chosen yet.** Discussed: cheapest+drop-in = shared cPanel MySQL (~$5) or raw Hetzner VPS (~€4, install MySQL); managed = Forge Hobby $12 + VPS (~$16, auto-MySQL + daily backups); Laravel Cloud Starter ($5+usage) was **rejected for now** because its managed DB is **Postgres** (app is MySQL → would need migration) and cost is usage-based. Recommended a flat **MySQL** option. Owner to decide; then write deploy steps.
- **Footer About/Contact links + `/about` `/contact` routes 404** — they hardcode slugs `about`/`contact`, but the only page has slug `chvens-shesakheb`. Pre-existing. Fix idea: drive footer from a menu, or relax the routes/PageController. (`page.about`/`page.contact` routes in `routes/web.php`.)
- Rich-editor product/page **image upload** is wired (public disk) but not yet tested with a real file upload.
- Drag-drop image upload in Filament product form sometimes doesn't persist on Save — to reproduce + fix.
- Live search (header `SearchBox` Livewire) — at ~20k products a MySQL fulltext index may be needed; currently fine.
- Production: switch `MAIL_MAILER` from `log` to Gmail SMTP; set production MySQL `.env`; `APP_ENV=production`, `APP_DEBUG=false`; `config/route/view:cache`; `storage:link`; `db:seed --class=Roni5CatalogSeeder`.
- The 19 cases where company price ≥ retail price were skipped (no override) — log for owner review.

## .env recent change
The user renamed `APP_NAME` to `Roni` (was `Laravel`). Keep that.

## Things to NOT do
- Don't compute prices outside `Pricing` service.
- Don't add online payment yet — checkout creates an invoice-only order.
- Don't add features the plan didn't include (no wishlists, no reviews, no multi-currency).
- Storefront **content** is Georgian-only (no `_en`/`_ru` yet). The **admin UI** is bilingual (ქარ/ENG) via `lang/ka.json` — that's UI translation, not content i18n.
- Don't reintroduce `categories.show_in_header` — the header is menu-driven now (`menu_items` + `App\Support\Menu`).
- Don't compute SEO/meta inline — use `Product::seoMeta()` / `SiteSetting` + the layout's `:seo` prop.
