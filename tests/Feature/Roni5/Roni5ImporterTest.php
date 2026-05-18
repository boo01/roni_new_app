<?php

namespace Tests\Feature\Roni5;

use App\Models\CustomerGroup;
use App\Models\Product;
use App\Services\Roni5\CodeMatcher;
use App\Services\Roni5\Roni5Importer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Roni5ImporterTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDir;
    private Roni5Importer $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/roni5-test-' . uniqid();
        mkdir($this->tempDir, 0775, true);
        $this->importer = new Roni5Importer(new CodeMatcher());
    }

    protected function tearDown(): void
    {
        $this->cleanDir($this->tempDir);
        parent::tearDown();
    }

    public function test_dry_run_writes_report_and_does_not_touch_db(): void
    {
        $b2c = $this->writeCsv('b2c.csv', [
            ['title', 'price'],
            ['კონვერტი NJ-012-128', '1.50'],
            ['მშრალი წებო 1505 15g', '2.30'],
            ['პროდუქცია კოდის გარეშე', '5.00'],
        ]);
        $b2b = $this->writeCsv('b2b.csv', [
            ['title', 'price'],
            ['კონვერტი NJ-012-128', '1.20'],
            ['ექსკლუზიური 9999 ნივთი', '10.00'],
        ]);

        $stats = $this->importer->import([
            'b2c_csv' => $b2c,
            'b2b_csv' => $b2b,
            'report_csv' => $this->tempDir . '/report.csv',
            'apply' => false,
            'default_category_slug' => null,
            'default_b2b_group_slug' => null,
        ]);

        $this->assertSame(3, $stats['b2c_rows']);
        $this->assertSame(2, $stats['b2b_rows']);
        $this->assertSame(1, $stats['matched_ok']);     // NJ-012-128 in both
        $this->assertSame(1, $stats['b2c_only']);       // 1505
        $this->assertSame(1, $stats['b2b_only']);       // 9999
        $this->assertSame(1, $stats['code_missing']);   // "კოდის გარეშე"
        $this->assertSame(0, $stats['products_upserted']);

        $this->assertSame(0, Product::count());
        $this->assertFileExists($stats['report_path']);

        $report = $this->readCsv($stats['report_path']);
        $this->assertCount(4, $report);
        $byStatus = collect($report)->groupBy('status');
        $this->assertNotEmpty($byStatus['ok']);
        $this->assertNotEmpty($byStatus['b2c-only']);
        $this->assertNotEmpty($byStatus['b2b-only']);
        $this->assertNotEmpty($byStatus['code-missing']);
    }

    public function test_apply_upserts_products_and_b2b_overrides(): void
    {
        $group = CustomerGroup::create([
            'name' => 'Companies',
            'slug' => 'companies',
            'discount_percent' => 0,
            'is_default_for_b2b' => true,
        ]);

        $b2c = $this->writeCsv('b2c.csv', [
            ['title', 'price'],
            ['კონვერტი NJ-012-128', '1.50'],
            ['მშრალი წებო 1505 15g', '2.30'],
        ]);
        $b2b = $this->writeCsv('b2b.csv', [
            ['title', 'price'],
            ['კონვერტი NJ-012-128', '1.20'],
        ]);

        $stats = $this->importer->import([
            'b2c_csv' => $b2c,
            'b2b_csv' => $b2b,
            'report_csv' => $this->tempDir . '/report.csv',
            'apply' => true,
            'default_category_slug' => null,
            'default_b2b_group_slug' => null,
        ]);

        $this->assertSame(2, $stats['products_upserted']);
        $this->assertSame(1, $stats['group_prices_upserted']);

        $p = Product::where('sku', 'NJ-012-128')->first();
        $this->assertNotNull($p);
        $this->assertSame('1.50', (string) $p->retail_price);

        $override = $p->groupPrices()->where('customer_group_id', $group->id)->first();
        $this->assertNotNull($override);
        $this->assertSame('1.20', (string) $override->price);
    }

    private function writeCsv(string $name, array $rows): string
    {
        $path = $this->tempDir . '/' . $name;
        $h = fopen($path, 'w');
        foreach ($rows as $r) {
            fputcsv($h, $r);
        }
        fclose($h);
        return $path;
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        $h = fopen($path, 'r');
        $header = fgetcsv($h);
        while (($line = fgetcsv($h)) !== false) {
            $rows[] = array_combine($header, $line);
        }
        fclose($h);
        return $rows;
    }

    private function cleanDir(string $dir): void
    {
        if (! is_dir($dir)) return;
        foreach (glob("$dir/*") as $f) {
            unlink($f);
        }
        rmdir($dir);
    }
}
