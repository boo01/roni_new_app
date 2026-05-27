<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the categories + products + images scraped from the live roni5.ge
 * storefront (see scripts/roni5-crawl + database/seeders/data/roni5-catalog).
 *
 * NOT wired into DatabaseSeeder on purpose — run it explicitly:
 *
 *     php artisan storage:link            # once, so /storage serves media
 *     php artisan db:seed --class=Roni5CatalogSeeder
 *
 * Idempotent: categories keyed by slug, products keyed by slug, images added
 * only when a product has none yet. Re-running updates fields without dupes.
 */
class Roni5CatalogSeeder extends Seeder
{
    private string $dataDir;

    public function run(): void
    {
        $this->dataDir = database_path('seeders/data/roni5-catalog');
        $file = $this->dataDir . '/catalog.json';

        if (! is_file($file)) {
            $this->command->error("catalog.json not found at {$file}. Run the crawler first (scripts/roni5-crawl).");
            return;
        }

        $catalog = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $catsBySource = collect($catalog['categories'])->keyBy('source_id');

        // ---- categories (two passes: create, then link parents) ----
        $modelBySource = [];
        $this->command->info('Seeding ' . count($catalog['categories']) . ' categories…');
        foreach ($catalog['categories'] as $c) {
            $retail = $this->rootInHeader($c['source_id'], $catsBySource);
            $modelBySource[$c['source_id']] = Category::updateOrCreate(
                ['slug' => $c['slug']],
                [
                    'name_ka' => $this->cleanName($c['name_ka']) ?: $c['slug'],
                    'sort_order' => $c['sort_order'] ?? 0,
                    'is_active' => $c['is_active'] ?? true,
                    'visible_to_retail' => $retail,
                    'visible_to_b2b' => true,
                    'show_in_header' => $c['in_header'] ?? false,
                    'header_sort_order' => $c['header_order'] ?? 0,
                ],
            );
        }
        foreach ($catalog['categories'] as $c) {
            $parentSrc = $c['parent_source_id'] ?? null;
            if ($parentSrc && isset($modelBySource[$parentSrc])) {
                $modelBySource[$c['source_id']]->update(['parent_id' => $modelBySource[$parentSrc]->id]);
            }
        }

        // ---- products ----
        $usedSkus = [];
        $imagesAdded = 0;
        $total = count($catalog['products']);
        $this->command->info("Seeding {$total} products…");
        $bar = $this->command->getOutput()->createProgressBar($total);

        foreach ($catalog['products'] as $i => $p) {
            $sku = $this->uniqueSku($p, $usedSkus);

            $product = Product::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'sku' => $sku,
                    'name_ka' => $this->cleanName($p['name_ka']),
                    'description_ka' => null, // source descriptions are empty
                    'retail_price' => $p['retail_price'] ?? 0,
                    'stock_quantity' => 0,
                    'track_stock' => false,
                    'is_active' => true,
                    'visible_to_retail' => true, // audience gating is handled by category flags
                    'visible_to_b2b' => true,
                    'sort_order' => $i,
                ],
            );

            // category memberships (many-to-many, is_primary on the chosen primary)
            $sync = [];
            foreach (($p['categoryIds'] ?? []) as $order => $srcId) {
                if (! isset($modelBySource[$srcId])) {
                    continue;
                }
                $sync[$modelBySource[$srcId]->id] = [
                    'is_primary' => $srcId === ($p['primary_category_id'] ?? null),
                    'sort_order' => $order,
                ];
            }
            // guarantee at least one primary
            if ($sync && ! collect($sync)->contains(fn ($pivot) => $pivot['is_primary'])) {
                $first = array_key_first($sync);
                $sync[$first]['is_primary'] = true;
            }
            $product->categories()->sync($sync);

            // images — only when the product has none yet (keeps re-runs cheap)
            if ($product->getMedia('images')->isEmpty()) {
                foreach (($p['images'] ?? []) as $img) {
                    $abs = $this->dataDir . '/' . ($img['local'] ?? '');
                    if (! isset($img['local']) || ! is_file($abs)) {
                        continue; // missing/failed download (see image-failures.json)
                    }
                    try {
                        $product->addMedia($abs)
                            ->preservingOriginal()      // keep the seed file in place
                            ->toMediaCollection('images');
                        $imagesAdded++;
                    } catch (\Throwable $e) {
                        $this->command->warn("  image failed for {$p['slug']}: {$e->getMessage()}");
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);
        $this->command->info("Done: " . count($catalog['categories']) . " categories, {$total} products, {$imagesAdded} images attached.");
    }

    /** Clean source titles: drop leading dashes/spaces, collapse internal runs. */
    private function cleanName(?string $name): string
    {
        $name = trim((string) $name);
        $name = ltrim($name, "- \t");
        return trim(preg_replace('/\s{2,}/u', ' ', $name) ?? $name);
    }

    /**
     * The source EAN is missing on ~30% of products and duplicated on others,
     * but the products.sku column is unique. Prefer the real SKU; fall back to a
     * deterministic slug-derived code; suffix collisions deterministically so a
     * re-run always assigns the same SKU to the same product.
     */
    private function uniqueSku(array $p, array &$used): string
    {
        $base = trim((string) ($p['sku'] ?? ''));
        if ($base === '') {
            $base = 'R5-' . Str::upper(substr(md5($p['slug']), 0, 10));
        }
        $sku = $base;
        if (isset($used[$sku])) {
            $sku = $base . '-' . Str::upper(substr(md5($p['slug']), 0, 5));
        }
        // extremely unlikely, but guarantee uniqueness within this run
        while (isset($used[$sku])) {
            $sku .= 'X';
        }
        $used[$sku] = true;
        return $sku;
    }

    /** A category is retail-visible iff its top-level root is in the header menu. */
    private function rootInHeader(string $sourceId, \Illuminate\Support\Collection $catsBySource): bool
    {
        $node = $catsBySource->get($sourceId);
        $guard = 0;
        while ($node && ! empty($node['parent_source_id']) && $guard++ < 20) {
            $node = $catsBySource->get($node['parent_source_id']);
        }
        return (bool) ($node['in_header'] ?? false);
    }
}
