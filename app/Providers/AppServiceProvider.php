<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // @mt('text') — uppercase Georgian Mkhedruli into Mtavruli code points
        // for use with the Noto Sans Mtavruli heading font (CSS text-transform
        // does not case-map Georgian in current Chromium).
        Blade::directive('mt', function (string $expression): string {
            return "<?php echo e(mb_strtoupper((string) ($expression), 'UTF-8')); ?>";
        });
    }
}
