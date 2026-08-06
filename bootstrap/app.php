<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        $middleware->validateCsrfTokens(except: [
            
        ]);

        $middleware->statefulApi();

        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'super_admin' => \App\Http\Middleware\IsSuperAdmin::class, 
            'api.kpi' => \App\Http\Middleware\VerifyKpiApiKey::class,
            'block.pkl' => \App\Http\Middleware\BlockPkl::class,
        ]);
        
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        \Sentry\Laravel\Integration::handles($exceptions);

        $exceptions->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });

        $exceptions->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*') && $e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                return response()->json([
                    'message' => 'Too many attempts. Please try again later.'
                ], 429);
            }
        });
    })
    ->create();
