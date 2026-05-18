<?php

namespace App\Console\Commands;

use App\Services\Roni5\Roni5Importer;
use Illuminate\Console\Command;

class ImportFromRoni5 extends Command
{
    protected $signature = 'import:roni5
                            {--b2c= : Path to the B2C (retail) CSV export}
                            {--b2b= : Path to the B2B (company) CSV export (optional)}
                            {--report=storage/app/import-report.csv : Where to write the merge report CSV}
                            {--category= : Slug of an existing category to assign new products to}
                            {--group= : Slug of the customer group that owns the B2B prices (defaults to the is_default_for_b2b group)}
                            {--apply : Actually upsert products and B2B prices into the DB (default is a dry run / report only)}';

    protected $description = 'Migrate products from a Wix CSV export of roni5.ge into the new schema, matching B2C/B2B by product code in title.';

    public function handle(Roni5Importer $importer): int
    {
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
}
