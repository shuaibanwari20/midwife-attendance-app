<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind TLS-terminating proxies (Railway/Back4App): honor X-Forwarded-*
        // so generated URLs (assets, form actions, redirects) use https.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        // Feature 44 — panel language (Dari / Pashto) from the session.
        $middleware->web(append: [\App\Http\Middleware\SetAdminLocale::class]);

        // Unauthenticated API calls must get 401 JSON, never a redirect/500.
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => $request->is('api/*') ? null : route('admin.login'));

        // API routes are stateless token based (Sanctum); keep web session for admin panel.
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API clients always get a clean 401 JSON (never a redirect or 500).
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
        });
    })->create();
