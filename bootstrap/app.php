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
        // Production runs behind Cloudflare → nginx → Caddy. Trust proxy
        // headers (X-Forwarded-Proto/Host/For) so Laravel detects HTTPS and the
        // real host, and generates https:// URLs, redirects and secure cookies
        // instead of http://. Only our own proxies can reach php-fpm.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
