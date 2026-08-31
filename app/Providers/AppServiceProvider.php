<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // 1. Inisialisasi Nonce secara konsisten
        $this->app->singleton('csp_nonce', fn () => bin2hex(random_bytes(16)));
        
        // 2. Register ke Vite
        \Illuminate\Support\Facades\Vite::useCspNonce(fn () => app('csp_nonce'));

        // Define Rate Limiter untuk API secara umum
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate Limiter lebih ketat untuk upload atau aksi sensitif
        \Illuminate\Support\Facades\RateLimiter::for('uploads', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
