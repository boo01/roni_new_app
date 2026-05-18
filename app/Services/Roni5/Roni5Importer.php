<?php

namespace App\Services\Roni5;

use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductGroupPrice;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;

class Roni5Importer
{
    public function __construct(private readonly CodeMatcher $matcher) {}

    /**
     * @param  array{
     *   b2c_csv: string,
     *   b2b_csv: ?string,
     *   report_csv: string,
     *   apply: bool,
     *   default_category_slug: ?string,
     *   default_b2b_group_slug: ?string,
     * }  $opts
     *
     * @return array{
     *   b2c_rows:int, b2b_rows:int,
     *   matched_ok:int, b2c_only:int, b2b_only:int, code_missing:int, duplicates:int,
     *   products_upserted:int, group_prices_upserted:int,
     *   report_path:string,
     * }
     */
    public function import(array $opts): array
    {
        [$b2c, $b2cIssues] = $this->indexByCode($this->readCsv($opts['b2c_csv']));
        [$b2b, $b2bIssues] = $opts['b2b_csv']
            ? $this->indexByCode($this->readCsv($opts['b2b_csv']))
            : [[], []];

        $report = [];
        $allCodes = array_unique(array_merge(array_keys($b2c), array_keys($b2b)));
        sort($allCodes);

        $stats = [
            'b2c_rows' => count($b2c) + count($b2cIssues['code-missing'] ?? []) + count($b2cIssues['duplicate'] ?? []),
            'b2b_rows' => count($b2b) + count($b2bIssues['code-missing'] ?? []) + count($b2bIssues['duplicate'] ?? []),
            'matched_ok' => 0,
            'b2c_only' => 0,
            'b2b_only' => 0,
            'code_missing' => count($b2cIssues['code-missing'] ?? []) + count($b2bIssues['code-missing'] ?? []),
            'duplicates' => count($b2cIssues['duplicate'] ?? []) + count($b2bIssues['duplicate'] ?? []),
            'products_upserted' => 0,
            'group_prices_upserted' => 0,
        ];

        foreach ($allCodes as $code) {
            $b2cRow = $b2c[$code] ?? null;
            $b2bRow = $b2b[$code] ?? null;

            if ($b2cRow && $b2bRow) {
                $status = 'ok';
                $stats['matched_ok']++;
            } elseif ($b2cRow) {
                $status = 'b2c-only';
                $stats['b2c_only']++;
            } else {
                $status = 'b2b-only';
                $stats['b2b_only']++;
            }

            $report[] = [
                'extracted_code' => $code,
                'b2c_title' => $b2cRow['title'] ?? '',
                'b2c_price' => $b2cRow['price'] ?? '',
                'b2b_title' => $b2bRow['title'] ?? '',
                'b2b_price' => $b2bRow['price'] ?? '',
                'status' => $status,
            ];
        }

        foreach ($b2cIssues['code-missing'] ?? [] as $row) {
            $report[] = ['extracted_code' => '', 'b2c_title' => $row['title'], 'b2c_price' => $row['price'] ?? '', 'b2b_title' => '', 'b2b_price' => '', 'status' => 'code-missing'];
        }
        foreach ($b2cIssues['duplicate'] ?? [] as $row) {
            $report[] = ['extracted_code' => $row['_code'], 'b2c_title' => $row['title'], 'b2c_price' => $row['price'] ?? '', 'b2b_title' => '', 'b2b_price' => '', 'status' => 'duplicate-b2c'];
        }

        $this->writeReport($opts['report_csv'], $report);

        if ($opts['apply']) {
            $defaultCategory = $opts['default_category_slug']
                ? Category::where('slug', $opts['default_category_slug'])->first()
                : null;
            $defaultGroup = $opts['default_b2b_group_slug']
                ? CustomerGroup::where('slug', $opts['default_b2b_group_slug'])->first()
                : CustomerGroup::where('is_default_for_b2b', true)->first();

            DB::transaction(function () use ($b2c, $b2b, $defaultCategory, $defaultGroup, &$stats): void {
                $allCodes = array_unique(array_merge(array_keys($b2c), array_keys($b2b)));
                foreach ($allCodes as $code) {
                    $b2cRow = $b2c[$code] ?? null;
                    $b2bRow = $b2b[$code] ?? null;
                    $primary = $b2cRow ?? $b2bRow;
                    if (! $primary) {
                        continue;
                    }
                    $product = Product::updateOrCreate(
                        ['sku' => $code],
                        [
                            'name_ka' => $primary['title'],
                            'slug' => Slug::generate($primary['title'], $code),
                            'description_ka' => $primary['description'] ?? null,
                            'retail_price' => (float) ($b2cRow['price'] ?? $b2bRow['price'] ?? 0),
                            'category_id' => $defaultCategory?->id,
                            'is_active' => true,
                        ],
                    );
                    $stats['products_upserted']++;

                    if ($b2bRow && $defaultGroup) {
                        ProductGroupPrice::updateOrCreate(
                            ['product_id' => $product->id, 'customer_group_id' => $defaultGroup->id],
                            ['price' => (float) $b2bRow['price']],
                        );
                        $stats['group_prices_upserted']++;
                    }
                }
            });
        }

        return $stats + ['report_path' => $opts['report_csv']];
    }

    /**
     * Returns [['code' => row], ['code-missing' => [...], 'duplicate' => [...]]].
     *
     * @return array{0:array<string,array<string,mixed>>, 1:array<string,array<int,array<string,mixed>>>}
     */
    private function indexByCode(array $rows): array
    {
        $byCode = [];
        $issues = ['code-missing' => [], 'duplicate' => []];

        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $code = $this->matcher->extract($title);
            if ($code === null) {
                $issues['code-missing'][] = $row;
                continue;
            }
            if (isset($byCode[$code])) {
                $row['_code'] = $code;
                $issues['duplicate'][] = $row;
                continue;
            }
            $byCode[$code] = $row;
        }

        return [$byCode, $issues];
    }

    /** @return array<int, array<string, mixed>> */
    private function readCsv(string $path): array
    {
        if (! is_readable($path)) {
            throw new \RuntimeException("CSV not readable: $path");
        }
        $rows = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        if (! $header) {
            return [];
        }
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);
        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) !== count($header)) {
                continue;
            }
            $rows[] = array_combine($header, $line);
        }
        fclose($handle);
        return $rows;
    }

    private function writeReport(string $path, array $rows): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $handle = fopen($path, 'w');
        fputcsv($handle, ['extracted_code', 'b2c_title', 'b2c_price', 'b2b_title', 'b2b_price', 'status']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['extracted_code'] ?? '',
                $row['b2c_title'] ?? '',
                $row['b2c_price'] ?? '',
                $row['b2b_title'] ?? '',
                $row['b2b_price'] ?? '',
                $row['status'] ?? '',
            ]);
        }
        fclose($handle);
    }
}
