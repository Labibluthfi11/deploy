<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/admin/absensi';


    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // --- START: Pendaftaran Alias Middleware Baru ---
        Route::middleware([
            // Daftarkan alias 'admin' ke class middleware IsAdmin Anda
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
        // --- END: Pendaftaran Alias Middleware Baru ---

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // Ini yang memastikan rute di api.php dimuat dengan awalan '/api'.
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Ini yang memuat rute web, termasuk rute register default Laravel.
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
