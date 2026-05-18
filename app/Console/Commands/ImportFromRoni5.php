<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Roni5\CodeMatcher;
use App\Services\Roni5\Roni5Importer;
use App\Services\Roni5\Roni5Scraper;
use App\Support\Slug;
use Illuminate\Console\Command;

class ImportFromRoni5 extends Command
{
    protected $signature = 'import:roni5
                            {--scrape-url=* : One or more roni5.ge category URLs to live-scrape (Wix-rendered JS pages)}
                            {--category= : Slug of an existing local category to attach scraped/imported products to as primary}
                            {--b2c= : Path to the B2C (retail) CSV export}
                            {--b2b= : Path to the B2B (company) CSV export (optional)}
                            {--report=storage/app/import-report.csv : Where to write the merge report CSV}
                            {--group= : Slug of the customer group that owns the B2B prices (defaults to the is_default_for_b2b group)}
                            {--apply : Actually upsert products and B2B prices into the DB (default is a dry run)}
                            {--limit= : Cap the number of products processed per scraped URL (for testing)}';

    protected $description = 'Migrate products from roni5.ge — live-scrape (--scrape-url) or CSV (--b2c/--b2b).';

    public function handle(Roni5Importer $importer): int
    {
        $scrapeUrls = (array) $this->option('scrape-url');
        if ($scrapeUrls !== []) {
            return $this->runScrape($scrapeUrls);
        }


        $b2cPath = $this->option('b2c');
        if (! $b2cPath) {
            $this->error('--b2c=path-to-csv is required.');
            return self::FAILURE;
        }

        $opts = [
            'b2c_csv' => $this->resolvePath($b2cPath),
            'b2b_csv' => $this->option('b2b') ? $this->resolvePath($this->option('b2b')) : null,
            'report_csv' => $this->resolvePath($this->option('report')),
            'apply' => (bool) $this->option('apply'),
            'default_category_slug' => $this->option('category'),
            'default_b2b_group_slug' => $this->option('group'),
        ];

        $this->info('Importing from Roni5 CSVs');
        $this->line('  B2C: ' . $opts['b2c_csv']);
        $this->line('  B2B: ' . ($opts['b2b_csv'] ?? '(none)'));
        $this->line('  Report: ' . $opts['report_csv']);
        $this->line('  Mode: ' . ($opts['apply'] ? 'APPLY (writes to DB)' : 'DRY RUN'));

        $stats = $importer->import($opts);

        $this->newLine();
        $this->info('Result:');
        $this->table(
            ['Metric', 'Value'],
            collect($stats)->map(fn ($v, $k) => [$k, is_scalar($v) ? (string) $v : json_encode($v)])->values()->all(),
        );

        $this->newLine();
        $this->line('Report written to: <info>' . $stats['report_path'] . '</info>');
        if (! $opts['apply']) {
            $this->line('Re-run with <info>--apply</info> after reviewing the report.');
        }

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path(ltrim($path, './'));
    }

    private function runScrape(array $urls): int
    {
        $scraper = app(Roni5Scraper::class);
        $matcher = new CodeMatcher();
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $targetCategorySlug = $this->option('category');
        $targetCategory = $targetCategorySlug
            ? \App\Models\Category::where('slug', $targetCategorySlug)->first()
            : null;

        $this->info('Live-scraping roni5.ge');
        $this->line('  Target category: ' . ($targetCategory?->name_ka ?? '(none — products won\'t be attached to a category)'));
        $this->line('  Mode: ' . ($apply ? 'APPLY (writes DB + downloads images)' : 'DRY RUN'));

        $stats = ['urls' => 0, 'products_found' => 0, 'products_upserted' => 0, 'images_downloaded' => 0, 'code_missing' => 0];

        foreach ($urls as $url) {
            $stats['urls']++;
            $this->newLine();
            $this->info('  → ' . $url);

            try {
                $products = $scraper->scrapeCategory($url);
            } catch (\Throwable $e) {
                $this->warn('    failed: ' . $e->getMessage());
                continue;
            }

            if ($limit) {
                $products = array_slice($products, 0, $limit);
            }

            $stats['products_found'] += count($products);
            $this->line('    found ' . count($products) . ' products');

            foreach ($products as $p) {
                $code = $matcher->extract($p['title']);
                if ($code === null) {
                    $stats['code_missing']++;
                    $this->line('      ! ' . $p['title'] . ' — no code, skipping');
                    continue;
                }

                $this->line(sprintf('      • %s | ₾%s | %s', $code, $p['price'], $p['title']));

                if (! $apply) {
                    continue;
                }

                $product = Product::updateOrCreate(
                    ['sku' => $code],
                    [
                        'name_ka' => $p['title'],
                        'slug' => Slug::generate($p['title'], $code),
                        'retail_price' => $p['price'],
                        'is_active' => true,
                    ],
                );
                $stats['products_upserted']++;

                if ($targetCategory) {
                    $product->categories()->syncWithoutDetaching([
                        $targetCategory->id => ['is_primary' => true, 'sort_order' => 0],
                    ]);
                }

                if (! empty($p['image']) && $product->getMedia('images')->isEmpty()) {
                    $imagePath = $scraper->downloadImage($p['image']);
                    if ($imagePath) {
                        try {
                            $product->addMedia($imagePath)->toMediaCollection('images');
                            $stats['images_downloaded']++;
                        } catch (\Throwable $e) {
                            @unlink($imagePath);
                        }
                    }
                }
            }
        }

        $this->newLine();
        $this->info('Result:');
        $this->table(['Metric', 'Value'], collect($stats)->map(fn ($v, $k) => [$k, (string) $v])->values()->all());

        if (! $apply) {
            $this->line('Re-run with <info>--apply</info> to write products and download images.');
        }

        return self::SUCCESS;
    }
}
