<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductGroupPrice;
use App\Services\Roni5\CodeMatcher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the scraped roni5.ge catalog, NORMALISING the two parallel price
 * trees the live site keeps:
 *
 *   - "retail" tree   = the categories that appear in the site header
 *                       (in_header=true). These carry retail prices.
 *   - "company" tree  = the "roni / რონი კატალოგი / რჩეული" categories.
 *                       Same physical goods, lower company (B2B) prices.
 *
 * The crawler captured both as separate product rows. This seeder collapses
 * them:
 *
 *   - A company product that matches a retail product (by code in the title,
 *     else a fuzzy name key) does NOT become its own row — instead its price
 *     is attached to the retail product as a B2B price override for the
 *     default "companies" group (only when it is actually cheaper).
 *   - A company product with no retail twin is kept as a B2B-only product
 *     (visible_to_retail=false) at its single company price.
 *   - Retail products are the canonical, retail-visible rows.
 *
 * Category visibility:
 *   - retail roots  -> visible to everyone, shown in header per in_header.
 *   - company roots -> visible to B2B only, shown in header for B2B so the
 *     wholesale-only catalogue is reachable; hidden from retail visitors.
 *
 * NOT wired into DatabaseSeeder. Run explicitly after migrate:fresh + db:seed:
 *
 *     php artisan db:seed --class=Roni5CatalogSeeder
 */
class Roni5CatalogSeeder extends Seeder
{
    private string $dataDir;

    private CodeMatcher $codeMatcher;

    public function run(): void
    {
        $this->dataDir = database_path('seeders/data/roni5-catalog');
        $this->codeMatcher = new CodeMatcher();
        $file = $this->dataDir . '/catalog.json';

        if (! is_file($file)) {
            $this->command->error("catalog.json not found at {$file}. Run the crawler first (scripts/roni5-crawl).");
            return;
        }

        $catalog = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $catsBySource = collect($catalog['categories'])->keyBy('source_id');

        $rootOf = function (?string $sourceId) use ($catsBySource) {
            $node = $catsBySource->get($sourceId);
            $guard = 0;
            while ($node && ! empty($node['parent_source_id']) && $guard++ < 20) {
                $node = $catsBySource->get($node['parent_source_id']);
            }
            return $node;
        };
        // A category belongs to the retail tree iff its root is in the header.
        $isRetailCat = fn (?string $sourceId): bool => (bool) ($rootOf($sourceId)['in_header'] ?? false);

        // ---- categories: create, set visibility, then link parents ----
        $modelBySource = [];
        $this->command->info('Seeding ' . count($catalog['categories']) . ' categories…');
        foreach ($catalog['categories'] as $c) {
            $retail = (bool) ($rootOf($c['source_id'])['in_header'] ?? false);

            $modelBySource[$c['source_id']] = Category::updateOrCreate(
                ['slug' => $c['slug']],
                [
                    'name_ka' => $this->cleanName($c['name_ka']) ?: $c['slug'],
                    'sort_order' => $c['sort_order'] ?? 0,
                    'is_active' => $c['is_active'] ?? true,
                    'visible_to_retail' => $retail,
                    'visible_to_b2b' => true,
                ],
            );
        }
        foreach ($catalog['categories'] as $c) {
            $parentSrc = $c['parent_source_id'] ?? null;
            if ($parentSrc && isset($modelBySource[$parentSrc])) {
                $modelBySource[$c['source_id']]->update(['parent_id' => $modelBySource[$parentSrc]->id]);
            }
        }

        $companiesGroup = CustomerGroup::where('is_default_for_b2b', true)->first()
            ?? CustomerGroup::firstOrCreate(
                ['slug' => 'companies'],
                ['name' => 'კომპანიები', 'discount_percent' => 0, 'is_default_for_b2b' => true],
            );

        // ---- split products into retail vs company ----
        $retailProducts = [];
        $companyProducts = [];
        foreach ($catalog['products'] as $p) {
            $isRetail = false;
            foreach (($p['categoryIds'] ?? []) as $cid) {
                if ($isRetailCat($cid)) {
                    $isRetail = true;
                    break;
                }
            }
            $isRetail ? $retailProducts[] = $p : $companyProducts[] = $p;
        }

        $usedSkus = [];
        $stats = ['retail' => 0, 'company_merged' => 0, 'company_only' => 0, 'images' => 0];

        // ---- pass 1: retail products (canonical) ----
        $retailByKey = [];
        $this->command->info('Seeding ' . count($retailProducts) . ' retail products…');
        $bar = $this->command->getOutput()->createProgressBar(count($retailProducts));
        foreach ($retailProducts as $i => $p) {
            $product = $this->upsertProduct($p, $usedSkus, $modelBySource, $i, retailVisible: true, b2bVisible: true);
            $stats['images'] += $this->attachImages($product, $p);
            $stats['retail']++;

            $key = $this->matchKey($p['name_ka']);
            $retailByKey[$key] ??= ['model' => $product, 'price' => (float) ($p['retail_price'] ?? 0)];
            $bar->advance();
        }
        $bar->finish();
        $this->command->newLine();

        // ---- pass 2: company products (merge or B2B-only) ----
        $this->command->info('Processing ' . count($companyProducts) . ' company products…');
        $bar = $this->command->getOutput()->createProgressBar(count($companyProducts));
        foreach ($companyProducts as $i => $p) {
            $key = $this->matchKey($p['name_ka']);
            $companyPrice = (float) ($p['retail_price'] ?? 0);

            if (isset($retailByKey[$key])) {
                // Merge: attach the company price to the retail twin as a B2B
                // override, but only when it's an actual discount.
                $retailPrice = $retailByKey[$key]['price'];
                if ($companyPrice > 0 && $companyPrice < $retailPrice) {
                    ProductGroupPrice::updateOrCreate(
                        ['product_id' => $retailByKey[$key]['model']->id, 'customer_group_id' => $companiesGroup->id],
                        ['price' => $companyPrice],
                    );
                }
                $stats['company_merged']++;
            } else {
                // Company-only: B2B-visible, single price.
                $product = $this->upsertProduct($p, $usedSkus, $modelBySource, 10000 + $i, retailVisible: false, b2bVisible: true);
                $stats['images'] += $this->attachImages($product, $p);
                $stats['company_only']++;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->command->newLine(2);

        $this->command->info(sprintf(
            'Done. Categories: %d | retail products: %d | company merged (B2B price): %d | company-only (B2B): %d | images: %d',
            count($catalog['categories']),
            $stats['retail'],
            $stats['company_merged'],
            $stats['company_only'],
            $stats['images'],
        ));
    }

    private function upsertProduct(array $p, array &$usedSkus, array $modelBySource, int $sortOrder, bool $retailVisible, bool $b2bVisible): Product
    {
        $product = Product::updateOrCreate(
            ['slug' => $p['slug']],
            [
                'sku' => $this->uniqueSku($p, $usedSkus),
                'name_ka' => $this->cleanName($p['name_ka']),
                'description_ka' => null,
                'retail_price' => $p['retail_price'] ?? 0,
                'stock_quantity' => 0,
                'track_stock' => false,
                'is_active' => true,
                'visible_to_retail' => $retailVisible,
                'visible_to_b2b' => $b2bVisible,
                'sort_order' => $sortOrder,
            ],
        );

        $sync = [];
        foreach (($p['categoryIds'] ?? []) as $order => $srcId) {
            if (isset($modelBySource[$srcId])) {
                $sync[$modelBySource[$srcId]->id] = [
                    'is_primary' => $srcId === ($p['primary_category_id'] ?? null),
                    'sort_order' => $order,
                ];
            }
        }
        if ($sync && ! collect($sync)->contains(fn ($pivot) => $pivot['is_primary'])) {
            $sync[array_key_first($sync)]['is_primary'] = true;
        }
        $product->categories()->sync($sync);

        return $product;
    }

    private function attachImages(Product $product, array $p): int
    {
        if ($product->getMedia('images')->isNotEmpty()) {
            return 0;
        }
        $added = 0;
        foreach (($p['images'] ?? []) as $img) {
            $abs = $this->dataDir . '/' . ($img['local'] ?? '');
            if (! isset($img['local']) || ! is_file($abs)) {
                continue;
            }
            try {
                $product->addMedia($abs)->preservingOriginal()->toMediaCollection('images');
                $added++;
            } catch (\Throwable $e) {
                $this->command->warn("  image failed for {$p['slug']}: {$e->getMessage()}");
            }
        }
        return $added;
    }

    /** Match key shared by a product's retail and company twins. */
    private function matchKey(string $name): string
    {
        if ($code = $this->codeMatcher->extract($name)) {
            return 'C:' . $code;
        }
        $s = mb_strtolower($name);
        $s = preg_replace('/[0-9]+\s*(pc|ც|g|ml|მგ|გ)\b/u', '', $s);
        $s = preg_replace('/[^\p{L}\p{N}]+/u', '', $s) ?? $s;
        return 'N:' . $s;
    }

    private function cleanName(?string $name): string
    {
        $name = ltrim(trim((string) $name), "- \t");
        return trim(preg_replace('/\s{2,}/u', ' ', $name) ?? $name);
    }

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
        while (isset($used[$sku])) {
            $sku .= 'X';
        }
        $used[$sku] = true;
        return $sku;
    }
}
