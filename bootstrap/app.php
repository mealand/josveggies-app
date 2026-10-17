<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StaffSecurity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Csp\AddCspHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            AddCspHeaders::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'staff' => StaffSecurity::class,
        ]);

        // Webhooks are authenticated by the gateway's HMAC signature instead of a CSRF token.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('console', 'console/*')
            ? route('console.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'webhooks/*') || $request->expectsJson(),
        );

        // Business rule violations are expected; show the message instead of an error page.
        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });

        $exceptions->dontReport([BusinessRuleException::class, AuthorizationException::class]);
    })->create();
