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
        // Trust proxy headers for HTTPS detection when behind a reverse proxy (e.g., Railway)
        $middleware->trustProxies(at: [
            '10.0.0.0/8',      // Docker/Kubernetes internal networks
            '172.16.0.0/12',   // Docker bridge networks
            '192.168.0.0/16',  // Private networks
            '127.0.0.1',       // Localhost
        ]);

        $middleware->appendToGroup('web', [
            \Webkul\Security\Http\Middleware\EnsureAdminLoginNoRedirectLoop::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
