<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Do NOT trust proxy proto headers here: Caddy (the last hop before
        // php-fpm) rewrites X-Forwarded-Proto to "http", and trusting it would
        // override the real scheme and make Laravel see requests as insecure —
        // breaking https URL generation and Livewire's signed upload URLs.
        // Instead the Caddyfile sets `env HTTPS on`, so Laravel detects https
        // from $_SERVER['HTTPS']. See deploy/Caddyfile.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
