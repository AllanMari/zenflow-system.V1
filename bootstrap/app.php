<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureSessionValid;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 🌟 Trust Render's load balancers to prevent page-refresh loops
        $middleware->trustProxies(at: '*');
        // Aliases for route middleware
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'auth' => Authenticate::class,
        ]);

        // Append to web middleware group — runs on EVERY web request
        $middleware->web(append: [
            EnsureUserIsActive::class,
            EnsureSessionValid::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();