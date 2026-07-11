<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Production is always served over HTTPS at the edge (Cloudflare → nginx),
        // but the internal hop (nginx → Caddy → php-fpm) is plain HTTP and Caddy
        // resets X-Forwarded-Proto to http, so proxy detection alone can't see the
        // real scheme. Force https so generated URLs / redirects aren't http://.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // @mt('text') — uppercase Georgian Mkhedruli into Mtavruli code points
        // for use with the Noto Sans Mtavruli heading font (CSS text-transform
        // does not case-map Georgian in current Chromium).
        Blade::directive('mt', function (string $expression): string {
            return "<?php echo e(mb_strtoupper((string) ($expression), 'UTF-8')); ?>";
        });
    }
}
