<?php

use App\Http\Middleware\CaptureUtm;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ThrottleAccountForms;
use App\Support\Cloudflare;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The live site sits behind Cloudflare, which passes on the visitor's IP and HTTPS.
        $middleware->trustProxies(at: Cloudflare::IPS);

        $middleware->web(append: [
            CaptureUtm::class,
            EnsureAccountActive::class,
            ThrottleAccountForms::class,
        ]);

        $middleware->alias([
            'feature' => EnsureFeatureEnabled::class,
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
