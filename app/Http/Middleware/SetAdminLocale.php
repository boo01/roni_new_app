<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    public const SESSION_KEY = 'admin_locale';

    public const LOCALES = ['ka', 'en'];

    public const DEFAULT = 'ka';

    /**
     * Apply the admin's chosen UI language (session-persisted) to this
     * request. Scoped to the Filament panel — the storefront keeps the
     * app default locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get(self::SESSION_KEY, self::DEFAULT);

        if (in_array($locale, self::LOCALES, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
