# roni5.ge catalog crawler

One-off tooling that scrapes the **live** roni5.ge Wix Stores storefront and
produces a seedable dataset for this Laravel app: the full category tree
(categories + subcategories), every product with full detail, and all gallery
images.

## How it works

roni5.ge is a Wix Stores site. There is no usable sitemap, and the catalog API
(`/_api/wix-ecommerce-storefront-web/api`, a whitelisted GraphQL endpoint) 403s
unless called with the browser's live session. So the crawler drives **system
Chrome** via `playwright-core`:

1. Reads the complete Wix "Categories" tree (incl. `parentCategoryId` →
   subcategories) from a category page's server-rendered warmup data.
2. Captures the storefront's own `getFilteredProducts` request once (clicking
   "load more"), then **replays that exact request** per category id with paging
   — so it isn't 403'd and doesn't depend on clicking through every page.
3. Unions products across all categories (dedup by Wix product id) and records
   each product's category memberships.

Product descriptions are empty on the source store, so they are not collected
(`description_ka` is seeded as `null`).

## Requirements

- Node 18+ and Google Chrome installed (`/Applications/Google Chrome.app`).
- `npm install --no-save playwright-core` (kept out of `package.json` — this is
  dev-only tooling, not an app dependency).

## Run

```bash
cd scripts/roni5-crawl
node crawl.mjs            # -> database/seeders/data/roni5-catalog/catalog.json
node download-images.mjs  # downloads all images, writes local paths back into catalog.json
```

`crawl.mjs` accepts `LIMIT=<n>` env to cap products per category for quick test
runs. `download-images.mjs` is resumable (skips files already on disk) and
records any failures to `image-failures.json`.

## Output → `database/seeders/data/roni5-catalog/`

- `catalog.json` — `{ source, scraped_at, stats, categories[], products[] }`
  - **categories**: `source_id`, `name_ka`, `slug`, `parent_source_id`,
    `sort_order`, `is_active`, `in_header`, `header_order`, `header_label`
  - **products**: `wixId`, `sku`, `name_ka`, `slug`, `retail_price`,
    `compare_price`, `ribbon`, `in_stock`, `images[] {file, src, local, w, h}`,
    `categorySlugs[]`, `categoryIds[]`, `primary_category_id`
- `images/` — full-res originals, named by their unique Wix file id.
- `image-failures.json` — images the CDN refused (2 assets at last run).

## Seeding (separate step — see `database/seeders/Roni5CatalogSeeder.php`)

```bash
php artisan storage:link                       # once
php artisan db:seed --class=Roni5CatalogSeeder # populate categories + products + media
```

The seeder is intentionally **not** wired into `DatabaseSeeder`. It is
idempotent, synthesizes/de-duplicates SKUs (the source has 217 missing and 117
duplicated EANs vs. the app's unique `sku` column), and attaches images with
`preservingOriginal()` so the seed files stay in place.

## Last crawl stats

108 categories (14 top-level, 8 in header), 689 products, 594 unique images.
