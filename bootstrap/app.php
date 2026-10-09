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
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        // Exclude PayMongo webhook from CSRF protection (called by PayMongo servers)
        $middleware->validateCsrfTokens(except: [
            'webhooks/paymongo',
        ]);

        // Check is_active on every authenticated request so that sessions
        // created before an account was disabled are invalidated immediately.
        $middleware->appendToGroup('auth', \App\Http\Middleware\EnsureUserIsActive::class);
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
