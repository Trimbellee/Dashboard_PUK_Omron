<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Import middleware kustom kamu
use App\Http\Middleware\AuthCheck;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'authcheck' => \App\Http\Middleware\AuthCheck::class,
        'role.check' => \App\Http\Middleware\RoleCheck::class,
        'anggota.role' => \App\Http\Middleware\EnsureAnggotaRole::class,
        'anggota.auth' => \App\Http\Middleware\EnsureAnggotaAuthenticated::class,
	'anggota.login' => \App\Http\Middleware\EnsureAnggotaLoggedIn::class,
	'check.page.access' => \App\Http\Middleware\CheckPageAccess::class,
    ]);
    $middleware->web([
        // middleware web default
    ]);

    $middleware->api([
        // middleware api default
    ]);
})

    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * Di sini kamu bisa tambahkan custom handler,
         * misalnya untuk error JSON, error 404, dsb.
         */
    })
    ->create();
